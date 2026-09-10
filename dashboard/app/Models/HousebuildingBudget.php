<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class HousebuildingBudget
 * @property int $id
 * @property int $type
 * @property string $house_name
 * @property string $category_name
 * @property float $budget
 * @method static create(array $params)
 * @method static insert(array $data)
 * @method static orderBy($a, $b)
 * @method static where($a)
 * @package App\Models
 */
class HousebuildingBudget extends Model
{
    protected $table = 'housebuilding_budgets';
    protected $appends = ['type_str'];
    protected $guarded = [];

    public $timestamps = false;

    const array TYPES = [
        'budget',
        'total',
        'actual_price',
    ];

    public function getType(): int
    {
        return $this->type;
    }

    public function getTypeStrAttribute(): string
    {
        return self::TYPES[$this->type] ?? '';
    }

    public function setBudget(?float $budget): self
    {
        $this->budget = $budget;

        return $this;
    }
}
