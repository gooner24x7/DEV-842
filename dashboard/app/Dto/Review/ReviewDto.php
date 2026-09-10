<?php
declare(strict_types=1);

namespace App\Dto\Review;

use Illuminate\Http\Request;

class ReviewDto
{
    private int $quoteId;
    private int $templateId;
    private ?string $comment;
    private array $sections;

    public function __construct(
        int    $quoteId,
        int    $templateId,
        ?string $comment,
        array  $sections
    ) {
        $this->quoteId = $quoteId;
        $this->templateId = $templateId;
        $this->comment = $comment;
        $this->sections = $sections;
    }

    public static function createFromRequest(Request $request): self
    {
        $data = $request->validate([
            'quote_id' => 'required|integer',
            'template_id' => 'required|integer',
            'comment' => '',
            'sections' => '',
        ]);

        return self::createFromArray($data);
    }

    public static function createFromArray(array $data): self
    {
        return new self(
            $data['quote_id'],
            $data['template_id'],
            $data['comment'] ?? null,
            $data['sections'] ?? [],
        );
    }

    public function getQuoteId(): int
    {
        return $this->quoteId;
    }

    public function getTemplateId(): int
    {
        return $this->templateId;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function getSections(): array
    {
        return $this->sections;
    }
}
