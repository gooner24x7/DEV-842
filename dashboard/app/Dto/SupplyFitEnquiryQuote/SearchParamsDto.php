<?php
declare(strict_types=1);

namespace App\Dto\SupplyFitEnquiry;

use Illuminate\Http\Request;

class SearchParamsDto
{
    private bool $paginate;
    private bool $orderSortDesc;
    private string $orderBy;
    private int|null $itemsPerPage;
    private int $page;
    private int|null $radius;

    public function __construct(
        ?string $orderBy,
        bool    $orderSortDesc,
        bool    $paginate,
        int     $radius,
        ?int    $itemsPerPage = null,
        int     $page = 1
    )
    {
        $this->orderBy = $orderBy;
        $this->orderSortDesc = $orderSortDesc;
        $this->paginate = $paginate;
        $this->itemsPerPage = $itemsPerPage;
        $this->page = $page;
        $this->radius = $radius;
    }

    public static function createFromRequest(Request $request): self
    {
        $params = $request->all();

        $orderBy = $params['sortBy'] ?? null;
        $orderSortDesc = (int)($params['sortDesc'] ?? 0) === 1;
        $paginate = (int)($params['paginate'] ?? 1) === 1;
        $itemsPerPage = (isset($params['itemsPerPage'])) ? (int)$params['itemsPerPage'] : null;
        $page = (int)($params['page'] ?? 1);
        $radius = (int)($params['radius'] ?? 5);

        return new self(
            $orderBy,
            $orderSortDesc,
            $paginate && $itemsPerPage > 0,
            $radius,
            $itemsPerPage,
            $page
        );
    }

    public function getHash(): string
    {
        return sprintf(
            '%s_%d_%d_%d_%d_%d',
            $this->getOrderBy() ?? '',
            $this->getOrderSortDesc() ? 1 : 0,
            $this->isPaginationEnabled() ? 1 : 0,
            $this->getItemsPerPage() ?? 0,
            $this->getPage(),
            $this->getRadius()
        );
    }

    public function getOrderBy(): ?string
    {
        return $this->orderBy;
    }

    public function getOrderSortDesc(): bool
    {
        return $this->orderSortDesc;
    }

    public function isPaginationEnabled(): bool
    {
        return $this->paginate;
    }

    public function getItemsPerPage(): ?int
    {
        return $this->itemsPerPage;
    }

    public function getPage(): int
    {
        return $this->page;
    }

    public function getRadius(): ?int
    {
        return $this->radius;
    }
}
