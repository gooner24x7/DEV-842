<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class HousebuildingProduct
 * @property int $id
 * @property string $house_name
 * @property string $category
 * @property string $subcategory
 * @property string $product_name
 * @property string $product_ref
 * @property string $uom
 * @property float $quantity
 * @property float $price
 * @method static create(array $params)
 * @method static insert(array $data)
 * @method static orderBy($a, $b)
 * @method static where($a)
 * @package App\Models
 */
class HousebuildingProduct extends Model
{
    protected $table = 'housebuilding';
    protected $guarded = [];

    public $timestamps = false;
}
