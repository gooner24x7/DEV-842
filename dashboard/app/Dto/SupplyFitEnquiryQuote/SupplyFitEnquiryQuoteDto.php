<?php
declare(strict_types=1);

namespace App\Dto\SupplyFitEnquiryQuote;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

class SupplyFitEnquiryQuoteDto
{
    private float $price;
    private string $comment;
    private string $offers;
    private string $type;
    private bool $resourcesAvailable;
    private bool $isCompetent;

    /** @var UploadedFile[] */
    private array $uploadedFile;

    public function __construct(
        float $price,
        string $comment,
        string $offers,
        string $type,
        bool $resourcesAvailable,
        bool $isCompetent,
        array $uploadedFile
    ) {
        $this->price = $price;
        $this->comment = $comment;
        $this->offers = $offers;
        $this->type = $type;
        $this->resourcesAvailable = $resourcesAvailable;
        $this->isCompetent = $isCompetent;
        $this->uploadedFile = $uploadedFile;
    }

    public static function createFromRequest(Request $request): self
    {
        $data = $request->validate([
            'price' => 'required',
            'comment' => 'required',
            'offers' => '',
            'type' => '',
            'resources_available' => '',
            'is_competent' => '',
            'attachment' => '',
        ]);

        return new self(
            (float)($data['price'] ?? 0),
            $data['comment'] ?? '',
            $data['offers'] ?? '',
            $data['type'] ?? '',
            (bool)($data['resources_available'] ?? false),
            (bool)($data['is_competent'] ?? false),
            ($data['attachment'] ?? [])
        );
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getPrice(): float
    {
        return $this->price;
    }

    public function getComment(): string
    {
        return $this->comment;
    }

    public function getOffers(): string
    {
        return $this->offers;
    }

    public function getResourcesAvailable(): bool
    {
        return $this->resourcesAvailable;
    }

    public function getIsCompetent(): bool
    {
        return $this->isCompetent;
    }

    public function getUploadedFile(): array
    {
        return $this->uploadedFile;
    }
}
