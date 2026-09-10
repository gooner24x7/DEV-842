<?php
declare(strict_types=1);

namespace App\Repository;

use App\Dto\SearchParamsDto;
use App\Exceptions\NotFoundException;
use App\Models\Role;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PreferredSubcontractorRepository
{
    const string DEFAULT_ORDER_FIELD_NAME = 'id';
    const int ITEMS_PER_PAGE = 20;

    /**
     * @param SearchParamsDto $searchParamsDto
     * @param int $userId
     * @return LengthAwarePaginator
     * @throws NotFoundException
     */
    public function find(SearchParamsDto $searchParamsDto, int $userId): LengthAwarePaginator
    {
        $user = User::where(['id' => $userId])->first();
        if (!$user) {
            throw new NotFoundException();
        }

        $query = $user->preferredSubcontractors()
            ->select(
                'u.id',
                'u.first_name',
                'u.username'
            )
            ->leftJoin('users as u', 'u.id', '=', 'users_preferred_subcontractors.subcontractor_id')
            ->orderBy(
                'u.' . ($searchParamsDto->getOrderBy() ?? self::DEFAULT_ORDER_FIELD_NAME),
                $searchParamsDto->getOrderSortDesc() ? 'desc' : 'asc'
            );

        $itemsPerPage = $searchParamsDto->getItemsPerPage() ?? self::ITEMS_PER_PAGE;
        if (!$searchParamsDto->isPaginationEnabled()) {
            $itemsPerPage = PHP_INT_MAX;
        }

        return $query->paginate($itemsPerPage);
    }

    public function store(User $user, int $subcontractorId): bool
    {
        $subcontractor = $user->preferredSubcontractors()->wherePivot('subcontractor_id', '=', $subcontractorId)->first();
        if ($subcontractor) {
            return false;
        }

        $user->preferredSubcontractors()->attach($subcontractorId);

        return true;
    }

    public function delete(User $user, int $subcontractorId): bool
    {
        $subcontractor = $user->preferredSubcontractors()->wherePivot('subcontractor_id', '=', $subcontractorId)->first();
        if (!$subcontractor) {
            return false;
        }

        $user->preferredSubcontractors()->detach($subcontractorId);

        return true;
    }

    public function getOptions($search = null): Collection
    {
        $query = DB::table('users')
            ->select('users.id', 'users.first_name', 'users.last_name')
            ->join('users_roles', 'users_roles.user_id', '=', 'users.id')
            ->join('roles', 'users_roles.role_id', '=', 'roles.id')
            ->where('roles.slug', '=', Role::ROLE_USER_SLUG);

        if (!empty($search)) {
            $query->where('users.first_name', 'like', "%$search%");
            $query->orWhere('users.last_name', 'like', "%$search%");
        }

        return $query->get();
    }
}
