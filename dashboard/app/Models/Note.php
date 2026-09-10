<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $parent_id
 * @property int $user_id
 * @property int $type
 * @property string $message
 * @method static create($a)
 * @method static select(...$a)
 */
class Note extends Model
{
    const array TYPES = [
        'QuoteProgress'
    ];

    protected $fillable = [
        'parent_id',
        'user_id',
        'message'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getParentId(): int
    {
        return $this->parent_id;
    }

    public function getUserId(): int
    {
        return $this->user_id;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getType(): int
    {
        return $this->type;
    }

    public function getTypeStrAttribute(): string
    {
        return self::TYPES[$this->type] ?? '';
    }

    public function setType(int $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function setMessage(string $message): self
    {
        $this->message = $message;

        return $this;
    }
}
