<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property $id
 * @property $ip
 * @property $attempts
 * @method static create($a)
 * @method static get()
 * @method static where($a)
 */
class IpAuthAttempt extends Model
{
    protected $guarded = [];
}
