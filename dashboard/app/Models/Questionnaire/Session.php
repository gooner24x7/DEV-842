<?php
declare(strict_types=1);

namespace App\Models\Questionnaire;

use DateTime;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

/**
 * @property int $id
 * @property int $inquiry_id
 * @property int $user_id
 * @property int $is_answered
 * @property int $is_accepted
 * @property int $is_declined
 * @property DateTime $deleted_at
 * @property int $project_id
 * @property int $works_package_id
 * @property string $hash
 * @method static select(...$a)
 * @method static create($a)
 * @method static where($a)
 */
class Session extends Model
{
    use Notifiable;

    protected $table = 'questionnaire_sessions';

    protected $guarded = [];
    protected $appends = [];

    public function getId(): int
    {
        return $this->id;
    }
}
