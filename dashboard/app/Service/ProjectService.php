<?php
declare(strict_types=1);

namespace App\Service;

use App\Models\Questionnaire\WorksPackage;
use App\Models\User;
use BoqAllocator\Services\BoqAllocationEngine;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

class ProjectService
{
    /** Maximum number of works package levels (parent + 3 nested levels) that can be imported. */
    private const MAX_WORKS_PACKAGE_DEPTH = 4;

    private const BOQ_PREVIEW_CACHE_PREFIX = 'boq:preview:';
    private const BOQ_PREVIEW_TTL_MINUTES = 60;

    private const BOQ_TEMPLATES = [
        'nrm1' => 'NRM1 template.csv',
        'nrm2' => 'NRM2 template.csv',
        'wd' => 'WD template.csv',
    ];

    private BoqAllocationEngine $boqAllocationEngine;

    public function __construct(BoqAllocationEngine $boqAllocationEngine)
    {
        $this->boqAllocationEngine = $boqAllocationEngine;
    }

    /**
     * @throws \Exception
     */
    public function importBoqFromCsv(int $projectId, string $csvPath): void
    {
        if (!is_file($csvPath) || !is_readable($csvPath)) {
            throw new \Exception(sprintf('BOQ CSV file is missing or unreadable: %s', $csvPath));
        }

        $handle = fopen($csvPath, 'r');

        if ($handle === false) {
            throw new \Exception(sprintf('Unable to open BOQ CSV file: %s', $csvPath));
        }

        // Skip header row
        fgetcsv($handle, 10000, ',');

        $batch = [];

        while (($row = fgetcsv($handle, 10000, ',')) !== false) {
            // Columns: Project ID(0), conquest_ref(1), top_level_works_package(2),
            //          Bill(3), Section(4), Page(5), Ref(6), Description(7),
            //          Quantity(8), Unit(9), Rate(10), Extension(11), Activity(12)
            $batch[] = [
                'project_id'    => $projectId,
                'conquest_ref'  => $row[1] !== '' ? $row[1] : null,
                'works_package' => $row[2] !== '' ? $row[2] : null,
                'bill'          => $row[3] !== '' ? (int) $row[3] : null,
                'section'       => $row[4] !== '' ? (int) $row[4] : null,
                'page'          => $row[5] !== '' ? (int) $row[5] : null,
                'ref'           => $row[6] !== '' ? $row[6] : null,
                'description'   => $row[7] !== '' ? $row[7] : null,
                'quantity'      => $row[8] !== '' ? (float) $row[8] : null,
                'unit'          => $row[9] !== '' ? $row[9] : null,
            ];

            if (count($batch) === 500) {
                DB::table('boq_items')->insert($batch);
                $batch = [];
            }
        }

        if (!empty($batch)) {
            DB::table('boq_items')->insert($batch);
        }

        fclose($handle);
    }

    public function deleteBoqItems(int $projectId): void
    {
        DB::table('boq_items')->where('project_id', $projectId)->delete();
    }

    public static function getBoqTemplates(): array
    {
        return self::BOQ_TEMPLATES;
    }

    public function createWorksPackagesPreview(
        UploadedFile $file,
        string $template,
        int $projectId,
        int $userId
    ): array {
        $templateFilename = self::BOQ_TEMPLATES[$template] ?? null;

        if ($templateFilename === null) {
            throw new InvalidArgumentException('The selected BOQ template is not supported.');
        }

        $storedPath = Storage::disk('local')->putFileAs(
            'boq_files',
            $file,
            Str::uuid()->toString() . '.xlsx'
        );

        if (!is_string($storedPath)) {
            throw new RuntimeException('The uploaded BOQ file could not be stored.');
        }

        try {
            $this->ensureDecisionCacheExists();

            $templatePath = rtrim((string) config('boq-allocator.templates_path'), DIRECTORY_SEPARATOR)
                . DIRECTORY_SEPARATOR
                . $templateFilename;

            if (!is_file($templatePath) || !is_readable($templatePath)) {
                throw new RuntimeException('The selected BOQ template is unavailable.');
            }

            $result = $this->boqAllocationEngine->allocate(
                boqPath: Storage::disk('local')->path($storedPath),
                templatePath: $templatePath,
                modelKey: (string) config('boq-allocator.default_model'),
                progressCallback: static function (string $statusMessage, int $percent): void {
                    Log::info('BOQ allocation progress', [
                        'percentage' => $percent,
                        'message' => $statusMessage,
                    ]);
                }
            );
        } finally {
            Storage::disk('local')->delete($storedPath);
        }

        $previewId = Str::uuid()->toString();
        $worksPackages = $this->addSelectionKeys($result->workPackages);

        Cache::put($this->getBoqPreviewCacheKey($previewId), [
            'project_id' => $projectId,
            'user_id' => $userId,
            'work_packages' => $worksPackages,
        ], now()->addMinutes(self::BOQ_PREVIEW_TTL_MINUTES));

        Log::info('BOQ allocation completed', $result->metadata + [
            'project_id' => $projectId,
            'user_id' => $userId,
            'preview_id' => $previewId,
        ]);

        return [
            'preview_id' => $previewId,
            'expires_in_minutes' => self::BOQ_PREVIEW_TTL_MINUTES,
            'metadata' => $result->metadata,
            'work_packages' => $worksPackages,
        ];
    }

