<?php
declare(strict_types=1);

namespace App\Dto\Question;

use App\Repository\QuestionRepository;
use Illuminate\Http\Request;

class SearchParamsDto
{
    private bool $paginate;
    private bool $orderSortDesc;
    private ?string $orderBy;
    private ?int $itemsPerPage;
    private int $page;
    /** @var string[]|null */
    private ?array $worksPackageIds;
    private ?array $productIds;
    private ?array $projectIds;
    private bool $archived;
    private ?int $id;
    private ?string $isQuoted;
    private ?string $isIgnored;
    private ?string $isQuotedByBranch;

    private ?bool $isAnswered;

    public function __construct(
        ?string $orderBy,
        bool    $orderSortDesc,
        bool    $paginate,
        ?array  $worksPackageIds,
        ?array  $productIds,
        ?array  $projectIds,
        bool    $archived,
        ?int    $itemsPerPage = null,
        int     $page = 1,
        ?int    $id = null,
        ?string $isQuoted = null,
        ?string $isIgnored = null,
        ?string $isQuotedByBranch = null,
        ?bool   $isAnswered = null
    ) {
        $this->orderBy = $orderBy;
        $this->orderSortDesc = $orderSortDesc;
        $this->paginate = $paginate;
        $this->itemsPerPage = $itemsPerPage;
        $this->page = $page;
        $this->worksPackageIds = $worksPackageIds;
        $this->productIds = $productIds;
        $this->archived = $archived;
        $this->id = $id;
        $this->isQuoted = $isQuoted;
        $this->isIgnored = $isIgnored;
        $this->isQuotedByBranch = $isQuotedByBranch;
        $this->isAnswered = $isAnswered;
        $this->projectIds = $projectIds;
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
        $productIds = $params['productIds'] ?? null;
        $archived = isset($params['archived']) && $params['archived'] == 1;
        $id = $params['id'] ?? null;
        $isQuoted = $params['isQuoted'] ?? null;
        $isIgnored = $params['isIgnored'] ?? null;
        $isQuotedByBranch = $params['isQuotedByBranch'] ?? null;
        $isAnswered = $params['isAnswered'] ?? null;
        if ($isAnswered) {
            $isAnswered = $isAnswered == 'true';
        }

        return new self(
            $orderBy,
            $orderSortDesc,
            $paginate && $itemsPerPage > 0,
            $worksPackageIds,
            $productIds,
            $projectIds,
            $archived,
            $itemsPerPage,
            $page,
            $id ? (int)$id : null,
            $isQuoted,
            $isIgnored,
            $isQuotedByBranch,
            $isAnswered
        );
    }

    public function getHash(): string
    {
        return sprintf(
            '%s_%d_%d_%d_%d_%s_%s_%d_%d_%d_%s_%s_%s_%d_%s',
            $this->getOrderBy() ?? '',
            $this->getOrderSortDesc() ? 1 : 0,
            $this->isPaginationEnabled() ? 1 : 0,
            $this->getItemsPerPage() ?? 0,
            $this->getPage(),
            $this->getWorksPackageIds() ? implode(',', $this->getWorksPackageIds()) : '',
            $this->getProductIds() ? implode(',', $this->getProductIds()) : '',
            $this->getArchived() ? 1 : 0,
            $this->getId() ?? 0,
            QuestionRepository::SEARCH_RADIUS,
            $this->getIsQuoted(),
            $this->getIsIgnored(),
            $this->getIsQuotedByBranch(),
            $this->getIsAnswered(),
            $this->getProjectIds() ? implode(',', $this->getProjectIds()) : '',
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

    public function getWorksPackageIds(): ?array
    {
        return $this->worksPackageIds;
    }

    public function getProductIds(): ?array
    {
        return $this->productIds;
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

    public function getIsQuotedByBranch(): ?string
    {
        return $this->isQuotedByBranch;
    }

    public function getIsAnswered(): ?bool
    {
        return $this->isAnswered;
    }

    public function getProjectIds(): ?array
    {
        return $this->projectIds;
    }
}
