<?php
declare(strict_types=1);

namespace App\DataProvider;

use App\Dto\SearchParamsDto;
use App\Dto\UserNote\UserNoteDto;
use App\Models\User;
use App\Models\UserNote;
use App\Repository\UserNotesRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use Redis;
use RedisException;

class UserNotesDataProvider extends BaseDataProvider
{
    const string USER_NOTES_FIND_CACHE = 'user_notes_find_%d_%d_%s';
    const int USER_NOTES_FIND_CACHE_TIMEOUT = 3600 * 24;
    private UserNotesRepository $repository;

    public function __construct(UserNotesRepository $repository, Redis $redis)
    {
        parent::__construct($redis);

        $this->repository = $repository;
    }

    /**
     * @throws RedisException
     */
    public function find(SearchParamsDto $searchParamsDto, User $user, int $requestUserId, bool $useCache = true): LengthAwarePaginator
    {
        $key = sprintf(self::USER_NOTES_FIND_CACHE, $user->getId(), $requestUserId, $searchParamsDto->getHash());

        $result = $this->redis->get($key);
        if ($useCache && $result) {
            return unserialize($result);
        }

        $response = $this->repository->find($searchParamsDto, $user, $requestUserId);

        $this->redis->set($key, serialize($response), self::USER_NOTES_FIND_CACHE_TIMEOUT);

        return $response;
    }

    public function store(UserNoteDto $dto): ?UserNote
    {
        $userNote = $this->repository->store($dto);
        if ($userNote !== null) {
            $this->resetFindCache($dto->getUserId());
        }

        return $userNote;
    }

    /**
     * @throws RedisException
     */
    private function resetFindCache(int $userId): void
    {
        $this->resetCachePatterns([
            sprintf('user_notes_find_%d_%s', $userId, '*')
        ]);
    }
}
