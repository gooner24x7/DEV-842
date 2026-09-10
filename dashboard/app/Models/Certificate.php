<?php
declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property string $type
 * @property string $cert_no
 * @property string $authority
 * @property string $scope
 * @property string $url
 * @property Carbon $issue_date
 * @property Carbon $expiry_date
 * @method static find(int $id)
 * @method static where(array $array)
 * @method static create(array $array)
 */

class Certificate extends Model
{
    protected $table = 'certificates';
    protected $fillable = [
        'user_id',
        'type',
        'cert_no',
        'authority',
        'scope',
        'url',
        'issue_date',
        'expiry_date'
    ];

    const array TYPES = [
        [
            'slug' => 'cscs',
            'label' => 'CSCS'
        ],
        [
            'slug' => 'ssip',
            'label' => 'SSIP'
        ],
        [
            'slug' => 'cyber',
            'label' => 'Cyber Essentials'
        ],
        [
            'slug' => 'fire_products',
            'label' => 'Fire Products'
        ],
        [
            'slug' => 'fire_competencies',
            'label' => 'Fire Competencies'
        ],
        [
            'slug' => 'pref_suppliers',
            'label' => 'Sub-letting'
        ],
        [
            'slug' => 'modern_slavery',
            'label' => 'Modern Slavery'
        ],
        [
            'slug' => 'induction',
            'label' => 'Induction'
        ],
        [
            'slug' => 'goods_supply',
            'label' => 'Goods Supply'
        ],
        [
            'slug' => 'cas',
            'label' => 'CAS'
        ],
        [
            'slug' => 'bribery',
            'label' => 'Bribery & Corruption'
        ],
        [
            'slug' => 'conduct',
            'label' => 'Code of Conduct'
        ],
        [
            'slug' => 'mod',
            'label' => 'MOD'
        ],
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
