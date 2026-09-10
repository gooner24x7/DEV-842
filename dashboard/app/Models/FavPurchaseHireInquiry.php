<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $inquiry_id
 * @property int $user_id
 * @method static where(array $params)
 */
class FavPurchaseHireInquiry extends Model
{
    protected $guarded = [];

    protected $table = 'favourite_purchase_hire_inquiries';
}
