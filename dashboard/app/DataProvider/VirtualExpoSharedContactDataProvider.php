<?php

declare(strict_types=1);

namespace App\DataProvider;

use App\Dto\VirtualExpo\SharedContactSearchParamsDto;
use App\Dto\VirtualExpo\VirtualExpoSharedContactDto;
use App\Exceptions\NotFoundException;
use App\Models\User;
use App\Models\VirtualExpoSharedContact;
use App\Repository\VirtualExpoSharedContactRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use Psr\SimpleCache\InvalidArgumentException;
use Redis;
use RedisException;

class VirtualExpoSharedContactDataProvider extends BaseDataProvider
{
    const string VIRTUAL_EXPO_SHARED_CONTACT_BY_ID = 'virtual_expo_shared_contact_id_%d';
    const string VIRTUAL_EXPO_SHARED_CONTACT_FIND_CACHE = 'virtual_expo_shared_contact_find_%s';
    const string VIRTUAL_EXPO_SHARED_CONTACT_BY_USER_ID_EXPO_ID = 'virtual_expo_shared_contact_user_id_expo_id_%d';
    const int VIRTUAL_EXPO_SHARED_CONTACT_FIND_CACHE_TIMEOUT = 3600;
    const int VIRTUAL_EXPO_SHARED_CONTACT_BY_ID_CACHE_TIMEOUT = 3600;
    const int VIRTUAL_EXPO_SHARED_CONTACT_BY_USER_ID_EXPO_ID_TIMEOUT = 3600;

    private VirtualExpoSharedContactRepository $virtualExpoSharedContactRepository;

    public function __construct(
        VirtualExpoSharedContactRepository $virtualExpoSharedContactRepository,
        Redis                              $redis
    )
    {
        parent::__construct($redis);

        $this->virtualExpoSharedContactRepository = $virtualExpoSharedContactRepository;
    }

    /**
     * @throws RedisException
     */
    public function find(SharedContactSearchParamsDto $searchParamsDto, User $user, bool $useCache = true): LengthAwarePaginator
    {
        $key = sprintf(self::VIRTUAL_EXPO_SHARED_CONTACT_FIND_CACHE, $searchParamsDto->getHash());

        $result = $this->redis->get($key);
        if ($useCache && $result) {
            return unserialize($result);
        }

        $result = $this->virtualExpoSharedContactRepository->find($searchParamsDto, $user);

        $this->redis->set($key, serialize($result), self::VIRTUAL_EXPO_SHARED_CONTACT_FIND_CACHE_TIMEOUT);

        return $result;
    }

    /**
     * @throws RedisException
     */
    public function findByUserExpoId(int $userId, int $virtualExpoId, bool $useCache = true): ?VirtualExpoSharedContact
    {
        $key = sprintf(self::VIRTUAL_EXPO_SHARED_CONTACT_BY_USER_ID_EXPO_ID, $userId, $virtualExpoId);

        $result = $this->redis->get($key);
        if ($useCache && $result) {
            return unserialize($result);
        }

        $result = $this->virtualExpoSharedContactRepository->findByUserExpoId($userId, $virtualExpoId);

        $this->redis->set($key, serialize($result), self::VIRTUAL_EXPO_SHARED_CONTACT_BY_USER_ID_EXPO_ID_TIMEOUT);

        return $result;
    }

    /**
     * @throws RedisException
     */
    public function getById(int $id, bool $useCache = true): ?VirtualExpoSharedContact
    {
        $key = sprintf(self::VIRTUAL_EXPO_SHARED_CONTACT_BY_ID, $id);

        $result = $this->redis->get($key);
        if ($useCache && $result) {
            return unserialize($result);
        }

        $result = $this->virtualExpoSharedContactRepository->get($id);

        $this->redis->set($key, serialize($result), self::VIRTUAL_EXPO_SHARED_CONTACT_BY_ID_CACHE_TIMEOUT);

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
        $this->redis->del(sprintf(self::VIRTUAL_EXPO_SHARED_CONTACT_BY_ID, $id));

        if ($this->virtualExpoSharedContactRepository->delete($id)) {
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
            sprintf(self::VIRTUAL_EXPO_SHARED_CONTACT_FIND_CACHE, '*')
        ];

        $this->resetCachePatterns($patterns);
    }

    /**
     * @throws RedisException
     */
    public function create(VirtualExpoSharedContactDto $dto): ?VirtualExpoSharedContact
    {
        $virtualExpoSharedContact = $this->virtualExpoSharedContactRepository->create($dto);
        if ($virtualExpoSharedContact) {
            $this->redis->set(
                sprintf(self::VIRTUAL_EXPO_SHARED_CONTACT_BY_ID, $virtualExpoSharedContact->getId()),
                serialize($virtualExpoSharedContact),
                self::VIRTUAL_EXPO_SHARED_CONTACT_BY_ID_CACHE_TIMEOUT
            );
        }

        $this->resetFindCache();

        return $virtualExpoSharedContact;
    }
}
