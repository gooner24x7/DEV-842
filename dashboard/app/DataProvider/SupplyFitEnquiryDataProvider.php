<?php

declare(strict_types=1);

namespace App\DataProvider;

use App\Dto\Question\SearchParamsDto;
use App\Dto\SupplyFitEnquiry\SupplyFitEnquiryBatchDto;
use App\Dto\SupplyFitEnquiry\SupplyFitEnquiryDto;
use App\Models\SupplyFitEnquiry;
use App\Models\User;
use App\Repository\ProjectRepository;
use App\Repository\SupplyFitEnquiryRepository;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use RedisException;

class SupplyFitEnquiryDataProvider extends BaseDataProvider
{
    public const string ENQUIRY_BY_ID_CACHE = 'supply_fit_enquiry_by_id_%d';
    public const int ENQUIRY_BY_ID_CACHE_TIMEOUT = 3600 * 24;

    public const string ENQUIRY_FIND_CACHE = 'supply_fit_enquiry_find_%d_%s';
    public const int ENQUIRY_FIND_CACHE_TIMEOUT = 3600 * 24;

    public const string ENQUIRY_PROJECT_OPTIONS = 'supply_fit_enquiry_project_opts_%s_%d_%d';
    public const int ENQUIRY_PROJECT_OPTIONS_TIMEOUT = 3600 * 24;

    public const string ENQUIRY_WORKS_PACKAGE_NAME_OPTIONS = 'works_package_name_opts_%s_%d_%s';
    public const int ENQUIRY_WORKS_PACKAGE_NAME_OPTIONS_TIMEOUT = 3600 * 24;

    public const string ENQUIRY_EMAIL_CACHE = 'supply_fit_enquiry_email_%d';
    public const int ENQUIRY_EMAIL_CACHE_TIMEOUT = 3600 * 24;

    private SupplyFitEnquiryRepository $enquiryRepository;
    private ProjectRepository $projectRepository;

    public function __construct(
        SupplyFitEnquiryRepository $enquiryRepository,
        ProjectRepository $projectRepository,
        \Redis $redis
    ) {
        parent::__construct($redis);

        $this->enquiryRepository = $enquiryRepository;
        $this->projectRepository = $projectRepository;
    }

