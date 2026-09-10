<?php
declare(strict_types=1);

namespace App\Models\Reviews;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $question_id
 * @property string $text
 * @property int $score
 * @method static find($a)
 * @method static orderBy($a, $b)
 * @method static create($a)
 */
class ReviewTemplateOption extends Model
{
    protected $fillable = [
        'question_id',
        'text',
        'score',
    ];
    protected $table = 'review_template_options';

    protected $guarded = [];

    public $timestamps = false;

    public function question(): BelongsTo
    {
        return $this->belongsTo(ReviewTemplateQuestion::class, 'question_id', 'id');
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getQuestionId(): int
    {
        return $this->question_id;
    }

    public function getText(): string
    {
        return $this->text;
    }

    public function getScore(): int
    {
        return $this->score;
    }
}
