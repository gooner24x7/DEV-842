<?php
declare(strict_types=1);

namespace App\Models\Reviews;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $user_id
 * @property int $quote_id
 * @property int $template_id
 * @property int $status
 * @property string|null $comment
 * @property string $completed_at
 * @property string $status_str
 * @method static find($a)
 * @method static orderBy($a, $b)
 * @method static create($a)
 */
class Review extends Model
{
    protected $table = 'reviews';

    protected $fillable = [
        'user_id',
        'quote_id',
        'template_id',
        'status',
        'comment',
    ];

    protected $guarded = [];

    protected $casts = [
        'created_at' => 'datetime:d-m-Y H:i',
        'updated_at' => 'datetime:d-m-Y H:i',
        'completed_at' => 'datetime:d-m-Y H:i',
    ];

    protected $appends = [
        'status_str'
    ];

    public const array STATUSES = [
        1 => 'Pending',
        2 => 'Completed',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(ReviewTemplate::class, 'template_id', 'id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(ReviewAnswer::class, 'review_id', 'id');
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getUserId(): int
    {
        return $this->user_id;
    }

    public function getQuoteId(): int
    {
        return $this->quote_id;
    }

    public function getTemplateId(): int
    {
        return $this->template_id;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function getCompletedAt(): ?string
    {
        return $this->completed_at;
    }

    public function getStatusStrAttribute(): string
    {
        return self::STATUSES[$this->status] ?? '';
    }
}
