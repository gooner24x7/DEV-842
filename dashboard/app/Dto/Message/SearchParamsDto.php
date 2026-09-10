<?php
declare(strict_types=1);

namespace App\Dto\Message;

use Illuminate\Http\Request;

class SearchParamsDto
{
    private bool $paginate;
    private bool $orderSortDesc;
    private ?string $orderBy;
    private ?int $itemsPerPage;
    private int $page;
    private int $userId;
    private int $aid = 0;
    private int $virtualExpoSharedContactId = 0;
    private int $supplyFitEnquiryQuoteId = 0;
    private int $supplyFitEnquiryId = 0;
    private int $questionId = 0;
    private int $requestUserId = 0;
    private int $logisticsEnquiryId = 0;
    private int $logisticsQuoteId = 0;

    public function __construct(?string $orderBy, bool $orderSortDesc, bool $paginate, ?int $itemsPerPage = null, int $page = 1)
    {
        $this->orderBy = $orderBy;
        $this->orderSortDesc = $orderSortDesc;
        $this->paginate = $paginate;
        $this->itemsPerPage = $itemsPerPage;
        $this->page = $page;
    }

    public static function createFromRequest(Request $request): self
    {
        $params = $request->all();

        $orderBy = $params['sortBy'] ?? null;
        $orderSortDesc = (int)($params['sortDesc'] ?? 0) === 1;
        $paginate = (int)($params['paginate'] ?? 1) === 1;
        $itemsPerPage = isset($params['itemsPerPage']) ? (int)$params['itemsPerPage'] : null;
        $page = (int)($params['page'] ?? 1);

        $parametersDto = new self($orderBy, $orderSortDesc, $paginate && $itemsPerPage > 0, $itemsPerPage, $page);
        $parametersDto->requestUserId = (int)($params['userId'] ?? 0);

        return $parametersDto;
    }

    public function getRequestUserId(): int
    {
        return $this->requestUserId;
    }

    public function setSupplyFitEnquiryQuote(int $id): self
    {
        $this->supplyFitEnquiryQuoteId = $id;

        return $this;
    }

    public function getHash(): string
    {
        return sprintf(
            '%s_%d_%d_%d_%d_%d_%d_%d_%d_%d_%d',
            $this->getOrderBy() ?? '',
            $this->getOrderSortDesc() ? 1 : 0,
            $this->isPaginationEnabled() ? 1 : 0,
            $this->getItemsPerPage() ?? 0,
            $this->getPage(),
            $this->getUserId(),
            $this->getAid(),
            $this->getVirtualExpoSharedContactId(),
            $this->getQuestionId(),
            $this->getSupplyFitEnquiryQuoteId(),
            $this->getSupplyFitEnquiryId(),
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

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function setUserId(int $userId): self
    {
        $this->userId = $userId;

        return $this;
    }

    public function getAid(): int
    {
        return $this->aid;
    }

    public function setAid(int $aid): self
    {
        $this->aid = $aid;

        return $this;
    }

    public function getVirtualExpoSharedContactId(): int
    {
        return $this->virtualExpoSharedContactId;
    }

    public function setVirtualExpoSharedContactId(int $id): self
    {
        $this->virtualExpoSharedContactId = $id;

        return $this;
    }

    public function getQuestionId(): int
    {
        return $this->questionId;
    }

    public function setQuestionId(int $questionId): self
    {
        $this->questionId = $questionId;

        return $this;
    }

    public function getSupplyFitEnquiryQuoteId(): int
    {
        return $this->supplyFitEnquiryQuoteId;
    }

    public function getSupplyFitEnquiryId(): int
    {
        return $this->supplyFitEnquiryId;
    }

    public function setSupplyFitEnquiryId(int $enquiryId): self
    {
        $this->supplyFitEnquiryId = $enquiryId;
        return $this;
    }

    public function getLogisticsEnquiryId(): int
    {
        return $this->logisticsEnquiryId;
    }

    public function getLogisticsQuoteId(): int
    {
        return $this->logisticsQuoteId;
    }

    public function setLogisticsEnquiryId(int $enquiryId): self
    {
        $this->logisticsEnquiryId = $enquiryId;
        return $this;
    }

    public function setLogisticsQuoteId(int $quoteId): self
    {
        $this->logisticsQuoteId = $quoteId;
        return $this;
    }
}
