<?php

declare(strict_types=1);

namespace App\DataProvider;

use App\Dto\Answer\SearchParamsDto;
use App\Dto\SupplyFitEnquiryQuote\SupplyFitEnquiryQuoteDto;
use App\Models\SupplyFitEnquiry;
use App\Models\SupplyFitEnquiryQuote;
use App\Models\User;
use App\Repository\SupplyFitEnquiryQuoteRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class SupplyFitEnquiryQuoteDataProvider extends BaseDataProvider
{
    public const string SUPPLY_FIT_ENQUIRY_QUOTE_FIND_CACHE = 'supply_fit_enquiry_quote_find_%d_%d_%s';
    public const int SUPPLY_FIT_ENQUIRY_QUOTE_FIND_CACHE_TIMEOUT = 3600 * 24;

    public const string SUPPLY_FIT_ENQUIRY_QUOTE_BY_ID_CACHE = 'supply_fit_enquiry_quote_by_id_%d';
    public const int SUPPLY_FIT_ENQUIRY_QUOTE_BY_ID_CACHE_TIMEOUT = 3600 * 24;

    private SupplyFitEnquiryQuoteRepository $supplyFitEnquiryQuoteRepository;

    public function __construct(SupplyFitEnquiryQuoteRepository $supplyFitEnquiryQuoteRepository, \Redis $redis)
    {
        parent::__construct($redis);

        $this->supplyFitEnquiryQuoteRepository = $supplyFitEnquiryQuoteRepository;
        $this->redis = $redis;
    }

    /**
     * @throws \RedisException
     */
    public function find(SearchParamsDto $searchParamsDto, User $user, int $enquiryId, bool $useCache = true): LengthAwarePaginator
    {
        $key = sprintf(self::SUPPLY_FIT_ENQUIRY_QUOTE_FIND_CACHE, $user->getId(), $enquiryId, $searchParamsDto->getHash());

        $result = $this->redis->get($key);
        if ($useCache && $result) {
            return unserialize($result);
        }

        $response = $this->supplyFitEnquiryQuoteRepository->find($searchParamsDto, $user, $enquiryId);

        $this->redis->set($key, serialize($response), self::SUPPLY_FIT_ENQUIRY_QUOTE_FIND_CACHE_TIMEOUT);

        return $response;
    }

    /**
     * @throws \RedisException
     */
    public function getById(int $id, bool $useCache = true): ?SupplyFitEnquiryQuote
    {
        $key = sprintf(self::SUPPLY_FIT_ENQUIRY_QUOTE_BY_ID_CACHE, $id);

        $result = $this->redis->get($key);
        if ($useCache && $result) {
            return unserialize($result);
        }

        $quote = $this->supplyFitEnquiryQuoteRepository->getById($id);

        $this->redis->set($key, serialize($quote), self::SUPPLY_FIT_ENQUIRY_QUOTE_BY_ID_CACHE_TIMEOUT);

        return $quote;
    }

    /**
     * @throws \RedisException
     */
    public function store(SupplyFitEnquiryQuoteDto $dto, SupplyFitEnquiry $enquiry, User $user): ?SupplyFitEnquiryQuote
    {
        $quote = $this->supplyFitEnquiryQuoteRepository->store($dto, $enquiry, $user);
        if ($quote) {
            $key = sprintf(self::SUPPLY_FIT_ENQUIRY_QUOTE_BY_ID_CACHE, $quote->getId());

            $this->redis->set($key, serialize($quote), self::SUPPLY_FIT_ENQUIRY_QUOTE_BY_ID_CACHE_TIMEOUT);

            $this->resetCache();

            return $quote;
        }

        return null;
    }

    /**
     * @throws \RedisException
     */
    private function resetCache(): void
    {
        $this->resetCachePatterns([
            'supply_fit_enquiry_quote_find_*',
        ]);
    }

    public function isLastAnswerOlderThanADay(int $enquiryId, int $excludeId): bool
    {
        return $this->supplyFitEnquiryQuoteRepository->isLastAnswerOlderThanADay($enquiryId, $excludeId);
    }

    /**
     * @throws \RedisException
     */
    public function storeSupplierInvoiceNo(string $supplierInvoiceNo, int $id): ?SupplyFitEnquiryQuote
    {
        $quote = $this->supplyFitEnquiryQuoteRepository->storeSupplierInvoiceNo($supplierInvoiceNo, $id);
        if ($quote) {
            $key = sprintf(self::SUPPLY_FIT_ENQUIRY_QUOTE_BY_ID_CACHE, $quote->getId());

            $this->redis->set($key, serialize($quote), self::SUPPLY_FIT_ENQUIRY_QUOTE_BY_ID_CACHE_TIMEOUT);

            $this->resetCache();

            return $quote;
        }

        return null;
    }

    /**
     * @throws \RedisException
     */
    public function storeLocalMaterialSpend(?float $localMaterialSpend, int $id): ?SupplyFitEnquiryQuote
    {
        $quote = $this->supplyFitEnquiryQuoteRepository->storeLocalMaterialSpend($localMaterialSpend, $id);
        if ($quote) {
            $key = sprintf(self::SUPPLY_FIT_ENQUIRY_QUOTE_BY_ID_CACHE, $quote->getId());

            $this->redis->set($key, serialize($quote), self::SUPPLY_FIT_ENQUIRY_QUOTE_BY_ID_CACHE_TIMEOUT);

            $this->resetCache();

            return $quote;
        }

        return null;
    }

    /**
     * @throws \RedisException
     */
    public function update(SupplyFitEnquiryQuoteDto $dto, int $id): ?SupplyFitEnquiryQuote
    {
        $quote = $this->supplyFitEnquiryQuoteRepository->update($dto, $id);
        if ($quote) {
            $key = sprintf(self::SUPPLY_FIT_ENQUIRY_QUOTE_BY_ID_CACHE, $quote->getId());

            $this->redis->set($key, serialize($quote), self::SUPPLY_FIT_ENQUIRY_QUOTE_BY_ID_CACHE_TIMEOUT);

            $this->resetCache();

            return $quote;
        }

        return null;
    }

    /**
     * @throws \RedisException
     */
    public function delete(int $id): bool
    {
        $key = sprintf(self::SUPPLY_FIT_ENQUIRY_QUOTE_BY_ID_CACHE, $id);

        if ($this->supplyFitEnquiryQuoteRepository->delete($id)) {
            $this->redis->del($key);

            $this->resetCache();

            return true;
        }

        return false;
    }

    /**
     * @throws \RedisException
     */
    public function unaccept(int $id): bool
    {
        $key = sprintf(self::SUPPLY_FIT_ENQUIRY_QUOTE_BY_ID_CACHE, $id);

        if ($this->supplyFitEnquiryQuoteRepository->unaccept($id)) {
            $this->redis->del($key);

            $this->resetCache();

            return true;
        }

        return false;
    }

    /**
     * @throws \RedisException
     */
    public function accept(int $id): bool
    {
        $key = sprintf(self::SUPPLY_FIT_ENQUIRY_QUOTE_BY_ID_CACHE, $id);

        if ($this->supplyFitEnquiryQuoteRepository->accept($id)) {
            $this->redis->del($key);

            $this->resetCache();

            return true;
        }

        return false;
    }
}
