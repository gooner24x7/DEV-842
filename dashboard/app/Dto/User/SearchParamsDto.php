<?php
declare(strict_types=1);

namespace App\Dto\User;

use Illuminate\Http\Request;

class SearchParamsDto
{
    private bool $paginate;
    private bool $orderSortDesc;
    private ?string $orderBy;
    private ?int $itemsPerPage;
    private int $page;
    private ?string $search;
    private ?string $region;
    private ?string $certType;
    private ?int $certStatus;
    private ?string $project;
    private ?string $worksPackage;
    private ?int $radius;
    private ?array $products;

    public function __construct(
        ?string $orderBy,
        bool $orderSortDesc,
        bool $paginate,
        ?int $itemsPerPage = null,
        int $page = 1,
        ?string $search = null,
        ?string $region = null,
        ?string $certType = null,
        ?int $certStatus = null,
        ?string $project = null,
        ?string $worksPackage = null,
        ?int $radius = null,
        ?array $products = null
    ) {
        $this->orderBy = $orderBy;
        $this->orderSortDesc = $orderSortDesc;
        $this->paginate = $paginate;
        $this->itemsPerPage = $itemsPerPage;
        $this->page = $page;
        $this->search = $search;
        $this->region = $region;
        $this->certType = $certType;
        $this->certStatus = $certStatus;
        $this->project = $project;
        $this->worksPackage = $worksPackage;
        $this->radius = $radius;
        $this->products = $products;
    }

    public static function createFromRequest(Request $request): self
    {
        $params = $request->all();

        $orderBy = $params['sortBy'] ?? null;
        $orderSortDesc = (int)($params['sortDesc'] ?? 0) === 1;
        $paginate = (int)($params['paginate'] ?? 1) === 1;
        $itemsPerPage = (isset($params['itemsPerPage'])) ? (int)$params['itemsPerPage'] : null;
        $page = (int)($params['page'] ?? 1);
        $search = $params['search'] ?? null;
        $region = $params['region'] ?? null;
        $certType = $params['certType'] ?? null;
        $certStatus = isset($params['certStatus']) ? (int) $params['certStatus'] : null;
        $project = $params['project'] ?? null;
        $worksPackage = $params['worksPackage'] ?? null;
        $radius = !empty($params['radius']) ? (int) $params['radius'] : null;
        $products = $params['products'] ?? [];

        return new self(
            $orderBy,
            $orderSortDesc,
            $paginate && $itemsPerPage > 0,
            $itemsPerPage,
            $page,
            $search,
            $region,
            $certType,
            $certStatus,
            $project,
            $worksPackage,
            $radius,
            $products,
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

    public function getSearch(): ?string
    {
        return $this->search;
    }

    public function getRegion(): ?string
    {
        return $this->region;
    }

    public function getCertType(): ?string
    {
        return $this->certType;
    }

    public function getCertStatus(): ?int
    {
        return $this->certStatus;
    }

    public function getProject(): ?string
    {
        return $this->project;
    }

    public function getWorksPackage(): ?string
    {
        return $this->worksPackage;
    }

    public function getRadius(): ?int
    {
        return $this->radius;
    }

    public function getProducts(): ?array
    {
        return $this->products;
    }
}
