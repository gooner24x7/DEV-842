<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property int $virtual_expo_id
 * @property int $virtual_expo_user_id
 * @property string $created_at
 * @method static orderBy($a, $b)
 * @method static where($a)
 * @method static create(...$a)
 */
class VirtualExpoSharedContact extends Model
{
    protected $guarded = [];

    public function virtualExpo(): BelongsTo
    {
        return $this->belongsTo(VirtualExpoVideo::class);
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getUserId(): int
    {
        return $this->user_id;
    }

    public function getCreatedAt(): string
    {
        return $this->created_at;
    }

    public function getVirtualExpoId(): int
    {
        return $this->virtual_expo_id;
    }
}
