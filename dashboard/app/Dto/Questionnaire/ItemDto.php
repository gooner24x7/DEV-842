<?php
declare(strict_types=1);

namespace App\Dto\Questionnaire;

use Illuminate\Http\Request;

class ItemDto
{
    private string $text;
    private string $type;
    private int $scoreYes;
    private int $scoreNo;
    private int $userId;

    private int $worksPackageId;

    public function __construct(
        int    $userId,
        string $text,
        string $type,
        int    $scoreYes,
        int    $scoreNo,
        int    $worksPackageId
    ) {
        $this->userId = $userId;
        $this->text = $text;
        $this->type = $type;
        $this->scoreNo = $scoreNo;
        $this->scoreYes = $scoreYes;
        $this->worksPackageId = $worksPackageId;
    }

    public static function createFromRequest(Request $request, int $userId): self
    {
        $data = $request->validate([
            'text' => 'required',
            'type' => 'required',
            'score_yes' => 'required',
            'score_no' => 'required',
            'worksPackageId' => 'required',
        ]);

        return self::createFromArray($data, $userId);
    }

    public static function createFromArray(array $data, int $userId): self
    {
        return new self(
            $userId,
            (string)($data['text']),
            (string)($data['type']),
            (int)($data['score_yes']),
            (int)($data['score_no']),
            (int)($data['worksPackageId']),
        );
    }

    public function getUserId(): int
    {
        return $this->userId;
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

    public function getWorksPackageId(): int
    {
        return $this->worksPackageId;
    }
}
