<?php

declare(strict_types=1);

namespace App\DataProvider;

use App\Dto\Question\QuestionBatchDto;
use App\Dto\Question\QuestionDto;
use App\Dto\Question\SearchParamsDto;
use App\Models\National;
use App\Models\Question;
use App\Models\User;
use App\Repository\AnswerRepository;
use App\Repository\QuestionRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use RedisException;

class QuestionDataProvider extends BaseDataProvider
{
    public const string QUESTION_BY_ID_CACHE = 'question_by_id_%d';
    public const int QUESTION_BY_ID_CACHE_TIMEOUT = 3600;

    public const string QUESTION_FIND_FAV_CACHE = 'question_find_fav_%d_%s';
    public const string QUESTION_FIND_CACHE = 'question_find_%d_%d_%d_%s';
    public const int QUESTION_FIND_CACHE_TIMEOUT = 3600;

    public const string QUESTION_WORKS_PACKAGE_OPTIONS = 'question_works_package_opts_%s_%d_%d';
    public const int QUESTION_WORKS_PACKAGE_OPTIONS_TIMEOUT = 3600;

    public const string QUESTION_PROJECT_NAME_OPTIONS = 'question_project_name_opts_%s_%d_%d';
    public const int QUESTION_PROJECT_NAME_OPTIONS_TIMEOUT = 3600;

    public const string QUESTION_EMAIL_CACHE = 'question_email_%d';
    public const int QUESTION_EMAIL_CACHE_TIMEOUT = 3600;

    private QuestionRepository $questionRepository;
    private AnswerRepository $answerRepository;

    public function __construct(QuestionRepository $questionRepository, AnswerRepository $answerRepository, \Redis $redis)
    {
        parent::__construct($redis);

        $this->questionRepository = $questionRepository;
        $this->answerRepository = $answerRepository;
    }

    /**
     * @throws RedisException
     */
    public function findFav(SearchParamsDto $searchParamsDto, User $user, bool $useCache = true): LengthAwarePaginator
    {
        $key = sprintf(self::QUESTION_FIND_FAV_CACHE, $user->getId(), $searchParamsDto->getHash());

        $result = $this->redis->get($key);
        if ($useCache && $result) {
            return unserialize($result);
        }

        $response = $this->questionRepository->findFav($searchParamsDto, $user);

        $this->redis->set($key, serialize($response), self::QUESTION_FIND_CACHE_TIMEOUT);

        return $response;
    }

    /**
     * @throws RedisException
     */
    public function get(int $id, bool $useCache = true): ?Question
    {
        $key = sprintf(self::QUESTION_BY_ID_CACHE, $id);

        $result = $this->redis->get($key);
        if ($useCache && $result) {
            return unserialize($result);
        }

        $response = $this->questionRepository->get($id);
        $this->redis->set($key, serialize($response), self::QUESTION_BY_ID_CACHE_TIMEOUT);

        return $response;
    }

    public function findBySeenByMerchants(int $qid, SearchParamsDto $searchParamsDto): LengthAwarePaginator
    {
        return $this->questionRepository->findBySeenByMerchants($qid, $searchParamsDto);
    }

    public function findPreferredSuppliers(int $qid, SearchParamsDto $searchParamsDto): LengthAwarePaginator
    {
        return $this->questionRepository->findPreferredSuppliers($qid, $searchParamsDto);
    }

    public function toggleAssign(Question $question, User $user): void
    {
        $this->resetCache();

        $this->questionRepository->toggleAssign($question, $user);
    }

    /**
     * @throws RedisException
     */
    private function resetCache(): void
    {
        $this->resetCachePatterns([
            'question_find_*',
            'question_works_package_opts_*',
        ]);
    }

    /**
     * @throws RedisException
     */
    public function toggleIgnore(Question $question, User $user): void
    {
        $this->resetCache();

        $this->questionRepository->toggleIgnore($question, $user);
    }

    /**
     * @throws RedisException
     */
    public function setArchived(Question $question): ?Question
    {
        $key = sprintf(self::QUESTION_BY_ID_CACHE, $question->getId());

        $question = $this->questionRepository->setArchived($question);
        $this->redis->set($key, serialize($question), self::QUESTION_BY_ID_CACHE_TIMEOUT);

        $this->resetCache();

        return $question;
    }

    /**
     * @throws RedisException
     */
    public function duplicate(int $id, QuestionDto $dto): ?Question
    {
        $question = $this->questionRepository->duplicate($id, $dto);

        $this->resetCache();

        return $question;
    }

    /**
     * @throws RedisException
     */
    public function restore(Question $question): ?Question
    {
        $key = sprintf(self::QUESTION_BY_ID_CACHE, $question->getId());

        $question = $this->questionRepository->restore($question);
        $this->redis->set($key, serialize($question), self::QUESTION_BY_ID_CACHE_TIMEOUT);

        $this->resetCache();

        return $question;
    }

    public function storeBatch(QuestionBatchDto $questionBatchDto, array $nationalsItems): array
    {
        try {
            $questions = $this->questionRepository->storeBatch($questionBatchDto, $nationalsItems);

            $this->resetCache();

            return $questions;
        } catch (\Exception $e) {
            return ["error" => $e->getMessage()];
        }
    }

    /**
     * @throws RedisException
     */
    public function store(QuestionDto $questionDto): ?Question
    {
        $question = $this->questionRepository->store($questionDto);
        if ($question) {
            $key = sprintf(self::QUESTION_BY_ID_CACHE, $question->getId());

            $this->redis->set($key, serialize($question), self::QUESTION_BY_ID_CACHE_TIMEOUT);

            $this->resetCache();

            return $question;
        }

        return null;
    }

