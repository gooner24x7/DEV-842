<?php
declare(strict_types=1);

namespace App\Dto\Message;

use Illuminate\Http\Request;

class MessageDto
{
    private int $userId;
    private ?int $aid = null;
    private ?int $virtualExpoSharedContactId = null;
    private string $message = '';
    private ?int $supplyFitEnquiryQuoteId = null;
    private ?int $questionId = null;
    private ?int $interlocutorId = null;
    private ?int $supplyFitEnquiryId = null;
    private ?int $logisticsEnquiryId = null;
    private ?int $logisticsQuoteId = null;

    public function __construct(string $message)
    {
        $this->message = $message;
    }

    public static function createFromRequest(Request $request): self
    {
        $data = $request->validate([
            'message' => 'required'
        ]);

        return new self(
            $data['message'] ?? ''
        );
    }

    public function getInterlocutorId(): ?int
    {
        return $this->interlocutorId;
    }

    public function setInterlocutorId(int $id): self
    {
        $this->interlocutorId = $id;
        return $this;
    }

    public function setSupplyFitEnquiryQuote(int $id): self
    {
        $this->supplyFitEnquiryQuoteId = $id;

        return $this;
    }

    public function getSupplyFitEnquiryQuoteId(): ?int
    {
        return $this->supplyFitEnquiryQuoteId;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getAid(): ?int
    {
        return $this->aid;
    }

    public function setAid(int $aid): self
    {
        $this->aid = $aid;

        return $this;
    }

    public function getVirtualExpoSharedContactId(): ?int
    {
        return $this->virtualExpoSharedContactId;
    }

    public function setVirtualExpoSharedContactId(int $id): self
    {
        $this->virtualExpoSharedContactId = $id;

        return $this;
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

    public function getQuestionId(): ?int
    {
        return $this->questionId;
    }

    public function setQuestionId(int $id): self
    {
        $this->questionId = $id;

        return $this;
    }

    public function getSupplyFitEnquiryId(): ?int
    {
        return $this->supplyFitEnquiryId;
    }

    public function setSupplyFitEnquiryId(int $enquiryId): self
    {
        $this->supplyFitEnquiryId = $enquiryId;
        return $this;
    }

    public function getLogisticsEnquiryId(): ?int
    {
        return $this->logisticsEnquiryId;
    }

    public function getLogisticsQuoteId(): ?int
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