    /**
     * @throws AuthorizationException
     */
    public function storeWorksPackagesFromPreview(
        string $previewId,
        array $selectedKeys,
        int $projectId,
        User $user
    ): array {
        $preview = Cache::get($this->getBoqPreviewCacheKey($previewId));

        if (!is_array($preview)) {
            throw new InvalidArgumentException('This BOQ preview has expired. Please upload the file again.');
        }

        if ((int) ($preview['project_id'] ?? 0) !== $projectId
            || (int) ($preview['user_id'] ?? 0) !== $user->getId()) {
            throw new AuthorizationException('This BOQ preview does not belong to the current project and user.');
        }

        $selectedKeys = array_values(array_unique(array_filter($selectedKeys, 'is_string')));
        $worksPackages = $this->filterPreviewTree(
            (array) ($preview['work_packages'] ?? []),
            array_fill_keys($selectedKeys, true)
        );

        if ($worksPackages === []) {
            throw new InvalidArgumentException('Select at least one BOQ item to import.');
        }

        $created = $this->storeWorksPackages($worksPackages, $projectId, $user);
        Cache::forget($this->getBoqPreviewCacheKey($previewId));

        return $created;
    }

    private function getBoqPreviewCacheKey(string $previewId): string
    {
        return self::BOQ_PREVIEW_CACHE_PREFIX . $previewId;
    }

    private function ensureDecisionCacheExists(): void
    {
        $cachePath = (string) config('boq-allocator.decision_cache_path');
        $seedPath = (string) config('boq-allocator.decision_cache_seed_path');

        if ($cachePath === '' || is_file($cachePath)) {
            return;
        }

        if ($seedPath === '' || !is_file($seedPath)) {
            throw new RuntimeException('The BOQ decision-cache seed is unavailable.');
        }

        File::ensureDirectoryExists(dirname($cachePath));

        if (!@copy($seedPath, $cachePath) && !is_file($cachePath)) {
            throw new RuntimeException('The BOQ decision cache could not be initialised.');
        }
    }

    private function addSelectionKeys(array $items, string $parentPath = 'root'): array
    {
        $result = [];

        foreach (array_values($items) as $index => $item) {
            if (!is_array($item)) {
                continue;
            }

            $path = $parentPath . '/' . $index . ':' . (string) ($item['id'] ?? 'node');
            $item['selection_key'] = 'boq_' . substr(hash('sha256', $path), 0, 24);
            $item['children'] = $this->addSelectionKeys((array) ($item['children'] ?? []), $path);
            $result[] = $item;
        }

        return $result;
    }

    private function filterPreviewTree(array $items, array $selectedKeys): array
    {
        $result = [];

        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $children = $this->filterPreviewTree((array) ($item['children'] ?? []), $selectedKeys);
            $selected = isset($selectedKeys[(string) ($item['selection_key'] ?? '')]);

            if (!$selected && $children === []) {
                continue;
            }

            $item['children'] = $children;
            unset($item['selection_key']);
            $result[] = $item;
        }

        return $result;
    }

    /**
     * @throws \Throwable
     */
    public function storeWorksPackages(array $worksPackages, int $projectId, User $user): array
    {
        return DB::transaction(fn (): array => $this->storeWorksPackageLevel(
            $worksPackages,
            $projectId,
            $user,
            null,
            1
        ));
    }

    /**
     * Stores one level of the works package tree and recurses into its children,
     * up to self::MAX_WORKS_PACKAGE_DEPTH levels deep.
     *
     * @return int[] Ids of every works package created at this level and below.
     */
    private function storeWorksPackageLevel(
        array $items,
        int $projectId,
        User $user,
        ?int $parentId,
        int $depth
    ): array {
        $created = [];

        foreach ($items as $data) {
            if (!is_array($data)) {
                Log::warning('Skipping invalid works package item because it is not an array.', [
                    'item' => $data,
                    'parent_id' => $parentId,
                    'depth' => $depth,
                ]);

                continue;
            }

            $name = trim((string) ($data['name'] ?? ''));

            if ($name === '') {
                Log::warning('Skipping works package item because it has no name.', [
                    'item' => $data,
                    'parent_id' => $parentId,
                    'depth' => $depth,
                ]);

                continue;
            }

            if (mb_strlen($name) > 255) {
                throw new InvalidArgumentException('A BOQ hierarchy item name exceeds 255 characters.');
            }

            $worksPackage = WorksPackage::create([
                'name' => $name,
                'user_id' => $user->getId(),
                'project_id' => $projectId,
                'parent_id' => $parentId,
                'high_risk' => 0,
            ]);

            $created[] = $worksPackage->getId();

            $children = $data['children'] ?? [];

            if (!is_array($children)) {
                Log::warning('Ignoring invalid children value for works package.', [
                    'works_package' => $name,
                    'children' => $children,
                    'depth' => $depth,
                ]);

                continue;
            }

            if ($children === []) {
                continue;
            }

            if ($depth >= self::MAX_WORKS_PACKAGE_DEPTH) {
                Log::warning('Ignoring works package children beyond the maximum supported depth.', [
                    'works_package' => $name,
                    'max_depth' => self::MAX_WORKS_PACKAGE_DEPTH,
                    'ignored_children' => count($children),
                ]);

                continue;
            }

            $created = array_merge($created, $this->storeWorksPackageLevel(
                $children,
                $projectId,
                $user,
                $worksPackage->getId(),
                $depth + 1
            ));
        }

        return $created;
    }
}
