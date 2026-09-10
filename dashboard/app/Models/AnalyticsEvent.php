<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class AnalyticsEvent
 * @package App\Models
 * @property $user_id
 * @property $object_id
 * @property $object_type
 * @property $action
 * @property $location
 * @property $url;
 * @property $target;
 * @method static where(array $a)
 */
class AnalyticsEvent extends Model
{
    protected $guarded = [];

    protected $table = 'analytics_events';
}
