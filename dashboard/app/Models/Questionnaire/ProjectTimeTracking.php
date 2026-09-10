<?php
declare(strict_types=1);

namespace App\Models\Questionnaire;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property int $project_id
 * @property int $user_id
 * @property float $hours_ap
 * @property float $hours_se
 * @property float $spend_se
 * @property string $name_se
 * @method static orderBy($a, $b)
 * @method static create($a)
 */
class ProjectTimeTracking extends Model
{
    protected $table = 'project_time_tracking';

    protected $guarded = [];
    protected $appends = [];
    protected $casts = [
        'created_at' => 'datetime:d-m-Y H:i',
        'updated_at' => 'datetime:d-m-Y H:i'
    ];

    public function project(): HasOne
    {
        return $this->hasOne(Project::class);
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

    public function getHoursAp(): float
    {
        return $this->hours_ap;
    }

    public function getHoursSe(): float
    {
        return $this->hours_se;
    }

    public function getSpendSe(): float
    {
        return $this->spend_se;
    }

    public function getNameSe(): string
    {
        return $this->name_se;
    }
}
