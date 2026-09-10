<?php

declare(strict_types=1);

namespace App\DataProvider;

use App\Dto\Logistics\LogisticsQuoteDto;
use App\Dto\Logistics\SearchParamsDto;
use App\Models\LogisticsQuote;
use App\Models\LogisticsEnquiry;
use App\Models\User;
use App\Repository\LogisticsQuoteRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use RedisException;

class LogisticsQuoteDataProvider extends BaseDataProvider
{
    public const string QUOTE_FIND_CACHE = 'quote_find_%d_%d';
    public const int QUOTE_FIND_CACHE_TIMEOUT = 3600 * 24;
    public const string QUOTE_BY_ID_CACHE = 'quote_by_id_%d';
    public const int QUOTE_BY_ID_CACHE_TIMEOUT = 3600 * 24;

    private LogisticsQuoteRepository $quoteRepository;

    public function __construct(LogisticsQuoteRepository $quoteRepository, \Redis $redis)
    {
        parent::__construct($redis);

        $this->quoteRepository = $quoteRepository;
        $this->redis = $redis;
    }

    /**
     * @throws RedisException
     */
    public function getById(int $id, bool $useCache = true): ?LogisticsQuote
    {
        $key = sprintf(self::QUOTE_BY_ID_CACHE, $id);

        $result = $this->redis->get($key);
        if ($useCache && $result) {
            return unserialize($result);
        }

        $quote = $this->quoteRepository->getById($id);

        $this->redis->set($key, serialize($quote), self::QUOTE_BY_ID_CACHE_TIMEOUT);

        return $quote;
    }

    /**
     * @throws RedisException
     */
    public function find(SearchParamsDto $searchParamsDto, User $user, int $enquiryId, bool $useCache = true): LengthAwarePaginator
    {
        $key = sprintf(self::QUOTE_FIND_CACHE, $user->getId(), $enquiryId);
        $result = $this->redis->get($key);

        if ($useCache && $result) {
            return unserialize($result);
        }

        $response = $this->quoteRepository->find($searchParamsDto, $user, $enquiryId);

        $this->redis->set($key, serialize($response), self::QUOTE_FIND_CACHE_TIMEOUT);

        return $response;
    }

    /**
     * @throws RedisException
     */
    public function store(LogisticsQuoteDto $quoteDto, LogisticsEnquiry $enquiry, User $user): ?LogisticsQuote
    {
        $quote = $this->quoteRepository->store($quoteDto, $enquiry, $user);
        if ($quote) {
            $key = sprintf(self::QUOTE_BY_ID_CACHE, $quote->getId());

            $this->redis->set($key, serialize($quote), self::QUOTE_BY_ID_CACHE_TIMEOUT);

            $this->resetCache();

            return $quote;
        }

        return null;
    }

    /**
     * @throws RedisException
     */
    public function update(LogisticsQuoteDto $quoteDto, int $id): ?LogisticsQuote
    {
        $quote = $this->quoteRepository->update($quoteDto, $id);
        if ($quote) {
            $key = sprintf(self::QUOTE_BY_ID_CACHE, $quote->getId());

            $this->redis->set($key, serialize($quote), self::QUOTE_BY_ID_CACHE_TIMEOUT);

            $this->resetCache();

            return $quote;
        }

        return null;
    }

    /**
     * @throws RedisException
     */
    public function delete(int $id): bool
    {
        $key = sprintf(self::QUOTE_BY_ID_CACHE, $id);

        if ($this->quoteRepository->delete($id)) {
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
        $key = sprintf(self::QUOTE_BY_ID_CACHE, $id);

        if ($this->quoteRepository->accept($id)) {
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
        $key = sprintf(self::QUOTE_BY_ID_CACHE, $id);

        if ($this->quoteRepository->unaccept($id)) {
            $this->redis->del($key);

            $this->resetCache();

            return true;
        }

        return false;
    }

    private function resetCache(): void
    {
        $this->resetCachePatterns([
            'quote_find_*',
        ]);
    }
}
