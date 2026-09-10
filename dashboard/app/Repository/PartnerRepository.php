<?php
declare(strict_types=1);

namespace App\Repository;

use App\Dto\SearchParamsDto;
use App\Models\Partner;
use Illuminate\Pagination\LengthAwarePaginator;

class PartnerRepository
{
    const string DEFAULT_ORDER_FIELD_NAME = 'partners.id';
    const int ITEMS_PER_PAGE = 20;

    public function find(SearchParamsDto $searchParamsDto): LengthAwarePaginator
    {
        $query = Partner::select([
            'partners.id',
            'partners.website',
            'partners.email',
            'partners.phone',
            'partners.logo',
            'partners.description'
        ])->orderBy(
            $searchParamsDto->getOrderBy() ?? self::DEFAULT_ORDER_FIELD_NAME,
            $searchParamsDto->getOrderSortDesc() ? 'desc' : 'asc'
        );

        $itemsPerPage = $searchParamsDto->getItemsPerPage() ?? self::ITEMS_PER_PAGE;
        if (!$searchParamsDto->isPaginationEnabled()) {
            $itemsPerPage = PHP_INT_MAX;
        }

        return $query->paginate($itemsPerPage);
    }
}
