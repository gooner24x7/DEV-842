<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $inquiry_id
 * @property int $manufacturer_product_id
 * @property int $qty
 * @package App\Models
 */
class InquiriesManufacturerProduct extends Model
{
    protected $table = 'inquiries_manufacturer_products';

    protected $guarded = [];
}
