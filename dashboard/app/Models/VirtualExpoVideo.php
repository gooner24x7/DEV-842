<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $video
 * @property int $virtual_expo_id
 * @property int $user_id
 * @method static create($a)
 * @method static where($a)
 */
class VirtualExpoVideo extends Model
{
    protected $guarded = [];

    public function getId(): int
    {
        return $this->id;
    }

    public function getVideo(): string
    {
        return $this->video;
    }

    public function virtualExpo(): BelongsTo
    {
        return $this->belongsTo(VirtualExpoVideo::class);
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'video' => $this->video,
        ];
    }
}
