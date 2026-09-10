<?php
declare(strict_types=1);

namespace App\DataProvider;

use App\Dto\SearchParamsDto;
use App\Exceptions\NotFoundException;
use App\Models\User;
use App\Repository\PreferredSubcontractorRepository;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Redis;
use RedisException;

class PreferredSubcontractorDataProvider extends BaseDataProvider
{
    const string PREFERRED_SUBCONTRACTOR_FIND_CACHE = 'preferred_subcontractor_%d_%s';
    const int PREFERRED_SUBCONTRACTOR_FIND_CACHE_TIMEOUT = 3600 * 24;
    private PreferredSubcontractorRepository $preferredSubcontractorRepository;

    public function __construct(PreferredSubcontractorRepository $repository, Redis $redis)
    {
        parent::__construct($redis);

        $this->preferredSubcontractorRepository = $repository;
    }

    /**
     * @throws RedisException
     * @throws NotFoundException
     */
    public function find(SearchParamsDto $dto, int $userId, $useCache = true): LengthAwarePaginator
    {
        $key = sprintf(self::PREFERRED_SUBCONTRACTOR_FIND_CACHE, $userId, $dto->getHash());

        $result = $this->redis->get($key);
        if ($useCache && $result) {
            return unserialize($result);
        }

        $response = $this->preferredSubcontractorRepository->find($dto, $userId);

        $this->redis->set($key, serialize($response), self::PREFERRED_SUBCONTRACTOR_FIND_CACHE_TIMEOUT);

        return $response;
    }

    public function store(User $user, int $subcontractorId): bool
    {
        if ($this->preferredSubcontractorRepository->store($user, $subcontractorId)) {
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
            sprintf(self::PREFERRED_SUBCONTRACTOR_FIND_CACHE, $userId, '*')
        ]);
    }

    /**
     * @throws RedisException
     */
    public function delete(User $user, int $subcontractorId): bool
    {
        if ($this->preferredSubcontractorRepository->delete($user, $subcontractorId)) {
            $this->resetFindCache($user->getId());

            return true;
        }

        return false;
    }

    public function getOptions($search = null): Collection
    {
        return $this->preferredSubcontractorRepository->getOptions($search);
    }
}
