<?php
declare(strict_types=1);

namespace App\Service;

use App\Models\Questionnaire\WorksPackage;
use App\Models\User;
use App\Repository\ProjectRepository;
use App\Service\BoqAllocator\BoqAllocationEngine;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProjectService
{
    /** Maximum number of works package levels (parent + 3 nested levels) that can be imported. */
    private const MAX_WORKS_PACKAGE_DEPTH = 4;

    private ProjectRepository $projectRepository;
    private OpenAIService $openAiService;

    public function __construct(ProjectRepository $projectRepository, OpenAIService $openAiService) {
        $this->projectRepository = $projectRepository;
        $this->openAiService = $openAiService;
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

    public function getWorksPackagesFromBoqFile(UploadedFile $file, string $template): array
    {
        $worksPackages = [];

        try {
            $cacheKey = $this->getWorksPackagesCacheKey($file);
            //Cache::store('redis')->forget($cacheKey);

            $worksPackages = Cache::store('redis')->remember($cacheKey, now()->addHour(), function () use ($file, $template) {
                $path = Storage::putFileAs('boq_files', $file, $file->getClientOriginalName());
                $engine = app(BoqAllocationEngine::class);

                $result = $engine->allocate(
                    boqPath: storage_path('app/' . $path),
                    templatePath: storage_path('app/templates/' . $template),
                    //modelKey: 'gemini-3.6-flash',
                    modelKey: 'gemini-3.5-flash-lite',
                    //customPromptRules: 'Always allocate drainage works to Substructure contractor.',
                    progressCallback: function (string $statusMessage, int $percent) {
                        logger()->info("[{$percent}%] {$statusMessage}");
                    }
                );

                $metadata = $result->metadata;
                Log::info(json_encode($metadata));

                return $result->workPackages;
            });

            if (!is_array($worksPackages)) {
                throw new \Exception('Response does not contain a valid work_packages array.');
            }
        } catch (\Exception $e) {
            Log::error('Failed to get works packages from file: ' . $e->getMessage());
        }

        return $worksPackages;
    }

    private function getWorksPackagesCacheKey(UploadedFile $file): string
    {
        $filePath = $file->getRealPath();

        if ($filePath !== false && is_readable($filePath)) {
            return 'openai:works_packages:' . hash_file('sha256', $filePath);
        }

        return 'openai:works_packages:' . hash('sha256', $file->getClientOriginalName());
    }

    /**
     * @throws \Throwable
     */
    public function storeWorksPackages(array $worksPackages, int $projectId, User $user): array
    {
        DB::beginTransaction();

        try {
            $created = $this->storeWorksPackageLevel($worksPackages, $projectId, $user, null, 1);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error storing works packages: ' . $e->getMessage(), [
                'works_packages' => $worksPackages,
                'project_id' => $projectId,
                'user_id' => $user->getId(),
            ]);

            return [];
        }

        return $created;
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
