<?php
declare(strict_types=1);

namespace App\DataProvider;

use App\Dto\SearchParamsDto;
use App\Repository\PartnerRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use Redis;
use RedisException;

class PartnerDataProvider extends BaseDataProvider
{
    const string PARTNERS_FIND_CACHE = 'partners_%s';
    const int PARTNERS_FIND_CACHE_TIMEOUT = 3600 * 24;
    private PartnerRepository $repository;

    public function __construct(PartnerRepository $repository, Redis $redis)
    {
        parent::__construct($redis);

        $this->repository = $repository;
    }

    /**
     * @throws RedisException
     */
    public function find(SearchParamsDto $searchParamsDto, bool $useCache = true): LengthAwarePaginator
    {
        $key = sprintf(self::PARTNERS_FIND_CACHE, $searchParamsDto->getHash());

        $result = $this->redis->get($key);
        if ($useCache && $result) {
            return unserialize($result);
        }

        $response = $this->repository->find($searchParamsDto);

        $this->redis->set($key, serialize($response), self::PARTNERS_FIND_CACHE_TIMEOUT);

        return $response;
    }
}
