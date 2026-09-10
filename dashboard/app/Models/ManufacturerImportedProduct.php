<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class ManufacturerImportedProduct
 * @property int $manufacturer_id
 * @property int $deal_id
 * @property string $name
 * @property string $image_url
 * @property string $deal_name
 * @property string $category_name
 * @property string $subcategory_name
 * @property string $supplier_name
 * @property string $supplier_product_code
 * @property string $mpn
 * @property string $supplier_category_name
 * @property string $supplier_subcategory_name
 * @property string $web_product_name
 * @property string $etim_class_no
 * @property string $colour
 * @property string $material
 * @property string $finish
 * @property string $attr1_title
 * @property string $attr1_value
 * @property string $attr2_title
 * @property string $attr2_value
 * @property string $attr3_title
 * @property string $attr3_value
 * @property string $attr4_title
 * @property string $attr4_value
 * @property string $attr5_title
 * @property string $attr5_value
 * @property string $feature1
 * @property string $feature2
 * @property string $feature3
 * @property string $feature4
 * @property string $feature5
 * @property string $environmental_text
 * @property string $keywords
 * @property string $marketing_completeness
 * @property string $width_m
 * @property string $length_m
 * @property string $depth_m
 * @property string $weight_kg
 * @property string $list_price
 * @property string $discount_percent
 * @property string $direct2_cost_price
 * @property string $sku
 * @property int $lead_time_in_days
 * @property string $ean
 * @property string $product_manufacturer
 * @property string $unit_of_measure
 * @method static create(array $params)
 * @method static orderBy($a, $b)
 * @method static where($a)
 * @package App\Models
 */
class ManufacturerImportedProduct extends Model
{
    protected $table = 'manufacturer_imported_products';

    protected $guarded = [];
}
