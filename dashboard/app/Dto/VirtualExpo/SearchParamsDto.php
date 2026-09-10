<?php
declare(strict_types=1);

namespace App\Dto\VirtualExpo;

use Illuminate\Http\Request;

class SearchParamsDto
{
    private bool $paginate;
    private bool $orderSortDesc;
    private ?string $orderBy;
    private ?int $itemsPerPage;
    private int $page;
    private bool $published;

    public function __construct(?string $orderBy, bool $orderSortDesc, bool $paginate, ?int $itemsPerPage = null, int $page = 1, bool $published = false)
    {
        $this->orderBy = $orderBy;
        $this->orderSortDesc = $orderSortDesc;
        $this->paginate = $paginate;
        $this->itemsPerPage = $itemsPerPage;
        $this->page = $page;
        $this->published = $published;
    }

    public static function createFromRequest(Request $request): self
    {
        $params = $request->all();

        $orderBy = $params['sortBy'] ?? 'id';
        $orderSortDesc = (int)($params['sortDesc'] ?? 0) === 1;
        $paginate = (int)($params['paginate'] ?? 1) === 1;
        $itemsPerPage = (isset($params['itemsPerPage'])) ? (int)$params['itemsPerPage'] : null;
        $page = (int)($params['page'] ?? 1);
        $published = (bool)($params['published'] ?? false);

        return new self($orderBy, $orderSortDesc, $paginate && $itemsPerPage > 0, $itemsPerPage, $page, $published);
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
            $this->getPublished() ? 1 : 0,
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

    public function getPublished(): bool
    {
        return $this->published;
    }
}
