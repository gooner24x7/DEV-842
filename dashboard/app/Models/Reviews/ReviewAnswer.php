<?php
declare(strict_types=1);

namespace App\Models\Reviews;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $review_id
 * @property int $question_id
 * @property int $option_id
 * @property string $comment
 * @method static find($a)
 * @method static orderBy($a, $b)
 * @method static create($a)
 */
class ReviewAnswer extends Model
{
    protected $table = 'review_answers';

    protected $fillable = [
        'review_id',
        'question_id',
        'option_id',
        'comment',
    ];

    protected $guarded = [];

    public $timestamps = false;

    public function review(): BelongsTo
    {
        return $this->belongsTo(Review::class, 'review_id', 'id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(ReviewTemplateQuestion::class, 'question_id', 'id');
    }

    public function option(): BelongsTo
    {
        return $this->belongsTo(ReviewTemplateOption::class, 'option_id', 'id');
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getReviewId(): int
    {
        return $this->review_id;
    }

    public function getQuestionId(): int
    {
        return $this->question_id;
    }

    public function getOptionId(): int
    {
        return $this->option_id;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }
}
