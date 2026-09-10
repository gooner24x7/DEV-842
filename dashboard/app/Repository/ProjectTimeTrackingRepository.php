<?php
declare(strict_types=1);

namespace App\Repository;

use App\Dto\SearchParamsDto;
use App\Models\Questionnaire\ProjectTimeTracking;

class ProjectTimeTrackingRepository
{
    const string DEFAULT_ORDER_FIELD_NAME = 'project_time_tracking.id';

    public function __construct()
    {

    }

    public function find(SearchParamsDto $searchParamsDto, int $projectId): array
    {
        $query = ProjectTimeTracking::query()->select('project_time_tracking.*', 'users.first_name')
            ->join('users', 'users.id', '=', 'project_time_tracking.user_id')
            ->where('project_time_tracking.project_id', '=', $projectId);

        $query->orderBy(
            ($searchParamsDto->getOrderBy() ?? self::DEFAULT_ORDER_FIELD_NAME),
            $searchParamsDto->getOrderSortDesc() ? 'desc' : 'asc'
        );

        $totals = [
            'total_hours_ap' => 0,
            'total_hours_se' => 0,
            'total_spend_se' => 0,
        ];

        $items = $query->get()->toArray();

        foreach($items as $item) {
            $totals['total_hours_ap'] += $item['hours_ap'];
            $totals['total_hours_se'] += $item['hours_se'];
            $totals['total_spend_se'] += $item['spend_se'];
        }

        return ['totals' => $totals, 'items' => $items];
    }

    public function create(array $data): ProjectTimeTracking
    {
        /** @var ProjectTimeTracking $projectTimeTracking */
        $projectTimeTracking = ProjectTimeTracking::create([
            'project_id' => $data['project_id'],
            'user_id' => $data['user_id'],
            'hours_ap' => $data['hours_ap'],
            'hours_se' => $data['hours_se'],
            'spend_se' => $data['spend_se'],
            'name_se' => $data['name_se'],
        ]);

        return $projectTimeTracking;
    }
}