    /**
     * @throws RedisException
     */
    public function update(QuestionDto $questionDto, int $id): ?Question
    {
        $question = $this->questionRepository->update($questionDto, $id);
        if ($question) {
            $key = sprintf(self::QUESTION_BY_ID_CACHE, $question->getId());

            $this->redis->set($key, serialize($question), self::QUESTION_BY_ID_CACHE_TIMEOUT);

            $this->resetCache();

            return $question;
        }

        return null;
    }

    public function updateNationals($question, $request): void
    {
        $nationals_existing = $question->nationals()->get()->toArray();
        $nationals = $request->get('nationals');

        // remove existing nationals
        foreach ($nationals_existing as $national) {
            if (!in_array($national['id'], array_column($nationals, 'id'))) {
                National::find($national['id'])->delete();
            }
        }

        // add any new nationals
        foreach ($nationals as $national) {
            if (empty($national['id']) && !empty($national['email'])) {
                $national['question_id'] = $question->getId();

                National::create($national);
            } elseif (!empty($national['id']) && !empty($national['email'])) {
                //update
                $nationalObj = National::find($national['id']);
                $nationalObj->supplier_id = $national['supplier_id'];
                $nationalObj->contact_name = $national['contact_name'] ?? '';
                $nationalObj->account_number = $national['account_number'] ?? '';
                $nationalObj->email = $national['email'];
                $nationalObj->save();
            }
        }
    }

    /**
     * @throws RedisException
     */
    public function delete(int $questionId): bool
    {
        $key = sprintf(self::QUESTION_BY_ID_CACHE, $questionId);

        if ($this->answerRepository->getQuotesForInquiryCount($questionId) > 0) {
            return false;
        }

        if ($this->questionRepository->delete($questionId)) {
            $this->redis->del($key);

            $this->resetCache();

            return true;
        }

        return false;
    }

    /**
     * @throws RedisException
     */
    public function find(SearchParamsDto $searchParamsDto, User $user, User $currentUser, bool $onlyUnexpired, bool $useCache = true): LengthAwarePaginator
    {
        $key = sprintf(self::QUESTION_FIND_CACHE, $user->getId(), $currentUser->getId(), $onlyUnexpired ? 1 : 0, $searchParamsDto->getHash());

        $result = $this->redis->get($key);
        if ($useCache && $result) {
            return unserialize($result);
        }

        $response = $this->questionRepository->find($searchParamsDto, $user, $currentUser, $onlyUnexpired);

        $this->redis->set($key, serialize($response), self::QUESTION_FIND_CACHE_TIMEOUT);

        return $response;
    }

    public function getProjectOptions(string $search, User $user, bool $archived = false, bool $useCache = false): Collection
    {
        $key = sprintf(self::QUESTION_PROJECT_NAME_OPTIONS, $search, $user->getId(), $archived ? 1 : 0);
        $result = $this->redis->get($key);
        if ($useCache && $result) {
            return unserialize($result);
        }

        $response = $this->questionRepository->getProjectOptions($search, $user, $archived);

        $this->redis->set($key, serialize($response), self::QUESTION_PROJECT_NAME_OPTIONS_TIMEOUT);

        return $response;
    }

    /**
     * @throws RedisException
     */
    public function getWorksPackageOptions(string $search, User $user, bool $archived = false, bool $useCache = false, array $projectIds = []): Collection
    {
        $key = sprintf(self::QUESTION_WORKS_PACKAGE_OPTIONS, $search, $user->getId(), $archived ? 1 : 0);

        $result = $this->redis->get($key);
        if ($useCache && $result) {
            return unserialize($result);
        }

        $response = $this->questionRepository->getWorksPackageOptions($search, $user, $archived, $projectIds);

        $this->redis->set($key, serialize($response), self::QUESTION_WORKS_PACKAGE_OPTIONS_TIMEOUT);

        return $response;
    }

    public function getEmailForQuestion(int $questionId, bool $useCache = true): ?string
    {
        $key = sprintf(self::QUESTION_EMAIL_CACHE, $questionId);

        $result = $this->redis->get($key);
        if ($useCache && $result) {
            return unserialize($result);
        }

        $email = $this->questionRepository->getEmailForQuestion($questionId);

        $this->redis->set($key, serialize($email), self::QUESTION_EMAIL_CACHE_TIMEOUT);

        return $email;
    }

    public function getTotalEnquiriesSendAll(): int
    {
        return $this->questionRepository->getTotalEnquiriesSendAll();
    }

    public function getTotalEnquiriesSend(User $user): int
    {
        return $this->questionRepository->getTotalEnquiriesSend($user);
    }

    public function getMerchantsReceived(User $user = null): int
    {
        return $this->questionRepository->getMerchantsReceived($user);
    }

    public function getTotalEnquiries(User $user = null): int
    {
        return $this->questionRepository->getTotalEnquiries($user);
    }

    public function getMatchedEnquiries(User $user = null): int
    {
        return $this->questionRepository->getMatchedEnquiries($user);
    }

    public function categoriesPercentagesSelectedMerchant(User $user = null)
    {
        return $this->questionRepository->categoriesPercentagesSelectedMerchant($user);
    }

    public function categoriesPercentagesSelectedContractor(User $user = null)
    {
        return $this->questionRepository->categoriesPercentagesSelectedContractor($user);
    }

    public function countByProductId(int $productId): int
    {
        return $this->questionRepository->countByProductId($productId);
    }

    public function getPreferredSuppliers(int $questionId): Collection
    {
        return $this->questionRepository->getPreferredSuppliers($questionId);
    }
}
