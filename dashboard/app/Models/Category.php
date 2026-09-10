<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $name
 * @property bool $active
 * @property int $category_id
 * @property string $slug
 * @property int $type
 * @method static orderBy($a, $b)
 * @method static where($a)
 * @method static create($a)
 * @method static select($a)
 */
class Category extends Model
{
    const int UNKNOWN_CATEGORY_ID = 0;

    protected $table = 'categories';

    protected $guarded = [];

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isActive(): bool
    {
        return (bool)$this->active;
    }

    public function getType(): int
    {
        return $this->type;
    }
}
