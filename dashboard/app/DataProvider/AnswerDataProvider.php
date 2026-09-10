<?php

declare(strict_types=1);

namespace App\DataProvider;

use App\Dto\Answer\AnswerDto;
use App\Dto\Answer\SearchParamsDto;
use App\Models\Answer;
use App\Models\Question;
use App\Models\User;
use App\Repository\AnswerRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use RedisException;

class AnswerDataProvider extends BaseDataProvider
{
    public const string ANSWER_FIND_CACHE = 'answer_find_%d_%d_%d_%s';
    public const int ANSWER_FIND_CACHE_TIMEOUT = 3600 * 24;
    public const string ANSWER_BY_ID_CACHE = 'answer_by_id_%d';
    public const int ANSWER_BY_ID_CACHE_TIMEOUT = 3600 * 24;
    private AnswerRepository $answerRepository;

    public function __construct(AnswerRepository $answerRepository, \Redis $redis)
    {
        parent::__construct($redis);

        $this->answerRepository = $answerRepository;
        $this->redis = $redis;
    }

    /**
     * @throws RedisException
     */
    public function find(SearchParamsDto $searchParamsDto, User $user, bool $isBranchUser, int $questionId, bool $useCache = true): LengthAwarePaginator
    {
        $key = sprintf(self::ANSWER_FIND_CACHE, $user->getId(), $questionId, $isBranchUser, $searchParamsDto->getHash());

        $result = $this->redis->get($key);
        if ($useCache && $result) {
            return unserialize($result);
        }

        $response = $this->answerRepository->find($searchParamsDto, $user, $isBranchUser, $questionId);

        $this->redis->set($key, serialize($response), self::ANSWER_FIND_CACHE_TIMEOUT);

        return $response;
    }

    /**
     * @throws RedisException
     */
    public function getById(int $id, bool $useCache = true): ?Answer
    {
        $key = sprintf(self::ANSWER_BY_ID_CACHE, $id);

        $result = $this->redis->get($key);
        if ($useCache && $result) {
            return unserialize($result);
        }

        $answer = $this->answerRepository->getById($id);

        $this->redis->set($key, serialize($answer), self::ANSWER_BY_ID_CACHE_TIMEOUT);

        return $answer;
    }

    /**
     * @throws RedisException
     */
    public function store(AnswerDto $answerDto, Question $question, User $user): ?Answer
    {
        $answer = $this->answerRepository->store($answerDto, $question, $user);
        if ($answer) {
            $key = sprintf(self::ANSWER_BY_ID_CACHE, $answer->getId());

            $this->redis->set($key, serialize($answer), self::ANSWER_BY_ID_CACHE_TIMEOUT);

            $this->resetCache();

            return $answer;
        }

        return null;
    }

    private function resetCache(): void
    {
        $this->resetCachePatterns([
            'answer_find_*',
        ]);
    }

    public function isLastAnswerOlderThanADay(int $questionId, int $excludeId): bool
    {
        return $this->answerRepository->isLastAnswerOlderThanADay($questionId, $excludeId);
    }

    /**
     * @throws RedisException
     */
    public function storeSupplierInvoiceNo(string $supplierInvoiceNo, int $answerId): ?Answer
    {
        $answer = $this->answerRepository->storeSupplierInvoiceNo($supplierInvoiceNo, $answerId);
        if ($answer) {
            $key = sprintf(self::ANSWER_BY_ID_CACHE, $answer->getId());

            $this->redis->set($key, serialize($answer), self::ANSWER_BY_ID_CACHE_TIMEOUT);

            $this->resetCache();

            return $answer;
        }

        return null;
    }

    /**
     * @throws RedisException
     */
    public function storeLocalMaterialSpend(?float $localMaterialSpend, int $answerId): ?Answer
    {
        $answer = $this->answerRepository->storeLocalMaterialSpend($localMaterialSpend, $answerId);
        if ($answer) {
            $key = sprintf(self::ANSWER_BY_ID_CACHE, $answer->getId());

            $this->redis->set($key, serialize($answer), self::ANSWER_BY_ID_CACHE_TIMEOUT);

            $this->resetCache();

            return $answer;
        }

        return null;
    }

    /**
     * @throws RedisException
     */
    public function update(AnswerDto $answerDto, int $id): ?Answer
    {
        $answer = $this->answerRepository->update($answerDto, $id);
        if ($answer) {
            $key = sprintf(self::ANSWER_BY_ID_CACHE, $answer->getId());

            $this->redis->set($key, serialize($answer), self::ANSWER_BY_ID_CACHE_TIMEOUT);

            $this->resetCache();

            return $answer;
        }

        return null;
    }

    /**
     * @throws RedisException
     */
    public function delete(int $id): bool
    {
        $key = sprintf(self::ANSWER_BY_ID_CACHE, $id);

        if ($this->answerRepository->delete($id)) {
            $this->redis->del($key);

            $this->resetCache();

            return true;
        }

        return false;
    }

    /**
     * @throws RedisException
     */
    public function unaccept(int $id): bool
    {
        $key = sprintf(self::ANSWER_BY_ID_CACHE, $id);

        if ($this->answerRepository->unaccept($id)) {
            $this->redis->del($key);

            $this->resetCache();

            return true;
        }

        return false;
    }

    /**
     * @throws RedisException
     */
    public function accept(int $id): bool
    {
        $key = sprintf(self::ANSWER_BY_ID_CACHE, $id);

        if ($this->answerRepository->accept($id)) {
            $this->redis->del($key);

            $this->resetCache();

            return true;
        }

        return false;
    }

    /**
     * @throws RedisException
     */
    public function setCheckedAt(Answer $answer, bool $checked): bool
    {
        return $this->answerRepository->setCheckedAt($answer, $checked);
    }

    public function getQuotesTotalForContractor(User $user = null): int
    {
        return $this->answerRepository->getQuotesTotalForContractor($user);
    }

    public function getQuotesTotalForMerchant(User $user = null): int
    {
        return $this->answerRepository->getQuotesTotalForMerchant($user);
    }

    public function getQuotesAcceptedForContractor(User $user = null): int
    {
        return $this->answerRepository->getQuotesAcceptedForContractor($user);
    }

    public function getQuotesAcceptedForMerchant(User $user = null): int
    {
        return $this->answerRepository->getQuotesAcceptedForMerchant($user);
    }
}
