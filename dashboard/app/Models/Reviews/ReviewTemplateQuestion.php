<?php
declare(strict_types=1);

namespace App\Models\Reviews;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $section_id
 * @property string $question
 * @method static find($a)
 * @method static orderBy($a, $b)
 * @method static create($a)
 */
class ReviewTemplateQuestion extends Model
{
    protected $fillable = [
        'section_id',
        'question',
    ];
    protected $table = 'review_template_questions';

    protected $guarded = [];

    public $timestamps = false;

    public function section(): BelongsTo
    {
        return $this->belongsTo(ReviewTemplateSection::class, 'section_id', 'id');
    }

    public function options(): HasMany
    {
        return $this->hasMany(ReviewTemplateOption::class, 'question_id', 'id');
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getSectionId(): int
    {
        return $this->section_id;
    }

    public function getQuestion(): string
    {
        return $this->question;
    }
}
