<?php
declare(strict_types=1);

namespace App\Dto\Project;

use Carbon\Carbon;
use Illuminate\Http\Request;

class SearchParamsDto
{
    private bool $paginate;
    private int $page;
    private int $itemsPerPage;
    private ?string $orderBy;
    private bool $orderSortDesc;
    private ?string $search;
    private bool $archived;
    private ?int $limit;
    private ?Carbon $dateStartFrom;
    private ?Carbon $dateStartTo;
    private bool $showAll;
    private ?string $region;
    private ?int $type;

    public function __construct(
        bool $paginate,
        int $page,
        int $itemsPerPage,
        ?string $orderBy,
        bool $orderSortDesc,
        ?string $search,
        bool $archived,
        ?int $limit,
        ?Carbon $dateStartFrom,
        ?Carbon $dateStartTo,
        bool $showAll,
        ?string $region,
        ?int $type
    ) {
        $this->paginate = $paginate;
        $this->page = $page;
        $this->itemsPerPage = $itemsPerPage;
        $this->orderBy = $orderBy;
        $this->orderSortDesc = $orderSortDesc;
        $this->search = $search;
        $this->archived = $archived;
        $this->limit = $limit;
        $this->dateStartFrom = $dateStartFrom;
        $this->dateStartTo = $dateStartTo;
        $this->showAll = $showAll;
        $this->region = $region;
        $this->type = $type;
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
        $archived = !empty($params['archived']) && (bool)$params['archived'];
        $limit = !empty($params['limit']) ? (int) $params['limit'] : null;
        $dateStartFrom = self::createDateFromFormat($params['dateStartFrom'] ?? null);
        $dateStartTo = self::createDateFromFormat($params['dateStartTo'] ?? null);
        $showAll = !empty($params['showAll']) && (bool)$params['showAll'];
        $region = $params['region'] ?? null;
        $type = !empty($params['type']) ? (int) $params['type'] : null;

        return new self(
            $paginate && $itemsPerPage > 0,
            $page,
            $itemsPerPage,
            $orderBy,
            $orderSortDesc,
            $search,
            $archived,
            $limit,
            $dateStartFrom,
            $dateStartTo,
            $showAll,
            $region,
            $type
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

    public function getArchived(): bool
    {
        return $this->archived;
    }

    public function getLimit(): ?int
    {
        return $this->limit;
    }

    public function getDateStartFrom(): ?Carbon
    {
        return $this->dateStartFrom;
    }

    public function getDateStartTo(): ?Carbon
    {
        return $this->dateStartTo;
    }

    public function getShowAll(): bool
    {
        return $this->showAll;
    }

    public function getRegion(): ?string
    {
        return $this->region;
    }

    public function getType(): ?int
    {
        return $this->type;
    }

    private static function createDateFromFormat(?string $date, string $format = 'd-m-Y'): ?Carbon
    {
        if (empty($date)) {
            return null;
        }

        try {
            return Carbon::createFromFormat($format, $date);
        } catch (\Exception $e) {
            return null;
        }
    }
}
