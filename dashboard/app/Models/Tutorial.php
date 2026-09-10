<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $title
 * @property int $embedCode
 * @method static orderBy($a, $b)
 * @method static where($a)
 * @method static create($a)
 */
class Tutorial extends Model
{
    protected $guarded = [];

    public function getId(): int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getEmbedCode(): int
    {
        return $this->embedCode;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'embedCode' => $this->embedCode,
        ];
    }
}
