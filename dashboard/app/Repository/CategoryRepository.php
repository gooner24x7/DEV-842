<?php
declare(strict_types=1);

namespace App\Repository;

use App\Dto\Category\CategoryDto;
use App\Dto\SearchParamsDto;
use App\Exceptions\NotFoundException;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class CategoryRepository
{
    const int ITEMS_PER_PAGE = 10;
    const string DEFAULT_ORDER_FIELD_NAME = 'id';

    public function find(SearchParamsDto $searchParamsDto): LengthAwarePaginator
    {
        $query = Category::orderBy(
            $searchParamsDto->getOrderBy() ?? self::DEFAULT_ORDER_FIELD_NAME,
            $searchParamsDto->getOrderSortDesc() ? 'desc' : 'asc'
        );

        $itemsPerPage = $searchParamsDto->getItemsPerPage() ?? self::ITEMS_PER_PAGE;
        if (!$searchParamsDto->isPaginationEnabled()) {
            $itemsPerPage = PHP_INT_MAX;
        }

        return $this->enrichResponse($query->paginate($itemsPerPage));
    }

    private function enrichResponse(LengthAwarePaginator $response): LengthAwarePaginator
    {
        $response->getCollection()->transform(function (Category $category): array {
            $parentCategory = $this->get($category->category_id);

            return array_merge(
                $category->toArray(),
                [
                    'category' => $parentCategory ? $parentCategory->name : '',
                ]
            );
        });

        return $response;
    }

    public function get(int $id): ?Category
    {
        return Category::where(['id' => $id])->first();
    }

    /**
     * @param int $id
     * @return bool
     * @throws NotFoundException
     */
    public function delete(int $id): bool
    {
        $category = $this->get($id);
        if ($category === null) {
            throw new NotFoundException();
        }

        return (bool)$category->delete();
    }

    public function create(CategoryDto $dto): ?Category
    {
        $category = $this->get($dto->getCategoryId());

        if ($category = Category::create([
            'name' => $dto->getName(),
            'category_id' => $dto->getCategoryId(),
            'active' => $dto->getActive(),
            'slug' => $dto->getSlug(),
            'type' => ($category) ? $category->getType() : $dto->getType()
        ])) {
            return $category;
        }

        return null;
    }

    /**
     * @param int $id
     * @param CategoryDto $dto
     * @return Product|null
     * @throws NotFoundException
     */
    public function update(int $id, CategoryDto $dto): ?Category
    {
        $category = $this->get($id);

        if ($category === null) {
            throw new NotFoundException();
        }

        $categoryParent = $this->get($dto->getCategoryId());

        $category->name = $dto->getName();
        $category->category_id = $dto->getCategoryId();
        $category->active = $dto->getActive();
        $category->slug = $dto->getSlug();
        $category->type = ($categoryParent) ? $categoryParent->getType() : $dto->getType();

        $category->save();

        return $category;
    }

    public function getListArrayIdName(int $userId, string $search): array
    {
        $categories = Category::orderBy('category_id', 'asc')
            ->where('name', 'like', "%$search%")
            ->limit(20)
            ->get();

        $items = [];
        /** @var Category $item */
        foreach ($categories as $item) {
            $items[] = [
                'id' => $item->getId(),
                'name' => $item->getName(),
            ];
        }

        return $items;
    }

    public function getCategories(int $parentId): Collection
    {
        return Category::select(['id', 'name', 'category_id'])
            ->where(['category_id' => $parentId])
            ->where(['active' => true])
            ->orderBy('name', 'asc')
            ->get();
    }
}
