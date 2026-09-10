<?php
declare(strict_types=1);

namespace App\Models;

use Bnb\Laravel\Attachments\HasAttachment;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property int $enquiry_id
 * @property int $type
 * @property float $price
 * @property string $comments
 * @property string $offers
 * @property Carbon $viewed_at
 * @property Carbon $accepted_at
 * @property array $attachments
 * @method static create(array $array)
 * @method static where(array $array)
 * @method static whereIn(string $col, array $array)
 * @method static whereNull(string $a)
 * @method static find(int $questionId)
 * @method static join($a, $b, $c, $d)
 * @method static select(...$a)
 * @method static selectRaw($a)
 */
class LogisticsQuote extends Model
{
    use HasAttachment;

    const array TYPES = [
        'Full',
        'Part'
    ];

    protected $guarded = [];
    protected $appends = [
        'attachments',
        'type_str',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function enquiry(): BelongsTo
    {
        return $this->belongsTo(LogisticsEnquiry::class, 'enquiry_id', 'id');
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getUserId(): int
    {
        return $this->user_id;
    }

    public function getEnquiryId(): int
    {
        return $this->enquiry_id;
    }

    public function getType(): int
    {
        return $this->type;
    }

    public function getTypeStrAttribute(): string
    {
        return self::TYPES[$this->type] ?? '';
    }

    public function getPrice(): float
    {
        return $this->price;
    }

    public function getComments(): string
    {
        return $this->comments;
    }

    public function getOffers(): string
    {
        return $this->offers;
    }

    public function getViewedAt(): Carbon
    {
        return $this->viewed_at;
    }

    public function getAcceptedAt(): Carbon
    {
        return $this->accepted_at;
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

    public function setUserId(int $userId): self
    {
        $this->user_id = $userId;

        return $this;
    }

    public function setEnquiryId(int $enquiryId): self
    {
        $this->enquiry_id = $enquiryId;

        return $this;
    }

    public function setType(int $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function setPrice(float $price): self
    {
        $this->price = $price;

        return $this;
    }

    public function setComments(string $comments): self
    {
        $this->comments = $comments;

        return $this;
    }

    public function setOffers(string $offers): self
    {
        $this->offers = $offers;

        return $this;
    }

    public function setViewedAt(Carbon $viewed_at): self
    {
        $this->viewed_at = $viewed_at;

        return $this;
    }

    public function setAcceptedAt(Carbon $accepted_at): self
    {
        $this->accepted_at = $accepted_at;

        return $this;
    }
}
