<?php
declare(strict_types=1);

namespace App\Models\Questionnaire;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $project_id
 * @property int $user_id
 * @property int $group_id
 * @property string $name
 * @property string $filename
 * @property string $description
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @method static orderBy($a, $b)
 * @method static create($a)
 */
class ProjectDocument extends Model
{
    protected $guarded = [];

    public function group(): BelongsTo
    {
        return $this->belongsTo(ProjectDocumentGroup::class, 'group_id', 'id');
    }

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

    public function getGroupId(): int
    {
        return $this->group_id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getFileName(): string
    {
        return $this->filename;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'project_id' => $this->project_id,
            'user_id' => $this->user_id,
            'group_id' => $this->group_id,
            'name' => $this->name,
            'filename' => $this->filename,
            'description' => $this->description,
            'created_by' => $this->user->first_name,
            'created_at' => $this->created_at->format('d-m-Y H:i'),
        ];
    }
}
