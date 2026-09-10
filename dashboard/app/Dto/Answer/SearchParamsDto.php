<?php
declare(strict_types=1);

namespace App\Dto\Answer;

use Illuminate\Http\Request;

class SearchParamsDto
{
    private ?string $orderBy;
    private bool $orderSortDesc;
    private bool $paginate;
    private ?int $radius;
    private ?int $itemsPerPage;
    private int  $page;
    private ?int $quoteId;

    public function __construct(
        ?string $orderBy,
        bool    $orderSortDesc,
        bool    $paginate,
        int     $radius,
        ?int    $itemsPerPage = null,
        int     $page = 1,
        ?int    $quoteId = null,
    ) {
        $this->orderBy       = $orderBy;
        $this->orderSortDesc = $orderSortDesc;
        $this->paginate      = $paginate;
        $this->radius        = $radius;
        $this->itemsPerPage  = $itemsPerPage;
        $this->page          = $page;
        $this->quoteId       = $quoteId;
    }

    public static function createFromRequest(Request $request): self
    {
        $params = $request->all();

        $orderBy       = $params['sortBy'] ?? null;
        $orderSortDesc = (int)($params['sortDesc'] ?? 0) === 1;
        $paginate      = (int)($params['paginate'] ?? 1) === 1;
        $radius        = (int)($params['radius'] ?? 5);
        $itemsPerPage  = (isset($params['itemsPerPage'])) ? (int)$params['itemsPerPage'] : null;
        $page          = (int)($params['page'] ?? 1);
        $quoteId       = !empty($params['quoteId']) ? (int)$params['quoteId'] : null;

        return new self(
            $orderBy,
            $orderSortDesc,
            $paginate && $itemsPerPage > 0,
            $radius,
            $itemsPerPage,
            $page,
            $quoteId
        );
    }

    public function getRadius(): ?int
    {
        return $this->radius;
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

    public function getQuoteId(): ?int
    {
        return $this->quoteId;
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
}
