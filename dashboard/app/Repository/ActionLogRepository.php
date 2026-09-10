<?php
declare(strict_types=1);

namespace App\Repository;

use App\Dto\ActionLog\ActionLogDto;
use App\Models\ActionLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class ActionLogRepository
{
    public function create(ActionLogDto $dto): ?ActionLog
    {
        if ($actionLog = ActionLog::create([
            'action' => $dto->getAction(),
            'page' => $dto->getPage(),
            'comment' => $dto->getComment(),
            'user_id' => $dto->getUserId(),
        ])) {
            return $actionLog;
        }

        return null;
    }

    public function getList(int $userId)
    {
        return ActionLog::where(['user_id' => $userId])->get();
    }

    public function getNotActiveList(int $days): Collection
    {
        $query = User::query();
        $query->distinct();
        $query->leftJoin('action_logs', 'action_logs.user_id', '=', 'users.id');
        $query->whereBetween('action_logs.created_at', [Carbon::now()->subDays($days), Carbon::now()]);
        $usersActiveIds = $query->pluck('users.id');

        return User::query()->whereNotIn('id', $usersActiveIds)->get();
    }
}
