<?php
declare(strict_types=1);

namespace App\Dto\QuoteProgress;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

class QuoteProgressDto
{
    private int $quoteId;
    private int $type;
    private Carbon $date;
    private string $comment;

    /** @var UploadedFile[] */
    private array $uploadedFile;

    public function __construct(int $quoteId, int $type, Carbon $date, string $comment, array $uploadedFile)
    {
        $this->quoteId = $quoteId;
        $this->type = $type;
        $this->date = $date;
        $this->comment = $comment;
        $this->uploadedFile = $uploadedFile;
    }

    public static function createFromRequest(Request $request): self
    {
        $data = $request->validate([
            'quoteId' => 'required',
            'type' => 'required',
            'date' => 'required',
            'comment' => '',
            'attachment' => '',
        ]);

        return new self(
            (int) $data['quoteId'],
            (int) $data['type'],
            Carbon::createFromFormat('d-m-Y', $data['date']),
            $data['comment'] ?? '',
            $data['attachment'] ?? []
        );
    }

    public function getQuoteId(): int
    {
        return $this->quoteId;
    }

    public function getType(): int
    {
        return $this->type;
    }

    public function getDate(): Carbon
    {
        return $this->date;
    }

    public function getComment(): string
    {
        return $this->comment;
    }

    public function getUploadedFile(): array
    {
        return $this->uploadedFile;
    }
}
