<?php

declare(strict_types=1);

namespace App\DataProvider;

use App\Models\User;
use App\Repository\AnalyticsRepository;
use App\Repository\ReportRepository;
use Carbon\Carbon;
use Psr\SimpleCache\InvalidArgumentException;
use Redis;
use RedisException;

class ReportDataProvider extends BaseDataProvider
{
    const string TOTAL_ENQUIRIES_CACHE = "total_enquiries_report_%d";
    const string TOTAL_QUOTES_CACHE = "total_quotes_report_%d";
    const int TOTAL_QUOTES_CACHE_TIMEOUT = 3600 * 24;
    const int TOTAL_ENQUIRIES_CACHE_TIMEOUT = 3600 * 24;

    private ReportRepository $reportRepository;
    private AnalyticsRepository $analyticsRepository;

    public function __construct(ReportRepository $reportRepository, AnalyticsRepository $analyticsRepository, Redis $redis)
    {
        parent::__construct($redis);

        $this->reportRepository = $reportRepository;
        $this->analyticsRepository = $analyticsRepository;
    }

    /**
     * @param int $branchId
     * @param bool $useCache
     * @return array
     * @throws InvalidArgumentException|RedisException
     */
    public function getTotalEnquiries(int $branchId, bool $useCache = true): array
    {
        $key = sprintf(self::TOTAL_ENQUIRIES_CACHE, $branchId);

        $result = $this->redis->get($key);
        if ($useCache && $result) {
            return unserialize($result);
        }

        $result = $this->reportRepository->getTotalEnquiries($branchId);

        $this->redis->set($key, serialize($result), self::TOTAL_ENQUIRIES_CACHE_TIMEOUT);

        return $result;
    }

    /**
     * @param int $branchId
     * @param bool $useCache
     * @return array []TotalEnquiryItemDto
     * @throws InvalidArgumentException|RedisException
     */
    public function getTotalQuotes(int $branchId, bool $useCache = true): array
    {
        $key = sprintf(self::TOTAL_QUOTES_CACHE, $branchId);

        $result = $this->redis->get($key);
        if ($useCache && $result) {
            return unserialize($result);
        }

        $result = $this->reportRepository->getTotalQuotes($branchId);

        $this->redis->set($key, serialize($result), self::TOTAL_QUOTES_CACHE_TIMEOUT);

        return $result;
    }

    public function getEnquiriesToTimeContract(User $user = null): array
    {
        return $this->reportRepository->getEnquiriesToTimeContract($user);
    }

    public function getEnquiriesToTimeMerchant(User $user = null): array
    {
        return $this->reportRepository->getEnquiriesToTimeMerchant($user);
    }

    public function getSupplierReport(User $user): array
    {
        return $this->reportRepository->getSupplierReport($user);
    }

    public function getLogisticsReport(User $user): array
    {
        return $this->reportRepository->getLogisticsReport($user);
    }

    public function getTotalBannerImpressionsForExpoId($expoId, ?Carbon $dateFrom, ?Carbon $dateTo): int
    {
        return $this->analyticsRepository->getTotalBannerImpressionsForExpoId($expoId, $dateFrom, $dateTo);
    }

    public function getTotalDocumentDownloadsExpoId($expoId, ?Carbon $dateFrom, ?Carbon $dateTo): int
    {
        return $this->analyticsRepository->getTotalDocumentDownloadsExpoId($expoId, $dateFrom, $dateTo);
    }

    public function getTotalBannerClicksForExpoId($expoId, ?Carbon $dateFrom, ?Carbon $dateTo): int
    {
        return $this->analyticsRepository->getTotalBannerClicksForExpoId($expoId, $dateFrom, $dateTo);
    }

    public function getTotalVideoViewsForExpoId($expoId, ?Carbon $dateFrom, ?Carbon $dateTo): int
    {
        return $this->analyticsRepository->getTotalVideoViewsForExpoId($expoId, $dateFrom, $dateTo);
    }

    public function getTotalBannerImpressionsByLocationForExpoId($expoId, ?Carbon $dateFrom, ?Carbon $dateTo): array
    {
        return $this->analyticsRepository->getTotalBannerImpressionsByLocationForExpoId($expoId, $dateFrom, $dateTo);
    }

    public function getTotalBannerClicksByLocationForExpoId($expoId, ?Carbon $dateFrom, ?Carbon $dateTo): array
    {
        return $this->analyticsRepository->getTotalBannerClicksByLocationForExpoId($expoId, $dateFrom, $dateTo);
    }

    public function getTotalBannerImpressionsByUserTypeForExpoId($expoId, ?Carbon $dateFrom, ?Carbon $dateTo): array
    {
        return $this->analyticsRepository->getTotalBannerImpressionsByUserTypeForExpoId($expoId, $dateFrom, $dateTo);
    }

    public function getTotalBannerClicksByUserTypeForExpoId($expoId, ?Carbon $dateFrom, ?Carbon $dateTo): array
    {
        return $this->analyticsRepository->getTotalBannerClicksByUserTypeForExpoId($expoId, $dateFrom, $dateTo);
    }

    public function getBuyerReport(User $user, int $projectId, int $worksPackageId): array
    {
        return $this->reportRepository->getBuyerReport($user, $projectId, $worksPackageId);
    }

    public function getContractorReport(User $user, int $projectId, int $worksPackageId = null): array
    {
        return $this->reportRepository->getContractorReport($user, $projectId, $worksPackageId);
    }

    public function getContractorMapData(?int $radius, array $type, int $projectId, array $worksPackageIds, User $user): array
    {
        return $this->reportRepository->getContractorMapData($radius, $type, $projectId, $worksPackageIds, $user);
    }

    public function getSubcontractorReport(User $user, array $subcontractorIds): array
    {
        return $this->reportRepository->getSubcontractorReport($user, $subcontractorIds);
    }

    public function getUserSummary(User $user): array
    {
        return $this->reportRepository->getUserSummary($user);
    }
}
