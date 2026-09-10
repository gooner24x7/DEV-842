<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @method static select($a)
 */
class Partner extends Model
{
    protected $table = 'partners';

    protected $guarded = [];
}
