<?php

declare(strict_types=1);

namespace App\DataProvider;

use App\Dto\VirtualExpo\SearchParamsDto;
use App\Dto\VirtualExpo\VirtualExpoDto;
use App\Exceptions\NotFoundException;
use App\Models\User;
use App\Models\VirtualExpo;
use App\Repository\VirtualExpoRepository;
use Exception;
use Illuminate\Pagination\LengthAwarePaginator;
use Psr\SimpleCache\InvalidArgumentException;
use Redis;
use RedisException;

class VirtualExpoDataProvider extends BaseDataProvider
{
    const string VIRTUAL_EXPO_BY_ID = 'virtual_expo_id_%d';
    const string VIRTUAL_EXPO_FIND_CACHE = 'virtual_expo_find_%s_%s';
    const string VIRTUAL_EXPO_BY_PRODUCT_FIND_CACHE = 'virtual_expo_find_%s_%d_%d';

    const string VIRTUAL_EXPO_FIND_VIDEOS_CACHE = 'virtual_expo_find_videos_%d_%s';

    const int VIRTUAL_EXPO_FIND_CACHE_TIMEOUT = 3600;
    const int VIRTUAL_EXPO_BY_ID_CACHE_TIMEOUT = 3600;

    private VirtualExpoRepository $virtualExpoRepository;

    public function __construct(VirtualExpoRepository $virtualExpoRepository, Redis $redis)
    {
        parent::__construct($redis);

        $this->virtualExpoRepository = $virtualExpoRepository;
    }

    /**
     * @throws RedisException
     */
    public function findVideos(SearchParamsDto $searchParamsDto, int $virtualExpoId, User $user, bool $useCache = true): LengthAwarePaginator
    {
        $key = sprintf(self::VIRTUAL_EXPO_FIND_VIDEOS_CACHE, $virtualExpoId, $searchParamsDto->getHash());

        $result = $this->redis->get($key);
        if ($useCache && $result) {
            return unserialize($result);
        }

        $result = $this->virtualExpoRepository->findVideos($searchParamsDto, $virtualExpoId, $user);

        $this->redis->set($key, serialize($result), self::VIRTUAL_EXPO_FIND_CACHE_TIMEOUT);

        return $result;
    }

    /**
     * @throws RedisException
     * @throws Exception
     */
    public function list(SearchParamsDto $searchParamsDto, User $user, bool $matchingProducts, bool $onlyGold, bool $useCache = true): LengthAwarePaginator
    {
        $key = sprintf(self::VIRTUAL_EXPO_BY_PRODUCT_FIND_CACHE, $searchParamsDto->getHash(), $matchingProducts ? 1 : 0, $onlyGold ? 1 : 0);

        $result = $this->redis->get($key);
        if ($useCache && $result) {
            return unserialize($result);
        }

        $result = $this->virtualExpoRepository->list($searchParamsDto, $user, $matchingProducts, $onlyGold);

        $this->redis->set($key, serialize($result), self::VIRTUAL_EXPO_FIND_CACHE_TIMEOUT);

        return $result;
    }

    /**
     * @throws RedisException
     */
    public function find(SearchParamsDto $searchParamsDto, User $user, array $productIds, bool $useCache = true): LengthAwarePaginator
    {
        $key = sprintf(self::VIRTUAL_EXPO_FIND_CACHE, $searchParamsDto->getHash(), join('', $productIds));

        $result = $this->redis->get($key);
        if ($useCache && $result) {
            return unserialize($result);
        }

        $result = $this->virtualExpoRepository->find($searchParamsDto, $user, $productIds);

        $this->redis->set($key, serialize($result), self::VIRTUAL_EXPO_FIND_CACHE_TIMEOUT);

        return $result;
    }

    public function getLastByManufacturerId(int $manufacturerId): ?VirtualExpo
    {
        return $this->virtualExpoRepository->getLastByManufacturerId($manufacturerId);
    }

    /**
     * @throws RedisException
     */
    public function getById(int $id, bool $useCache = true): ?VirtualExpo
    {
        $key = sprintf(self::VIRTUAL_EXPO_BY_ID, $id);

        $result = $this->redis->get($key);
        if ($useCache && $result) {
            return unserialize($result);
        }

        $result = $this->virtualExpoRepository->get($id);

        $this->redis->set($key, serialize($result), self::VIRTUAL_EXPO_BY_ID_CACHE_TIMEOUT);

        return $result;
    }

    /**
     * @param int $id
     * @return bool
     * @throws NotFoundException
     * @throws InvalidArgumentException|RedisException
     */
    public function delete(int $id): bool
    {
        $this->redis->del(sprintf(self::VIRTUAL_EXPO_BY_ID, $id));

        if ($this->virtualExpoRepository->delete($id)) {
            $this->resetFindCache();

            return true;
        }

        return false;
    }

    /**
     * @throws RedisException
     */
    private function resetFindCache(): void
    {
        $patterns = [
            sprintf(self::VIRTUAL_EXPO_FIND_CACHE, '*', '*')
        ];

        $this->resetCachePatterns($patterns);
    }

    /**
     * @throws RedisException
     */
    public function create(VirtualExpoDto $dto): ?VirtualExpo
    {
        $virtualExpo = $this->virtualExpoRepository->create($dto);
        if ($virtualExpo) {
            $this->redis->set(sprintf(self::VIRTUAL_EXPO_BY_ID, $virtualExpo->getId()), serialize($virtualExpo), self::VIRTUAL_EXPO_BY_ID_CACHE_TIMEOUT);
        }

        $this->resetFindCache();

        return $virtualExpo;
    }

    /**
     * @throws RedisException
     * @throws NotFoundException
     */
    public function update(int $id, VirtualExpoDto $dto): ?VirtualExpo
    {
        $virtualExpo = $this->virtualExpoRepository->update($id, $dto);
        if ($virtualExpo) {
            $this->redis->set(sprintf(self::VIRTUAL_EXPO_BY_ID, $virtualExpo->getId()), serialize($virtualExpo), self::VIRTUAL_EXPO_BY_ID_CACHE_TIMEOUT);
        }

        $this->resetFindCache();

        return $virtualExpo;
    }
}
