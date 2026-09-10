<?php
declare(strict_types=1);

namespace App\Service;

use App\Dto\Project\ProjectDto;
use App\Models\Questionnaire\Project;
use App\Models\Questionnaire\WorksPackage;
use App\Models\User;
use App\Repository\ProjectRepository;
use Carbon\Carbon;

class TenderNoticeService
{

    private ProjectRepository $projectRepository;

    public function __construct(ProjectRepository $projectRepository)
    {
        $this->projectRepository = $projectRepository;
    }

    /**
     * @throws \Exception
     */
    public function createProject(array $data, User $user) : ?Project
    {
        $id = $data['id'];
        $ocid = $data['ocid'] ?? null;
        $name = $data['tender']['title'] ?? null;
        $description = $data['tender']['description'] ?? null;
        $postcode = $data['parties'][0]['address']['postalCode'] ?? null;
        $organisation = $data['parties'][0]['name'] ?? null;
        $value = $data['tender']['value']['amount'] ?? null;

        $dateStart = $data['tender']['contractPeriod']['startDate'] ?? null;
        $dateEnd = $data['tender']['contractPeriod']['endDate'] ?? null;
        $dateEndTender = $data['tender']['tenderPeriod']['endDate'] ?? null;

        $dateStart = $dateStart ? Carbon::parse($dateStart)->format('d-m-Y') : null;
        $dateEnd = $dateEnd ? Carbon::parse($dateEnd)->format('d-m-Y') : null;
        $dateEndTender = $dateEndTender ? Carbon::parse($dateEndTender)->format('d-m-Y') : null;

        try {
            $dto = ProjectDto::createFromArray([
                'name' => $name,
                'description' => $description,
                'ocid' => $ocid,
                'tender_notice_id' => $id,
                'postcode' => $postcode,
                'client_name' => $organisation,
                'status' => 1,
                'stage' => 1,
                'type' => 1,
                'project_value' => $value,
                'date_start' => $dateStart,
                'date_end' => $dateEnd,
                'date_end_tender' => $dateEndTender,
            ]);

            $project = $this->projectRepository->create($dto, $user);
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }

        if (!$project) {
            return null;
        }

        return $project;
    }

    /**
     * Persist a works packages tree into the works_packages table, linking each
     * node to its parent via the parent_id column.
     *
     * @param array<int, array<string, mixed>> $worksPackages Tree as built by buildWorksPackagesTree().
     * @param int $projectId
     * @param int $userId
     * @param int|null $parentId The id of the parent row, or null for top-level packages.
     */
    public function storeWorksPackages(array $worksPackages, int $projectId, int $userId, ?int $parentId = null): void
    {
        foreach ($worksPackages as $package) {
            /** @var WorksPackage $wp */
            $wp = WorksPackage::create([
                'name' => $package['description'] ?? $package['id'],
                'cpv_code' => $package['id'],
                'project_id' => $projectId,
                'user_id' => $userId,
                'parent_id' => $parentId,
            ]);

            if (!empty($package['children'])) {
                $this->storeWorksPackages($package['children'], $projectId, $userId, $wp->getId());
            }
        }
    }

    /**
     * Organise a flat list of CPV works packages into a parent/child tree.
     *
     * CPV codes are hierarchical: trailing zeros denote how general a code is.
     * A code is a child of the most specific other code in the set whose
     * "significant" prefix (the code with trailing zeros stripped) is a prefix
     * of its own. e.g. 45211340 (sig 4521134) is a child of 45211300 (sig 452113).
     *
     * @param array<int, array{scheme: string, id: string, description?: string}> $worksPackages
     * @return array<int, array<string, mixed>> Top-level packages, each with a nested "children" array.
     */
    public function buildWorksPackagesTree(array $worksPackages): array
    {
        // Index by id and seed an empty children bucket on each node.
        $nodes = [];
        foreach ($worksPackages as $package) {
            $package['children'] = [];
            $nodes[$package['id']] = $package;
        }

        // Resolve each node's parent: the existing code with the longest
        // significant prefix that is a proper prefix of this node's prefix.
        $tree = [];
        foreach ($nodes as $id => &$node) {
            $significant = rtrim((string)$id, '0');
            $parentId = null;
            $parentLength = -1;

            foreach ($nodes as $candidateId => $candidate) {
                if ($candidateId === $id) {
                    continue;
                }

                $candidateSignificant = rtrim((string)$candidateId, '0');
                $candidateLength = strlen($candidateSignificant);

                if ($candidateLength < strlen($significant)
                    && $candidateLength > $parentLength
                    && str_starts_with($significant, $candidateSignificant)
                ) {
                    $parentId = $candidateId;
                    $parentLength = $candidateLength;
                }
            }

            if ($parentId === null) {
                $tree[] = &$node;
            } else {
                $nodes[$parentId]['children'][] = &$node;
            }
        }
        unset($node);

        return $tree;
    }
}
