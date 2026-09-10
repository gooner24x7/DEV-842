<?php

declare(strict_types=1);

namespace App\DataProvider;

use App\Dto\SearchParamsDto;
use App\Dto\Tutorial\TutorialDto;
use App\Exceptions\NotFoundException;
use App\Models\Tutorial;
use App\Repository\TutorialRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use Psr\SimpleCache\InvalidArgumentException;
use Redis;
use RedisException;

class TutorialDataProvider extends BaseDataProvider
{
    const string TUTORIAL_BY_ID = 'tutorial_id_%d';
    const string TUTORIAL_FIND_CACHE = 'tutorial_find_%s';
    const int TUTORIAL_FIND_CACHE_TIMEOUT = 3600;
    const int TUTORIAL_BY_ID_CACHE_TIMEOUT = 3600;
    private TutorialRepository $tutorialRepository;

    public function __construct(TutorialRepository $tutorialRepository, Redis $redis)
    {
        parent::__construct($redis);

        $this->tutorialRepository = $tutorialRepository;
    }

    /**
     * @throws RedisException
     */
    public function find(SearchParamsDto $searchParamsDto, $useCache = true): LengthAwarePaginator
    {
        $key = sprintf(self::TUTORIAL_FIND_CACHE, $searchParamsDto->getHash());

        $result = $this->redis->get($key);
        if ($useCache && $result) {
            return unserialize($result);
        }

        $result = $this->tutorialRepository->find($searchParamsDto);

        $this->redis->set($key, serialize($result), self::TUTORIAL_FIND_CACHE_TIMEOUT);

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
        $this->redis->del(sprintf(self::TUTORIAL_BY_ID, $id));

        if ($this->tutorialRepository->delete($id)) {
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
            sprintf(self::TUTORIAL_FIND_CACHE, '*')
        ];

        $this->resetCachePatterns($patterns);
    }

    /**
     * @throws RedisException
     */
    public function create(TutorialDto $dto): ?Tutorial
    {
        $tutorial = $this->tutorialRepository->create($dto);
        if ($tutorial) {
            $this->redis->set(
                sprintf(
                    self::TUTORIAL_BY_ID,
                    $tutorial->getId()
                ),
                serialize($tutorial),
                self::TUTORIAL_BY_ID_CACHE_TIMEOUT
            );
        }

        $this->resetFindCache();

        return $tutorial;
    }

    /**
     * @throws NotFoundException
     * @throws RedisException
     */
    public function update(int $id, TutorialDto $dto): ?Tutorial
    {
        $tutorial = $this->tutorialRepository->update($id, $dto);
        if ($tutorial) {
            $this->redis->set(
                sprintf(
                    self::TUTORIAL_BY_ID,
                    $tutorial->getId()
                ),
                serialize($tutorial),
                self::TUTORIAL_BY_ID_CACHE_TIMEOUT
            );
        }

        $this->resetFindCache();

        return $tutorial;
    }
}
