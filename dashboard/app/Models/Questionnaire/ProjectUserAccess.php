<?php
declare(strict_types=1);

namespace App\Models\Questionnaire;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $project_id
 * @property int $user_id
 * @property array $roles
 * @property int $stage
 * @method static orderBy($a, $b)
 * @method static create($a)
 */
class ProjectUserAccess extends Model
{
    protected $table = 'projects_user_access';

    protected $fillable = [
        'project_id',
        'user_id',
        'stage',
    ];

    protected $appends = [
        'roles'
    ];

    public $timestamps = false;

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id', 'id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getProjectId(): int
    {
        return $this->project_id;
    }

    public function getUserId(): int
    {
        return $this->user_id;
    }

    public function getStage(): ?int
    {
        return $this->stage;
    }

    public function getRolesAttribute(): array
    {
        return $this->user->roles()->pluck('slug')->toArray();
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'project_id' => $this->project_id,
            'user_id' => $this->user_id,
            'roles' => $this->roles,
            'stage' => $this->stage
        ];
    }
}
