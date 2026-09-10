<?php
declare(strict_types=1);

namespace App\Dto\Review;

use Illuminate\Http\Request;

class SearchParamsDto
{
    private bool $paginate;
    private int $page;
    private int $itemsPerPage;
    private ?string $orderBy;
    private bool $orderSortDesc;
    private ?string $search;

    public function __construct(
        bool $paginate,
        int $page,
        int $itemsPerPage,
        ?string $orderBy,
        bool $orderSortDesc,
        ?string $search,
    ) {
        $this->paginate = $paginate;
        $this->page = $page;
        $this->itemsPerPage = $itemsPerPage;
        $this->orderBy = $orderBy;
        $this->orderSortDesc = $orderSortDesc;
        $this->search = $search;
    }

    public static function createFromRequest(Request $request): self
    {
        $params = $request->all();

        $paginate = !empty($params['paginate']) ? (int) $params['paginate'] : 1;
        $page = !empty($params['page']) ? (int) $params['page'] : 1;
        $itemsPerPage = !empty($params['itemsPerPage']) ? (int) $params['itemsPerPage'] : 10;
        $orderBy = $params['sortBy'] ?? null;
        $orderSortDesc = empty($params['sortDesc']) || (bool)$params['sortDesc'];
        $search = $params['search'] ?? null;

        return new self(
            $paginate && $itemsPerPage > 0,
            $page,
            $itemsPerPage,
            $orderBy,
            $orderSortDesc,
            $search
        );
    }

    public function isPaginationEnabled(): bool
    {
        return $this->paginate;
    }

    public function getPage(): int
    {
        return $this->page;
    }

    public function getItemsPerPage(): int
    {
        return $this->itemsPerPage;
    }

    public function getOrderBy(): ?string
    {
        return $this->orderBy;
    }

    public function getOrderSortDesc(): bool
    {
        return $this->orderSortDesc;
    }

    public function getSearch(): ?string
    {
        return $this->search;
    }
}
