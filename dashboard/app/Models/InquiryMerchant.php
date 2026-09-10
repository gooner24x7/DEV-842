<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class InquiryMerchant
 * @property int $user_id
 * @property int $comment
 * @property int $author_id
 * @method static create($a)
 * @method static select($a, $b)
 * @package App\Models
 */
class InquiryMerchant extends Model
{
    protected $guarded = [
    ];

    protected $table = 'inquiry_merchant';
}
