<?php
declare(strict_types=1);

namespace App\Models\Questionnaire;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;

/**
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string $questions
 * @method static select(...$a)
 * @method static create($a)
 * @method static where($a)
 */
class Template extends Model
{
    use Notifiable;

    protected $table = 'questionnaire_templates';

    protected $guarded = [];

    public function worksPackage(): HasMany
    {
        return $this->hasMany(WorksPackage::class, 'template_id', 'id');
    }
}
