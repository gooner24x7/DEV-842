<?php
declare(strict_types=1);

namespace App\Repository;

use App\Dto\SearchParamsDto;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UserRoleRepository
{
    const string DEFAULT_ORDER_FIELD_NAME = 'id';
    const int ITEMS_PER_PAGE = 15;

    public function find(SearchParamsDto $searchParamsDto): LengthAwarePaginator
    {
        $query = Role::orderBy(
            $searchParamsDto->getOrderBy() ?? self::DEFAULT_ORDER_FIELD_NAME,
            $searchParamsDto->getOrderSortDesc() ? 'desc' : 'asc'
        );

        $itemsPerPage = $searchParamsDto->getItemsPerPage() ?? self::ITEMS_PER_PAGE;
        if (!$searchParamsDto->isPaginationEnabled()) {
            $itemsPerPage = PHP_INT_MAX;
        }

        return $query->paginate($itemsPerPage);
    }

    public function getBySlug(string $slug): ?Role
    {
        return Role::where(['slug' => $slug])->first();
    }

    public function getPermissions(int $roleId = null): Collection
    {
        $query = Permission::query()
            ->select('id', 'name', 'slug', 'category');

        if (!empty($roleId)) {
            $query->selectRaw(DB::raw("CASE WHEN EXISTS (
                SELECT 1 FROM roles_permissions WHERE roles_permissions.role_id = ? AND roles_permissions.permission_id = permissions.id
            ) THEN 1 ELSE 0
            END AS enabled"), [$roleId]);
        }

        $query->orderBy('id', 'asc');

        $results = $query->get();

        $results = $results->each(function ($item) {
            if (is_null($item->category)) {
                $item->category = "Other";
            }
        });

        return $results->groupBy('category');
    }

    public function updateRolePermissions(int $roleId, array $data): void
    {
        foreach($data as $permission) {
            if ($permission['enabled']) {
                DB::table('roles_permissions')->upsert(
                    ['role_id' => $roleId, 'permission_id' => $permission['id']],
                    ['role_id', 'permission_id'],
                    ['permission_id']
                );
            } else {
                DB::table('roles_permissions')->where([
                    'role_id' => $roleId,
                    'permission_id' => $permission['id']
                ])->delete();
            }
        }
    }
}
