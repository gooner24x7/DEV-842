<?php

declare(strict_types=1);

namespace App\DataProvider;

use App\Dto\Category\CategoryDto;
use App\Dto\SearchParamsDto;
use App\Exceptions\NotFoundException;
use App\Models\Category;
use App\Repository\CategoryRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use Psr\SimpleCache\InvalidArgumentException;
use Redis;
use RedisException;

class CategoryDataProvider extends BaseDataProvider
{
    const string CATEGORY_FIND_CACHE = 'category_find_%s';
    const int CATEGORY_FIND_CACHE_TIMEOUT = 3600 * 24;

    const string CATEGORY_BY_ID = 'category_id_%d';
    const int CATEGORY_BY_ID_CACHE_TIMEOUT = 3600 * 24;

    const string CATEGORY_OPTIONS_FOR_USER = 'category_options_user_id_%d_%s';

    const string CATEGORY_TREE_CACHE = 'category_tree_%d';
    const int CATEGORY_TREE_CACHE_TIMEOUT = 3600 * 24;
    private CategoryRepository $categoryRepository;

    public function __construct(CategoryRepository $categoryRepository, Redis $redis)
    {
        parent::__construct($redis);

        $this->categoryRepository = $categoryRepository;
    }

    /**
     * @throws RedisException
     */
    public function find(SearchParamsDto $searchParamsDto, $useCache = true): LengthAwarePaginator
    {
        $key = sprintf(self::CATEGORY_FIND_CACHE, $searchParamsDto->getHash());

        $result = $this->redis->get($key);
        if ($useCache && $result) {
            return unserialize($result);
        }

        $result = $this->categoryRepository->find($searchParamsDto);

        $this->redis->set($key, serialize($result), self::CATEGORY_FIND_CACHE_TIMEOUT);

        return $result;
    }

    /**
     * @throws RedisException
     */
    public function get(int $category_id, bool $useCache = true): ?Category
    {
        $key = sprintf(self::CATEGORY_BY_ID, $category_id);

        $result = $this->redis->get($key);
        if ($useCache && $result) {
            return unserialize($result);
        }

        $category = $this->categoryRepository->get($category_id);

        $this->redis->set($key, serialize($category), self::CATEGORY_BY_ID_CACHE_TIMEOUT);

        return $category;
    }

    public function getTree(bool $useCache = true): array
    {
        return $this->getSubTree(0, $useCache);
    }

    /**
     * @throws RedisException
     */
    private function getSubTree(int $parentId, bool $useCache = true): array
    {
        $key = sprintf(self::CATEGORY_TREE_CACHE, $parentId);
        $result = $this->redis->get($key);
        if ($useCache && $result) {
            return unserialize($result);
        }

        $items = $this->categoryRepository->getCategories($parentId);

        $categories = [];
        /** @var Category $item */
        foreach ($items as $item) {
            $children = $this->getSubTree($item->getId(), $useCache);

            $categories[] = [
                'id' => $item->getId(),
                'name' => $item->getName(),
                'children' => $children,
            ];
        }

        $this->redis->set($key, serialize($categories), self::CATEGORY_TREE_CACHE_TIMEOUT);

        return $categories;
    }

    /**
     * @throws RedisException
     */
    public function getSelectOptions(int $userId, string $search, $useCache = true): array
    {
        $key = sprintf(self::CATEGORY_OPTIONS_FOR_USER, $userId, $search);

        $result = $this->redis->get($key);
        if ($useCache && $result) {
            return unserialize($result);
        }

        $result = $this->categoryRepository->getListArrayIdName($userId, $search);

        $this->redis->set($key, serialize($result), self::CATEGORY_FIND_CACHE_TIMEOUT);

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
        $this->redis->del(sprintf(self::CATEGORY_BY_ID, $id));

        if ($this->categoryRepository->delete($id)) {
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
        $this->resetCachePatterns([
            sprintf(self::CATEGORY_FIND_CACHE, '*')
        ]);
    }

    /**
     * @throws RedisException
     */
    public function create(CategoryDto $dto): ?Category
    {
        $category = $this->categoryRepository->create($dto);
        if ($category) {
            $this->redis->set(sprintf(self::CATEGORY_BY_ID, $category->getId()), serialize($category), self::CATEGORY_BY_ID_CACHE_TIMEOUT);
        }

        $this->resetFindCache();

        return $category;
    }

    /**
     * @throws RedisException
     * @throws NotFoundException
     */
    public function update(int $id, CategoryDto $dto): ?Category
    {
        $category = $this->categoryRepository->update($id, $dto);
        if ($category) {
            $this->redis->set(sprintf(self::CATEGORY_BY_ID, $category->getId()), serialize($category), self::CATEGORY_BY_ID_CACHE_TIMEOUT);
        }

        $this->resetFindCache();

        return $category;
    }
}
