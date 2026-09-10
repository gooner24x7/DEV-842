<?php
declare(strict_types=1);

namespace App\Dto\Answer;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

class AnswerDto
{
    private float $price;
    private string $comment;
    private string $offers;
    private string $type;
    private bool $hasSubstitution;

    /** @var UploadedFile[] */
    private array $uploadedFile;

    public function __construct(
        float $price,
        string $comment,
        string $offers,
        string $type,
        bool $hasSubstitution,
        array $uploadedFile
    ) {
        $this->price = $price;
        $this->comment = $comment;
        $this->offers = $offers;
        $this->type = $type;
        $this->hasSubstitution = $hasSubstitution;
        $this->uploadedFile = $uploadedFile;
    }

    public static function createFromRequest(Request $request): self
    {
        $data = $request->validate([
            'price' => 'required',
            'comment' => 'required',
            'offers' => '',
            'type' => 'required',
            'has_substitution' => '',
            'attachment' => '',
        ]);

        return new self(
            (float)($data['price'] ?? 0),
            $data['comment'] ?? '',
            $data['offers'] ?? '',
            $data['type'] ?? 'full',
            (bool)($data['has_substitution'] ?? false),
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

    public function getHasSubstitution(): bool
    {
        return $this->hasSubstitution;
    }

    public function getUploadedFile(): array
    {
        return $this->uploadedFile;
    }
}
