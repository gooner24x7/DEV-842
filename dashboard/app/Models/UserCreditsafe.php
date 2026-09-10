<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserCreditsafe extends Model
{
    protected $table = 'user_creditsafe';
    protected $fillable = [
        'user_id',
        'vat_no',
        'risk_score',
        'international_score',
        'credit_limit',
        'contract_limit',
        'total_ccjs'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
