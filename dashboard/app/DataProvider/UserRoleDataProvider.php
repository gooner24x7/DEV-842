<?php

declare(strict_types=1);

namespace App\DataProvider;

use App\Dto\SearchParamsDto;
use App\Models\Role;
use App\Repository\UserRoleRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Redis;
use RedisException;

class UserRoleDataProvider extends BaseDataProvider
{
    const string USER_ROLES_FIND_CACHE = 'user_roles_find_%s';
    const int USER_ROLES_FIND_CACHE_TIMEOUT = 3600;

    const string USER_ROLE_BY_SLUG_CACHE = 'user_role_by_slug_%s';
    const int USER_ROLE_BY_SLUG_CACHE_TIMEOUT = 3600 * 24;
    private UserRoleRepository $userRoleRepository;

    public function __construct(UserRoleRepository $userRoleRepository, Redis $redis)
    {
        parent::__construct($redis);

        $this->userRoleRepository = $userRoleRepository;
    }

    /**
     * @throws RedisException
     */
    public function find(SearchParamsDto $searchParamsDto, bool $useCache = true): LengthAwarePaginator
    {
        $key = sprintf(self::USER_ROLES_FIND_CACHE, $searchParamsDto->getHash());

        $result = $this->redis->get($key);
        if ($useCache && $result) {
            return unserialize($result);
        }

        $result = $this->userRoleRepository->find($searchParamsDto);

        $this->redis->set($key, serialize($result), self::USER_ROLES_FIND_CACHE_TIMEOUT);

        return $result;
    }

    /**
     * @throws RedisException
     */
    public function getBySlug(string $slug, bool $useCache = true): ?Role
    {
        $key = sprintf(self::USER_ROLE_BY_SLUG_CACHE, $slug);

        $result = $this->redis->get($key);
        if ($useCache && $result) {
            return unserialize($result);
        }

        $response = $this->userRoleRepository->getBySlug($slug);
        $this->redis->set($key, serialize($response), self::USER_ROLE_BY_SLUG_CACHE_TIMEOUT);

        return $response;
    }

    public function getPermissions(int $roleId = null): Collection
    {
        return $this->userRoleRepository->getPermissions($roleId);
    }

    public function updateRolePermissions(int $roleId, array $data): void
    {
        $this->userRoleRepository->updateRolePermissions($roleId, $data);
    }
}
