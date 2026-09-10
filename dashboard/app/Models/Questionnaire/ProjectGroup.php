<?php

namespace App\Models\Questionnaire;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $user_id
 * @property int $status
 * @property string $name
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @method static create($a)
 * @method static where($a)
 */
class ProjectGroup extends Model
{
    protected $fillable = [
        'user_id',
        'status',
        'name',
    ];

    protected $casts = [
        'created_at' => 'datetime:d-m-Y H:i',
        'updated_at' => 'datetime:d-m-Y H:i',
    ];

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class, 'group_id', 'id');
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getUserId(): int
    {
        return $this->user_id;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function getName(): string
    {
        return $this->name;
    }
}