    /**
     * @throws RedisException
     */
    public function find(SearchParamsDto $searchParamsDto, User $user, bool $useCache = true): LengthAwarePaginator
    {
        $key = sprintf(self::ENQUIRY_FIND_CACHE, $user->getId(), $searchParamsDto->getHash());

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
    public function get(int $id, bool $useCache = true): ?SupplyFitEnquiry
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
    public function setArchived(SupplyFitEnquiry $enquiry): ?SupplyFitEnquiry
    {
        $key = sprintf(self::ENQUIRY_BY_ID_CACHE, $enquiry->getId());

        $enquiry = $this->enquiryRepository->setArchived($enquiry);
        $this->redis->set($key, serialize($enquiry), self::ENQUIRY_BY_ID_CACHE_TIMEOUT);

        $this->resetCache();

        return $enquiry;
    }

    /**
     * @throws RedisException
     */
    private function resetCache(): void
    {
        $this->resetCachePatterns([
            'question_find_*',
            'question_project_opts_*',
        ]);
    }

    /**
     * @throws RedisException
     */
    public function duplicate(int $id, SupplyFitEnquiryDto $dto): ?SupplyFitEnquiry
    {
        $enquiry = $this->enquiryRepository->duplicate($id, $dto);

        $this->resetCache();

        return $enquiry;
    }

    /**
     * @throws RedisException
     */
    public function restore(SupplyFitEnquiry $enquiry): ?SupplyFitEnquiry
    {
        $key = sprintf(self::ENQUIRY_BY_ID_CACHE, $enquiry->getId());

        $enquiry = $this->enquiryRepository->restore($enquiry);
        $this->redis->set($key, serialize($enquiry), self::ENQUIRY_BY_ID_CACHE_TIMEOUT);

        $this->resetCache();

        return $enquiry;
    }

    /**
     * @throws RedisException
     * @throws Exception
     */
    public function delete(int $id): bool
    {
        $key = sprintf(self::ENQUIRY_BY_ID_CACHE, $id);

        if ($this->enquiryRepository->delete($id)) {
            $this->redis->del($key);

            $this->resetCache();

            return true;
        }

        return false;
    }

    public function doProjectsBelongToUser(array $projectIds, User $user): bool
    {
        if ($this->enquiryRepository->doProjectsBelongToUser($projectIds, $user)) {
            return true;
        }

        return $this->projectRepository->doProjectsBelongToUser($projectIds, $user);
    }

    /**
     * @throws RedisException
     * @throws Exception
     */
    public function store(SupplyFitEnquiryDto $dto): ?SupplyFitEnquiry
    {
        $enquiry = $this->enquiryRepository->store($dto);
        if ($enquiry) {
            $key = sprintf(self::ENQUIRY_BY_ID_CACHE, $enquiry->getId());

            $this->redis->set($key, serialize($enquiry), self::ENQUIRY_BY_ID_CACHE_TIMEOUT);

            $this->resetCache();

            return $enquiry;
        }

        return null;
    }

    public function storeBatch(SupplyFitEnquiryDto $dto): array
    {
        try {
            $items = $this->enquiryRepository->storeBatch($dto);

            $this->resetCache();

            return $items;
        } catch (Exception $e) {
            return [$e->getMessage()];
        }
    }

    /**
     * @throws RedisException
     */
    public function update(SupplyFitEnquiryDto $dto, int $id): ?SupplyFitEnquiry
    {
        $enquiry = $this->enquiryRepository->update($dto, $id);
        if ($enquiry) {
            $key = sprintf(self::ENQUIRY_BY_ID_CACHE, $enquiry->getId());

            $this->redis->set($key, serialize($enquiry), self::ENQUIRY_BY_ID_CACHE_TIMEOUT);

            $this->resetCache();

            return $enquiry;
        }

        return null;
    }

    public function getOverview(int $id): array
    {
        return $this->enquiryRepository->getOverview($id);
    }

    /**
     * @throws RedisException
     */
    public function getProjectOptions(string $search, User $user, bool $archived = false, bool $useCache = false): Collection
    {
        $key = sprintf(self::ENQUIRY_PROJECT_OPTIONS, $search, $user->getId(), $archived ? 1 : 0);

        $result = $this->redis->get($key);
        if ($useCache && $result) {
            return unserialize($result);
        }

        $response = $this->enquiryRepository->getProjectOptions($search, $user, $archived);

        $this->redis->set($key, serialize($response), self::ENQUIRY_PROJECT_OPTIONS_TIMEOUT);

        return $response;
    }

    /**
     * @throws RedisException
     */
    public function getWorksPackageOptions(string $search, User $user, array $projectIds, bool $useCache = false): Collection
    {
        $projectIdStr = implode(',', $projectIds);
        $key = sprintf(self::ENQUIRY_WORKS_PACKAGE_NAME_OPTIONS, $search, $user->getId(), $projectIdStr);

        $result = $this->redis->get($key);
        if ($useCache && $result) {
            return unserialize($result);
        }

        $response = $this->enquiryRepository->getWorksPackageOptions($search, $user, $projectIds);

        $this->redis->set($key, serialize($response), self::ENQUIRY_WORKS_PACKAGE_NAME_OPTIONS_TIMEOUT);

        return $response;
    }


    /**
     * @throws RedisException
     */
    public function getEmailForEnquiry(int $id, bool $useCache = true): ?string
    {
        $key = sprintf(self::ENQUIRY_EMAIL_CACHE, $id);

        $result = $this->redis->get($key);
        if ($useCache && $result) {
            return unserialize($result);
        }

        $email = $this->enquiryRepository->getEmailForEnquiry($id);

        $this->redis->set($key, serialize($email), self::ENQUIRY_EMAIL_CACHE_TIMEOUT);

        return $email;
    }
}
