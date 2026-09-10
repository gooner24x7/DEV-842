<?php
declare(strict_types=1);

namespace App\DataProvider;

use App\Dto\SearchParamsDto;
use App\Dto\User\UserDto;
use App\Models\Stripe\Subscription;
use App\Models\User;
use App\Repository\UserRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Redis;
use RedisException;

class UserDataProvider extends BaseDataProvider
{
    const string USERS_FIND_CACHE = 'users_find_%d_%d_%s_%s';
    const int USERS_FIND_CACHE_TIMEOUT = 3600 * 24;

    const string USER_SUBSCRIPTION_CACHE = 'user_subscription_%d';
    const int USER_SUBSCRIPTION_CACHE_TIMEOUT = 3600;

    const string USER_SEARCH_BY_ROLE_CACHE = 'user_search_by_role_%d_%s';
    const int USER_SEARCH_BY_ROLE_CACHE_TIMEOUT = 3600 * 24;

    const string USER_BY_ID_CACHE = 'user_by_id_%d';
    const int USER_BY_ID_CACHE_TIMEOUT = 3600 * 24;

    const string USER_BY_USERNAME_CACHE = 'user_by_username_%s';
    const int USER_BY_USERNAME_CACHE_TIMEOUT = 3600 * 24;

    const string USER_BY_EMAIL = 'user_by_email_%s';
    const int USER_BY_EMAIL_CACHE_TIMEOUT = 3600 * 24;

    private UserRepository $userRepository;

    public function __construct(UserRepository $userRepository, Redis $redis)
    {
        parent::__construct($redis);

        $this->userRepository = $userRepository;
    }

    /**
     * @throws RedisException
     */
    public function find(SearchParamsDto $searchParamsDto, ?int $billingUserId = null, $branches = false, $roleId = '', $useCache = true): LengthAwarePaginator
    {
        $key = sprintf(self::USERS_FIND_CACHE, $billingUserId, $branches ? 1 : 0, $roleId ?? 0, $searchParamsDto->getHash());

        $result = $this->redis->get($key);
        if ($useCache && $result) {
            return unserialize($result);
        }

        $result = $this->userRepository->find($searchParamsDto, $billingUserId, $roleId, $branches);

        $this->redis->set($key, serialize($result), self::USERS_FIND_CACHE_TIMEOUT);

        return $result;
    }

    /**
     * @throws RedisException
     */
    public function delete(int $id): bool
    {
        if ($this->userRepository->delete($id)) {
            $this->resetFindCache();

            $key = sprintf(self::USER_BY_ID_CACHE, $id);
            $this->redis->del($key);

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
            'users_find_*',
            'user_search_by_role_*',
        ];

        $this->resetCachePatterns($patterns);
    }

    /**
     * @throws RedisException
     */
    public function getUserStripeSubscription(int $userId, bool $useCache = true): ?Subscription
    {
        $key = sprintf(self::USER_SUBSCRIPTION_CACHE, $userId);

        $result = $this->redis->get($key);
        if ($useCache && $result) {
            return unserialize($result);
        }

        $result = $this->userRepository->getUserStripeSubscription($userId);

        $this->redis->set($key, serialize($result), self::USER_SUBSCRIPTION_CACHE_TIMEOUT);

        return $result;
    }

    /**
     * @throws RedisException
     */
    public function searchUserByRole(int $roleId, string $search, array $productIds = [], bool $useCache = true): Collection
    {
        $key = sprintf(self::USER_SEARCH_BY_ROLE_CACHE, $roleId, $search);

        $result = $this->redis->get($key);
        if ($useCache && $result) {
            return unserialize($result);
        }

        $result = $this->userRepository->searchUserByRole($roleId, $search, $productIds);

        $this->redis->set($key, serialize($result), self::USER_SEARCH_BY_ROLE_CACHE_TIMEOUT);

        return $result;
    }

    /**
     * @throws RedisException
     */
    public function update(int $userId, UserDto $userDto): ?User
    {
        $key = sprintf(self::USER_BY_ID_CACHE, $userId);

        $user = $this->userRepository->update($userId, $userDto);
        if ($user) {
            $this->resetFindCache();

            $this->redis->set($key, serialize($user), self::USER_BY_ID_CACHE_TIMEOUT);

            return $user;
        }

        return null;
    }

    /**
     * @throws RedisException
     */
    public function store(UserDto $userDto): ?User
    {
        $user = $this->userRepository->store($userDto);
        if ($user) {
            $this->resetFindCache();

            $key = sprintf(self::USER_BY_ID_CACHE, $user->getId());
            $this->redis->set($key, serialize($user), self::USER_BY_ID_CACHE_TIMEOUT);

            return $user;
        }

        return null;
    }

    public function storeRolesForUser(int $userId, array $roleIds): array
    {
        return $this->userRepository->storeRolesForUser($userId, $roleIds);
    }

    public function storeProductsForUser(int $userId, array $productIds): array
    {
        return $this->userRepository->storeProductsForUser($userId, $productIds);
    }

    /**
     * @throws RedisException
     */
    public function getByUsername(string $username, bool $useCache = true): ?User
    {
        $key = sprintf(self::USER_BY_USERNAME_CACHE, $username);

        $result = $this->redis->get($key);
        if ($useCache && $result) {
            return unserialize($result);
        }

        $response = $this->userRepository->getByUsername($username);

        $this->redis->set($key, serialize($response), self::USER_BY_USERNAME_CACHE_TIMEOUT);

        return $response;
    }

    /**
     * @throws RedisException
     */
    public function getByEmail(string $email, bool $useCache = true): ?User
    {
        $key = sprintf(self::USER_BY_EMAIL, $email);

        $result = $this->redis->get($key);
        if ($useCache && $result) {
            return unserialize($result);
        }

        $response = $this->userRepository->getByEmail($email);

        $this->redis->set($key, serialize($response), self::USER_BY_EMAIL_CACHE_TIMEOUT);

        return $response;
    }

    /**
     * @throws RedisException
     */
    public function touch(int $userId): void
    {
        $user = $this->getById($userId, false);
        $user->touch();

        $keys = [
            sprintf(self::USER_SUBSCRIPTION_CACHE, $user->getId()),
            sprintf(self::USER_BY_ID_CACHE, $user->getId()),
            sprintf(self::USER_BY_USERNAME_CACHE, $user->getUsername())
        ];

        $this->redis->del($keys);

        $this->resetFindCache();
    }

    /**
     * @throws RedisException
     */
    public function getById(int $id, bool $useCache = true): ?User
    {
        $key = sprintf(self::USER_BY_ID_CACHE, $id);

        $result = $this->redis->get($key);
        if ($useCache && $result) {
            return unserialize($result);
        }

        $result = $this->userRepository->getUserById($id);

        $this->redis->set($key, serialize($result), self::USER_BY_ID_CACHE_TIMEOUT);

        return $result;
    }

    public function getContractorStats($searchParams): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return $this->userRepository->getContractorStats($searchParams);
    }

    public function getMerchantStats($searchParams): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return $this->userRepository->getMerchantStats($searchParams);
    }

    public function getManufacturerStats($searchParams): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return $this->userRepository->getManufacturerStats($searchParams);
    }

    public function getCalledUsers(SearchParamsDto $searchParamsDto, ?int $merchantId): LengthAwarePaginator
    {
        return $this->userRepository->getCalledUsers($searchParamsDto, $merchantId);
    }
}
