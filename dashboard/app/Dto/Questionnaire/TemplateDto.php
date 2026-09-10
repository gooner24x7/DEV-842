<?php
declare(strict_types=1);

namespace App\Dto\Questionnaire;

use Illuminate\Http\Request;

class TemplateDto
{
    /** @var TemplateItemDto[] */
    private array $questions;

    public function __construct(array $questions)
    {
        $this->questions = $questions;
    }

    public static function createFromRequest(Request $request): self
    {
        $data = $request->validate([
            'questions' => 'present|array',
            'questions.*.text' => 'required',
            'questions.*.type' => 'required',
            'questions.*.score_yes' => 'required',
            'questions.*.score_no' => 'required',
        ]);

        $questions = array_map(function (array $item) {
            return TemplateItemDto::createFromArray($item);
        }, $data['questions'] ?? []);

        return new self($questions);
    }

    public function getQuestions(): array
    {
        return $this->questions;
    }
}
