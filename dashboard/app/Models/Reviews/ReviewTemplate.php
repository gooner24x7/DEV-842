<?php
declare(strict_types=1);

namespace App\Models\Reviews;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string $description
 * @method static find(int $a)
 * @method static orderBy($a, $b)
 * @method static create(array $a)
 */
class ReviewTemplate extends Model
{
    protected $table = 'review_templates';

    protected $guarded = [];

    protected $fillable = [
        'user_id',
        'name',
        'description',
    ];

    protected $casts = [
        'created_at' => 'datetime:d-m-Y H:i',
        'updated_at' => 'datetime:d-m-Y H:i',
    ];

    public function sections(): HasMany
    {
        return $this->hasMany(ReviewTemplateSection::class, 'template_id', 'id');
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getUserId(): int
    {
        return $this->user_id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }
}
