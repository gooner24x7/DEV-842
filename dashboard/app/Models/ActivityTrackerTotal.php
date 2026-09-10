<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @method static create(array $params)
 */
class ActivityTrackerTotal extends Model
{
    protected $table = 'activity_tracker_totals';

    protected $guarded = [];
}
