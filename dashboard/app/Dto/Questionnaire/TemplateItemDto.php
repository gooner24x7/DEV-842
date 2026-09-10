<?php
declare(strict_types=1);

namespace App\Dto\Questionnaire;

use Illuminate\Http\Request;

class TemplateItemDto
{
    private string $text;
    private string $type;
    private int $scoreYes;
    private int $scoreNo;

    public function __construct(
        string $text,
        string $type,
        int    $scoreYes,
        int    $scoreNo
    )
    {
        $this->text = $text;
        $this->type = $type;
        $this->scoreNo = $scoreNo;
        $this->scoreYes = $scoreYes;
    }

    public static function createFromRequest(Request $request): self
    {
        $data = $request->validate([
            'text' => 'required',
            'type' => 'required',
            'score_yes' => 'required',
            'score_no' => 'required',
        ]);

        return self::createFromArray($data);
    }

    public static function createFromArray(array $data): self
    {
        return new self(
            (string)($data['text']),
            (string)($data['type']),
            (int)($data['score_yes']),
            (int)($data['score_no'])
        );
    }

    public function getText(): string
    {
        return $this->text;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getScoreYes(): int
    {
        return $this->scoreYes;
    }

    public function getScoreNo(): int
    {
        return $this->scoreNo;
    }

    public function toTemplateArray(): array
    {
        return [
            'text' => $this->text,
            'type' => $this->type,
            'score_yes' => $this->scoreYes,
            'score_no' => $this->scoreNo
        ];
    }
}
