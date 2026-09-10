<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property $action
 * @property $page
 * @property $comment
 * @property $user_id
 * @method static create(array $params)
 * @method static where(array $a)
 */
class ActionLog extends Model
{
    protected $guarded = [];
}
