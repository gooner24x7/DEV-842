<?php
declare(strict_types=1);

namespace App\Models\Questionnaire;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;

/**
 * @property int $id
 * @property int $parent_id
 * @property int $project_id
 * @property int $template_id
 * @property int $user_id
 * @property int $high_risk
 * @property int $cpv_code
 * @property string $name
 * @property array $children
 * @method static find($a)
 * @method static orderBy($a, $b)
 * @method static create($a)
 */
class WorksPackage extends Model
{
    protected $table = 'works_packages';

    protected $guarded = [];
    protected $appends = [
        'children',
        'assigned_users'
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id', 'id');
    }

    public function questionnaires(): HasMany
    {
        return $this->hasMany(Questionnaire::class, 'works_package_id', 'id');
    }

    public function template(): HasOne
    {
        return $this->hasOne(Template::class, 'id', 'template_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(WorksPackage::class, 'parent_id', 'id');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(WorksPackageQuestion::class, 'works_package_id', 'id');
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getParentId(): ?int
    {
        return $this->parent_id;
    }

    public function getProjectId(): int
    {
        return $this->project_id;
    }

    public function getTemplateId(): ?int
    {
        return $this->template_id;
    }

    public function getUserId(): int
    {
        return $this->user_id;
    }

    public function getHighRisk(): int
    {
        return $this->high_risk;
    }

    public function getCpvCode(): ?int
    {
        return $this->cpv_code;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getChildrenAttribute(): array
    {
        $query = WorksPackage::query()->where('parent_id', $this->getId());

        return $query->get()->toArray();
    }

    public function getAssignedUsersAttribute(): array
    {
        return DB::table('users')->select('users.id', 'users.first_name', 'users.last_name')
            ->join('works_packages_assigned_users', 'users.id', '=', 'works_packages_assigned_users.user_id')
            ->where('works_packages_assigned_users.works_package_id', '=', $this->getId())
            ->get()
            ->toArray();
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function setParentId(?int $parentId): self
    {
        $this->parent_id = $parentId;

        return $this;
    }

    public function setTemplateId(?int $templateId): self
    {
        $this->template_id = $templateId;

        return $this;
    }

    public function setHighRisk(int $highRisk): self
    {
        $this->high_risk = $highRisk;

        return $this;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'parent_id' => $this->parent_id,
            'project_id' => $this->project_id,
            'user_id' => $this->user_id,
            'project_name' => $this->project()->first()?->name,
            'template_id' => $this->template_id,
            'high_risk' => $this->high_risk,
            'cpv_code' => $this->cpv_code,
            'name' => $this->name,
            'children' => $this->children,
            'assigned_users' => $this->assignedUsers
        ];
    }
}
