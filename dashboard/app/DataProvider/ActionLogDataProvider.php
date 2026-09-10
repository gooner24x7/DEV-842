<?php

declare(strict_types=1);

namespace App\DataProvider;

use App\Dto\ActionLog\ActionLogDto;
use App\Models\ActionLog;
use App\Repository\ActionLogRepository;
use Illuminate\Database\Eloquent\Collection;
use Redis;

class ActionLogDataProvider extends BaseDataProvider
{
    private ActionLogRepository $actionLogRepository;

    public function __construct(ActionLogRepository $repository, Redis $redis)
    {
        parent::__construct($redis);

        $this->actionLogRepository = $repository;
    }

    public function create(ActionLogDto $dto): ?ActionLog
    {
        return $this->actionLogRepository->create($dto);
    }

    public function getList(int $userId)
    {
        return $this->actionLogRepository->getList($userId);
    }

    public function getNotActiveList(int $days): Collection
    {
        return $this->actionLogRepository->getNotActiveList($days);
    }
}
