<?php
declare(strict_types=1);

namespace App\Models\Questionnaire;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $works_package_id
 * @property int $user_id
 * @property int $answer_user_id
 * @property string $question
 * @property string $answer
 * @property Carbon $answered_at
 * @method static orderBy($a, $b)
 * @method static create($a)
 */
class WorksPackageQuestion extends Model
{
    protected $table = 'works_package_questions';

    protected $guarded = [];

    public function worksPackage(): BelongsTo
    {
        return $this->belongsTo(WorksPackage::class);
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getWorksPackageId(): int
    {
        return $this->works_package_id;
    }

    public function getUserId(): int
    {
        return $this->user_id;
    }

    public function getAnswerUserId(): ?int
    {
        return $this->answer_user_id;
    }

    public function getQuestion(): string
    {
        return $this->question;
    }

    public function getAnswer(): string
    {
        return $this->answer;
    }

    public function getAnsweredAt(): ?Carbon
    {
        return $this->answered_at;
    }

    public function setQuestion(string $question): self
    {
        $this->question = $question;

        return $this;
    }

    public function setAnswer(string $answer): self
    {
        $this->answer = $answer;

        return $this;
    }

    public function setAnsweredAt(Carbon $answeredAt): self
    {
        $this->answered_at = $answeredAt;

        return $this;
    }

    public function setAnswerUserId(int $answerUserId): self
    {
        $this->answer_user_id = $answerUserId;

        return $this;
    }

//    public function toArray(): array
//    {
//        return [
//            'id' => $this->id,
//            'works_package_id' => $this->works_package_id,
//            'user_id' => $this->user_id,
//            'answer_user_id' => $this->answer_user_id,
//            'question' => $this->question,
//            'answer' => $this->answer
//        ];
//    }
}
