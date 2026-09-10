<?php
declare(strict_types=1);

namespace App\Repository;

use App\DataProvider\CategoryDataProvider;
use App\Dto\Product\ProductDto;
use App\Dto\SearchParamsDto;
use App\Exceptions\NotFoundException;
use App\Models\Product;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use RedisException;

class ProductRepository
{
    const int ITEMS_PER_PAGE = 10;
    const string DEFAULT_ORDER_FIELD_NAME = 'id';
    private CategoryDataProvider $categoryDataProvider;

    public function __construct(CategoryDataProvider $categoryDataProvider)
    {
        $this->categoryDataProvider = $categoryDataProvider;
    }

    public function find(SearchParamsDto $searchParamsDto): LengthAwarePaginator
    {
        $query = Product::orderBy(
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
        $response->getCollection()->transform(function (Product $product): array {
            $parentCategory = $this->categoryDataProvider->get($product->category_id);

            return array_merge(
                $product->toArray(),
                [
                    'category' => $parentCategory ? $parentCategory->getName() : '',
                ]
            );
        });

        return $response;
    }

    public function get(int $id): ?Product
    {
        return Product::where(['id' => $id])->first();
    }

    /**
     * @throws RedisException
     */
    public function getListArrayIdName(int $userId, array $typeIds): array
    {
        $query = Product::orderBy('products.name', 'asc');
        if ($typeIds) {
            $query->whereIn(DB::raw('(select type from categories where id=products.category_id)'), $typeIds);
        }
        $products = $query->get();

        $categories = [];
        /** @var Product $product */
        foreach ($products as $product) {
            $categoryObject = $this->categoryDataProvider->get($product->getCategoryId()) ?? '';

            if (!$categoryObject || !$categoryObject->isActive()) {
                continue;
            }

            $category = $categoryObject->getName();

            if (!isset($categories[$category])) {
                $categories[$category] = [];
            }

            $categories[$category][] = $product;
        }

        $items = [];
        foreach ($categories as $category => $products) {
            $items[] = ['header' => $category];

            foreach ($products as $product) {
                $items[] = $product;
            }

            $items[] = ['divider' => true];
        }


        return $items;
    }

    public function getByName(string $name): ?Product
    {
        return Product::where(['name' => $name])->first();
    }

    /**
     * @param int $id
     * @return bool
     * @throws NotFoundException
     */
    public function delete(int $id): bool
    {
        $product = $this->get($id);
        if ($product === null) {
            throw new NotFoundException();
        }

        return (bool)$product->delete();
    }

    public function create(ProductDto $dto): ?Product
    {
        if ($product = Product::create([
            'name' => $dto->getName(),
            'category_id' => $dto->getCategoryId(),
        ])) {
            return $product;
        }

        return null;
    }

    /**
     * @param int $id
     * @param ProductDto $dto
     * @return Product|null
     * @throws NotFoundException
     */
    public function update(int $id, ProductDto $dto): ?Product
    {
        $product = $this->get($id);
        if ($product === null) {
            throw new NotFoundException();
        }

        $product->name = $dto->getName();
        $product->category_id = $dto->getCategoryId();
        $product->save();

        return $product;
    }
}
