<?php

declare(strict_types=1);

namespace App\DataProvider;

use App\Dto\Product\ProductDto;
use App\Dto\SearchParamsDto;
use App\Exceptions\NotFoundException;
use App\Models\Product;
use App\Repository\ProductRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use Psr\SimpleCache\InvalidArgumentException;
use Redis;
use RedisException;

class ProductDataProvider extends BaseDataProvider
{
    const string PRODUCT_BY_ID = 'product_id_%d';
    const string PRODUCT_FIND_CACHE = 'product_find_%s';
    const string PRODUCT_FIND_BY_NAME_CACHE = 'product_find_by_name_%s';
    const string PRODUCT_OPTIONS_FOR_USER = 'product_options_user_id_%d_%s';
    const int PRODUCT_FIND_CACHE_TIMEOUT = 3600;
    const int PRODUCT_BY_ID_CACHE_TIMEOUT = 3600;

    private ProductRepository $productRepository;

    public function __construct(ProductRepository $productRepository, Redis $redis)
    {
        parent::__construct($redis);

        $this->productRepository = $productRepository;
    }

    /**
     * @throws RedisException
     */
    public function find(SearchParamsDto $searchParamsDto, $useCache = true): LengthAwarePaginator
    {
        $key = sprintf(self::PRODUCT_FIND_CACHE, $searchParamsDto->getHash());

        $result = $this->redis->get($key);
        if ($useCache && $result) {
            return unserialize($result);
        }

        $result = $this->productRepository->find($searchParamsDto);

        $this->redis->set($key, serialize($result), self::PRODUCT_FIND_CACHE_TIMEOUT);

        return $result;
    }

    /**
     * @throws RedisException
     */
    public function getByName(string $name, bool $useCache = true): ?Product
    {
        $key = sprintf(self::PRODUCT_FIND_BY_NAME_CACHE, $name);

        $result = $this->redis->get($key);
        if ($useCache && $result) {
            return unserialize($result);
        }

        $product = $this->productRepository->getByName($name);

        $this->redis->set($key, serialize($product), self::PRODUCT_FIND_CACHE_TIMEOUT);

        return $product;
    }

    /**
     * @throws RedisException
     */
    public function getSelectOptions(int $userId, array $typeIds, $useCache = false): array
    {
        $key = sprintf(self::PRODUCT_OPTIONS_FOR_USER, $userId, join(',', $typeIds));

        $result = $this->redis->get($key);
        if ($useCache && $result) {
            return unserialize($result);
        }

        $result = $this->productRepository->getListArrayIdName($userId, $typeIds);

        $this->redis->set($key, serialize($result), self::PRODUCT_FIND_CACHE_TIMEOUT);

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
        $this->redis->del(sprintf(self::PRODUCT_BY_ID, $id));

        if ($this->productRepository->delete($id)) {
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
            'product_options_user_id_*',
            sprintf(self::PRODUCT_FIND_CACHE, '*')
        ];

        $this->resetCachePatterns($patterns);
    }

    /**
     * @throws RedisException
     */
    public function create(ProductDto $dto): ?Product
    {
        $product = $this->productRepository->create($dto);
        if ($product) {
            $this->redis->set(sprintf(self::PRODUCT_BY_ID, $product->getId()), serialize($product), self::PRODUCT_BY_ID_CACHE_TIMEOUT);
        }

        $this->resetFindCache();

        return $product;
    }

    /**
     * @throws RedisException
     * @throws NotFoundException
     */
    public function update(int $id, ProductDto $dto): ?Product
    {
        $product = $this->productRepository->update($id, $dto);
        if ($product) {
            $this->redis->set(sprintf(self::PRODUCT_BY_ID, $product->getId()), serialize($product), self::PRODUCT_BY_ID_CACHE_TIMEOUT);
        }

        $this->resetFindCache();

        return $product;
    }
}
