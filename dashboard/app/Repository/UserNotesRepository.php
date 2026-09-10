<?php
declare(strict_types=1);

namespace App\Repository;

use App\Dto\SearchParamsDto;
use App\Dto\UserNote\UserNoteDto;
use App\Models\Role;
use App\Models\User;
use App\Models\UserNote;
use Illuminate\Pagination\LengthAwarePaginator;

class UserNotesRepository
{
    const string DEFAULT_ORDER_FIELD_NAME = 'id';
    const int ITEMS_PER_PAGE = 20;

    public function find(SearchParamsDto $searchParamsDto, User $user, int $requestUserId): LengthAwarePaginator
    {
        $query = UserNote::select(
            'user_notes.id',
            'user_notes.user_id',
            'users.username as author_id',
            'user_notes.message',
            'user_notes.created_at'
        )
            ->join('users', 'users.id', '=', 'user_notes.author_id')
            ->where([
                'user_id' => $requestUserId,
            ]);

        if (!$user->hasRole(Role::ROLE_ADMIN_SLUG)) {
            $query = $query->where(['user_notes.author_id' => $user->getId()]);
        }

        $query = $query->orderBy(
            ('user_notes.' . ($searchParamsDto->getOrderBy() ?? self::DEFAULT_ORDER_FIELD_NAME)),
            $searchParamsDto->getOrderSortDesc() ? 'desc' : 'asc'
        );

        $itemsPerPage = $searchParamsDto->getItemsPerPage() ?? self::ITEMS_PER_PAGE;
        if (!$searchParamsDto->isPaginationEnabled()) {
            $itemsPerPage = PHP_INT_MAX;
        }

        return $query->paginate($itemsPerPage);
    }

    public function store(UserNoteDto $dto): ?UserNote
    {
        if ($userNote = UserNote::create([
            'message' => $dto->getMessage(),
            'user_id' => $dto->getUserId(),
            'author_id' => $dto->getAuthorId(),
        ])) {
            return $userNote;
        }

        return null;
    }
}
