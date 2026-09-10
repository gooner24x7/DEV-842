<?php
declare(strict_types=1);

namespace App\DataProvider;

use App\Dto\SearchParamsDto;
use App\Exceptions\NotFoundException;
use App\Models\User;
use App\Repository\PreferredSupplierRepository;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Redis;
use RedisException;

class PreferredSupplierDataProvider extends BaseDataProvider
{
    const string PREFERRED_SUPPLIER_FIND_CACHE = 'preferred_supplier_%d_%s';
    const int PREFERRED_SUPPLIER_FIND_CACHE_TIMEOUT = 3600 * 24;
    private PreferredSupplierRepository $preferredSupplierRepository;

    public function __construct(PreferredSupplierRepository $repository, Redis $redis)
    {
        parent::__construct($redis);

        $this->preferredSupplierRepository = $repository;
    }

    /**
     * @throws RedisException
     * @throws NotFoundException
     */
    public function find(SearchParamsDto $dto, int $userId, $useCache = true): LengthAwarePaginator
    {
        $key = sprintf(self::PREFERRED_SUPPLIER_FIND_CACHE, $userId, $dto->getHash());

        $result = $this->redis->get($key);
        if ($useCache && $result) {
            return unserialize($result);
        }

        $response = $this->preferredSupplierRepository->find($dto, $userId);

        $this->redis->set($key, serialize($response), self::PREFERRED_SUPPLIER_FIND_CACHE_TIMEOUT);

        return $response;
    }

    public function store(User $user, int $supplierId): bool
    {
        if ($this->preferredSupplierRepository->store($user, $supplierId)) {
            $this->resetFindCache($user->getId());

            return true;
        }

        return false;
    }

    /**
     * @throws RedisException
     */
    private function resetFindCache(int $userId): void
    {
        $this->resetCachePatterns([
            sprintf(self::PREFERRED_SUPPLIER_FIND_CACHE, $userId, '*')
        ]);
    }

    /**
     * @throws RedisException
     */
    public function delete(User $user, int $supplierId): bool
    {
        if ($this->preferredSupplierRepository->delete($user, $supplierId)) {
            $this->resetFindCache($user->getId());

            return true;
        }

        return false;
    }

    public function getOptions($search = null): Collection
    {
        return $this->preferredSupplierRepository->getOptions($search);
    }
}
