<?php
declare(strict_types=1);

namespace App\Models;

use Bnb\Laravel\Attachments\HasAttachment;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $quote_id
 * @property int $user_id
 * @property int $type
 * @property string $comment
 * @property Carbon $date
 * @property Carbon $completed_at
 * @property array $attachments
 * @method static create(array $array)
 * @method static where(array $array)
 * @method static whereIn(string $col, array $array)
 * @method static whereNull(string $a)
 * @method static find(int $id)
 * @method static join($a, $b, $c, $d)
 * @method static select(...$a)
 * @method static selectRaw($a)
 */
class QuoteProgress extends Model
{
    use HasAttachment;

    const array TYPES = [
        'Answer',
        'SupplyFitEnquiryQuote',
        'LogisticsQuote'
    ];

    protected $guarded = [];
    protected $appends = [
        'type_str',
        'attachments'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function quote(): BelongsTo
    {
        $classRef = $this->getTypeStrAttribute() . '::class';
        return $this->belongsTo($classRef, 'quote_id', 'id');
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getQuoteId(): int
    {
        return $this->quote_id;
    }

    public function getUserId(): int
    {
        return $this->user_id;
    }

    public function getType(): int
    {
        return $this->type;
    }

    public function getTypeStrAttribute(): string
    {
        return self::TYPES[$this->type] ?? '';
    }

    public function getComment(): string
    {
        return $this->comment;
    }

    public function getDate(): Carbon
    {
        return $this->date;
    }

    public function getCompletedAt(): ?Carbon
    {
        return $this->completed_at;
    }

    public function getAttachmentsAttribute(): array
    {
        $attachments = $this->attachments()->get();
        $attachmentsArray = [];

        foreach($attachments as $attachment) {
            $attachmentsArray[] = [
                'id' => $attachment->id,
                'name' => $attachment->filename,
                'url' => $attachment->url,
                'description' => $attachment->description
            ];
        }

        return $attachmentsArray;
    }

    public function setType(int $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function setComment(string $comment): self
    {
        $this->comment = $comment;

        return $this;
    }

    public function setDate(Carbon $date): self
    {
        $this->date = $date;

        return $this;
    }

    public function setCompletedAt(?Carbon $completed_at): self
    {
        $this->completed_at = $completed_at;

        return $this;
    }
}
