<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property $id
 * @property $user_id
 * @property $status
 * @method static create($a)
 * @method static get()
 * @method static where($a)
 */
class UserAuthCheck extends Model
{
    protected $table = 'user_auth_check';

    protected $guarded = [];
}
