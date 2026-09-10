<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OtherMerchants extends Model
{
    protected $table = 'other_merchants';
    protected $fillable = [
        'place_id',
        'called',
        'onboarded'
    ];
    protected $casts = [
        'called' => 'boolean',
        'onboarded' => 'boolean'
    ];
}
