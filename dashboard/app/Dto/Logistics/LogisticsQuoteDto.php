<?php
declare(strict_types=1);

namespace App\Dto\Logistics;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

class LogisticsQuoteDto
{
    private int $type;
    private float $price;
    private string $comments;
    private string $offers;

    /** @var UploadedFile[] */
    private array $uploadedFile;

    public function __construct(int $type, float $price, string $comment, string $offers, array $uploadedFile)
    {
        $this->type = $type;
        $this->price = $price;
        $this->comments = $comment;
        $this->offers = $offers;
        $this->uploadedFile = $uploadedFile;
    }

    public static function createFromRequest(Request $request): self
    {
        $data = $request->validate([
            'type' => 'required',
            'price' => 'required',
            'comments' => '',
            'offers' => '',
            'attachment' => '',
        ]);

        return new self(
            (int) $data['type'],
            (float)($data['price'] ?? 0),
            $data['comments'] ?? '',
            $data['offers'] ?? '',
            $data['attachment'] ?? []
        );
    }

    public function getType(): int
    {
        return $this->type;
    }

    public function getPrice(): float
    {
        return $this->price;
    }

    public function getComments(): string
    {
        return $this->comments;
    }

    public function getOffers(): string
    {
        return $this->offers;
    }

    public function getUploadedFile(): array
    {
        return $this->uploadedFile;
    }
}
