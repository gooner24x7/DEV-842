<?php
declare(strict_types=1);

namespace App\Dto\Logistics;

use App\Repository\LogisticsEnquiryRepository;
use Illuminate\Http\Request;

class SearchParamsDto
{
    private bool $paginate;
    private bool $orderSortDesc;
    private ?string $orderBy;
    private ?int $itemsPerPage;
    private int $page;
    private ?array $projectIds;
    private ?array $worksPackageIds;
    private bool $archived;
    private ?int $id;
    private ?string $isQuoted;
    private ?string $isIgnored;

    public function __construct(
        ?string     $orderBy,
        bool        $orderSortDesc,
        bool        $paginate,
        ?array      $projectIds,
        ?array      $worksPackageIds,
        bool        $archived,
        ?int        $itemsPerPage = null,
        int         $page = 1,
        ?int        $id = null,
        ?string    $isQuoted = null,
        ?string    $isIgnored = null
    ) {
        $this->orderBy = $orderBy;
        $this->orderSortDesc = $orderSortDesc;
        $this->paginate = $paginate;
        $this->itemsPerPage = $itemsPerPage;
        $this->page = $page;
        $this->projectIds = $projectIds;
        $this->worksPackageIds = $worksPackageIds;
        $this->archived = $archived;
        $this->id = $id;
        $this->isQuoted = $isQuoted;
        $this->isIgnored = $isIgnored;
    }

    public static function createFromRequest(Request $request): self
    {
        $params = $request->all();

        $orderBy = $params['sortBy'] ?? null;
        $orderSortDesc = (int)($params['sortDesc'] ?? 0) === 1;
        $paginate = (int)($params['paginate'] ?? 1) === 1;
        $itemsPerPage = (isset($params['itemsPerPage'])) ? (int)$params['itemsPerPage'] : null;
        $page = (int)($params['page'] ?? 1);
        $projectIds = $params['projectIds'] ?? null;
        $worksPackageIds = $params['worksPackageIds'] ?? null;
        $archived = isset($params['archived']) && $params['archived'] == 1;
        $id = $params['id'] ?? null;
        $isQuoted = $params['isQuoted'] ?? null;
        $isIgnored = $params['isIgnored'] ?? null;

        return new self(
            $orderBy,
            $orderSortDesc,
            $paginate && $itemsPerPage > 0,
            $projectIds,
            $worksPackageIds,
            $archived,
            $itemsPerPage,
            $page,
            $id ? (int)$id : null,
            $isQuoted,
            $isIgnored
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

    public function getProjectIds(): ?array
    {
        return $this->projectIds;
    }

    public function getWorksPackageIds(): ?array
    {
        return $this->worksPackageIds;
    }

    public function getArchived(): bool
    {
        return $this->archived;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIsQuoted(): ?string
    {
        return $this->isQuoted;
    }

    public function getIsIgnored(): ?string
    {
        return $this->isIgnored;
    }
}
