<?php
declare(strict_types=1);

namespace App\Dto\Questionnaire;

use Illuminate\Http\Request;

class ReplyDto
{
    private string $text;
    private bool $isYes;
    private int $sessionId;
    private int $itemId;
    private int $score;

    public function __construct(
        string $text,
        bool   $isYes,
        int    $sessionId,
        int    $itemId,
        int    $score
    )
    {
        $this->text = $text;
        $this->isYes = $isYes;
        $this->sessionId = $sessionId;
        $this->itemId = $itemId;
        $this->score = $score;
    }

    public static function createFromRequest(Request $request): self
    {
        $data = $request->validate([
            'text' => '',
            'is_yes' => '',
            'session_id' => 'required',
            'item_id' => 'required',
            'score' => '',
        ]);

        return self::createFromArray($data);
    }

    public static function createFromArray(array $data): self
    {
        return new self(
            (string)($data['text_answer'] ?? ''),
            (bool)($data['is_yes'] === 'Y'),
            (int)($data['session_id']),
            (int)($data['item_id']),
            (int)($data['score'] ?? 0)
        );
    }

    public function getIsYes(): bool
    {
        return $this->isYes;
    }

    public function getText(): string
    {
        return $this->text;
    }

    public function getSessionId(): int
    {
        return $this->sessionId;
    }

    public function getItemId(): int
    {
        return $this->itemId;
    }

    public function getScore(): int
    {
        return $this->score;
    }
}
