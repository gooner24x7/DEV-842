<?php

declare(strict_types=1);

namespace App\DataProvider;

use App\Dto\Logistics\LogisticsEnquiryDto;
use App\Dto\Logistics\SearchParamsDto;
use App\Models\LogisticsEnquiry;
use App\Models\User;
use App\Repository\LogisticsQuoteRepository;
use App\Repository\LogisticsEnquiryRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use RedisException;

class LogisticsEnquiryDataProvider extends BaseDataProvider
{
    public const string ENQUIRY_BY_ID_CACHE = 'enquiry_by_id_%d';
    public const int ENQUIRY_BY_ID_CACHE_TIMEOUT = 3600;

    public const string ENQUIRY_FIND_CACHE = 'enquiry_find_%d';
    public const int ENQUIRY_FIND_CACHE_TIMEOUT = 3600;

    public const string ENQUIRY_WORKS_PACKAGE_OPTIONS = 'enquiry_works_package_opts_%s_%d_%d';
    public const int ENQUIRY_WORKS_PACKAGE_OPTIONS_TIMEOUT = 3600;

    public const string ENQUIRY_PROJECT_NAME_OPTIONS = 'enquiry_project_name_opts_%s_%d_%d';
    public const int ENQUIRY_PROJECT_NAME_OPTIONS_TIMEOUT = 3600;

    public const string ENQUIRY_EMAIL_CACHE = 'enquiry_email_%d';
    public const int ENQUIRY_EMAIL_CACHE_TIMEOUT = 3600;

    private LogisticsEnquiryRepository $enquiryRepository;
    private LogisticsQuoteRepository $quoteRepository;

    public function __construct(LogisticsEnquiryRepository $enquiryRepository, LogisticsQuoteRepository $quoteRepository, \Redis $redis)
    {
        parent::__construct($redis);

        $this->enquiryRepository = $enquiryRepository;
        $this->quoteRepository = $quoteRepository;
    }

    /**
     * @throws RedisException
     */
    public function get(int $id, bool $useCache = true): ?LogisticsEnquiry
    {
        $key = sprintf(self::ENQUIRY_BY_ID_CACHE, $id);

        $result = $this->redis->get($key);
        if ($useCache && $result) {
            return unserialize($result);
        }

        $response = $this->enquiryRepository->get($id);
        $this->redis->set($key, serialize($response), self::ENQUIRY_BY_ID_CACHE_TIMEOUT);

        return $response;
    }

    /**
     * @throws RedisException
     */
    public function find(SearchParamsDto $searchParamsDto, User $user, bool $useCache = true): LengthAwarePaginator
    {
        $key = sprintf(self::ENQUIRY_FIND_CACHE, $user->getId());
        $result = $this->redis->get($key);

        if ($useCache && $result) {
            return unserialize($result);
        }

        $response = $this->enquiryRepository->find($searchParamsDto, $user);

        $this->redis->set($key, serialize($response), self::ENQUIRY_FIND_CACHE_TIMEOUT);

        return $response;
    }

    /**
     * @throws RedisException
     */
    public function store(LogisticsEnquiryDto $enquiryDto): ?LogisticsEnquiry
    {
        $enquiry = $this->enquiryRepository->store($enquiryDto);
        if ($enquiry) {
            $key = sprintf(self::ENQUIRY_BY_ID_CACHE, $enquiry->getId());

            $this->redis->set($key, serialize($enquiry), self::ENQUIRY_BY_ID_CACHE_TIMEOUT);

            $this->resetCache();

            return $enquiry;
        }

        return null;
    }

    /**
     * @throws RedisException
     */
    public function update(LogisticsEnquiryDto $enquiryDto, int $id): ?LogisticsEnquiry
    {
        $enquiry = $this->enquiryRepository->update($enquiryDto, $id);
        if ($enquiry) {
            $key = sprintf(self::ENQUIRY_BY_ID_CACHE, $enquiry->getId());

            $this->redis->set($key, serialize($enquiry), self::ENQUIRY_BY_ID_CACHE_TIMEOUT);

            $this->resetCache();

            return $enquiry;
        }

        return null;
    }

    /**
     * @throws RedisException
     */
    public function delete(int $enquiryId): bool
    {
        $key = sprintf(self::ENQUIRY_BY_ID_CACHE, $enquiryId);

        if ($this->quoteRepository->getQuotesCount($enquiryId) > 0) {
            return false;
        }

        if ($this->enquiryRepository->delete($enquiryId)) {
            $this->redis->del($key);

            $this->resetCache();

            return true;
        }

        return false;
    }

    /**
     * @throws RedisException
     */
    public function archive(LogisticsEnquiry $enquiry): ?LogisticsEnquiry
    {
        $key = sprintf(self::ENQUIRY_BY_ID_CACHE, $enquiry->getId());

        $enquiry = $this->enquiryRepository->archive($enquiry);
        $this->redis->set($key, serialize($enquiry), self::ENQUIRY_BY_ID_CACHE_TIMEOUT);

        $this->resetCache();

        return $enquiry;
    }

    /**
     * @throws RedisException
     */
    public function restore(LogisticsEnquiry $enquiry): ?LogisticsEnquiry
    {
        $key = sprintf(self::ENQUIRY_BY_ID_CACHE, $enquiry->getId());

        $enquiry = $this->enquiryRepository->restore($enquiry);
        $this->redis->set($key, serialize($enquiry), self::ENQUIRY_BY_ID_CACHE_TIMEOUT);

        $this->resetCache();

        return $enquiry;
    }

    /**
     * @throws RedisException
     */
    public function duplicate(int $id, LogisticsEnquiryDto $dto): ?LogisticsEnquiry
    {
        $question = $this->enquiryRepository->duplicate($id, $dto);

        $this->resetCache();

        return $question;
    }

    /**
     * @throws RedisException
     */
    public function toggleIgnore(LogisticsEnquiry $enquiry, User $user): void
    {
        $this->resetCache();

        $this->enquiryRepository->toggleIgnore($enquiry, $user);
    }

    /**
     * @throws RedisException
     */
    private function resetCache(): void
    {
        $this->resetCachePatterns([
            'enquiry_find_*',
            'enquiry_works_package_opts_*',
        ]);
    }

    public function getProjectOptions(string $search, User $user, bool $archived = false, bool $useCache = false): Collection
    {
        $key = sprintf(self::ENQUIRY_PROJECT_NAME_OPTIONS, $search, $user->getId(), $archived ? 1 : 0);
        $result = $this->redis->get($key);
        if ($useCache && $result) {
            return unserialize($result);
        }

        $response = $this->enquiryRepository->getProjectOptions($search, $user, $archived);

        $this->redis->set($key, serialize($response), self::ENQUIRY_PROJECT_NAME_OPTIONS_TIMEOUT);

        return $response;
    }

    /**
     * @throws RedisException
     */
    public function getWorksPackageOptions(string $search, User $user, bool $archived = false, bool $useCache = false, array $projectIds = []): Collection
    {
        $key = sprintf(self::ENQUIRY_WORKS_PACKAGE_OPTIONS, $search, $user->getId(), $archived ? 1 : 0);

        $result = $this->redis->get($key);
        if ($useCache && $result) {
            return unserialize($result);
        }

        $response = $this->enquiryRepository->getWorksPackageOptions($search, $user, $archived, $projectIds);

        $this->redis->set($key, serialize($response), self::ENQUIRY_WORKS_PACKAGE_OPTIONS_TIMEOUT);

        return $response;
    }
}
