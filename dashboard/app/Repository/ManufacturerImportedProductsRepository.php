<?php
declare(strict_types=1);

namespace App\Repository;

use App\Dto\SearchParamsDto;
use App\Models\ManufacturerImportedProduct;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

class ManufacturerImportedProductsRepository
{
    const int ITEMS_PER_PAGE = 10;
    const string DEFAULT_ORDER_FIELD_NAME = 'id';

    public function find(SearchParamsDto $searchParamsDto, ?int $merchantId): LengthAwarePaginator
    {
        $query = ManufacturerImportedProduct::orderBy(
            $searchParamsDto->getOrderBy() ?? self::DEFAULT_ORDER_FIELD_NAME,
            $searchParamsDto->getOrderSortDesc() ? 'desc' : 'asc'
        );

        if ($merchantId) {
            $query->where(['manufacturer_id' => $merchantId]);
        }

        $itemsPerPage = $searchParamsDto->getItemsPerPage() ?? self::ITEMS_PER_PAGE;
        if (!$searchParamsDto->isPaginationEnabled()) {
            $itemsPerPage = PHP_INT_MAX;
        }

        return $query->paginate($itemsPerPage);
    }

    public function getManufacturerByProductId(int $productId): ?User
    {
        $product = ManufacturerImportedProduct::where(['id' => $productId])->first();
        if (!$product) {
            return null;
        }

        return User::where(['id' => $product->manufacturer_id])->first();
    }
}
