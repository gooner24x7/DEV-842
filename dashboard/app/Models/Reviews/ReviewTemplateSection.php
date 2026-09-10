<?php
declare(strict_types=1);

namespace App\Models\Reviews;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $template_id
 * @property string $title
 * @property string $description
 * @method static find($a)
 * @method static orderBy($a, $b)
 * @method static create($a)
 */
class ReviewTemplateSection extends Model
{
    protected $fillable = [
        'template_id',
        'title',
        'description',
    ];
    protected $table = 'review_template_sections';

    protected $guarded = [];

    public $timestamps = false;

    public function template(): BelongsTo
    {
        return $this->belongsTo(ReviewTemplate::class, 'template_id', 'id');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(ReviewTemplateQuestion::class, 'section_id', 'id');
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getTemplateId(): int
    {
        return $this->template_id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }
}
