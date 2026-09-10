<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserOnboarding extends Model
{
    const string ONBOARDING_INTERVAL = '+2 weeks';
    protected $table = 'user_onboarding';
    protected $fillable = [
        'user_id',
        'account_setup',
        'billing_portal',
        'preferred_supplier',
        'customer_intro',
        'first_enquiry',
        'line_of_credit',
        'form_complete',
        'first_project',
        'first_works_package',
        'first_tender',
        'onboarding_end_date'
    ];
    protected $casts = [
        'account_setup' => 'boolean',
        'billing_portal' => 'boolean',
        'preferred_supplier' => 'boolean',
        'customer_intro' => 'boolean',
        'first_enquiry' => 'boolean',
        'line_of_credit' => 'boolean',
        'form_complete' => 'boolean',
        'first_project' => 'boolean',
        'first_works_package' => 'boolean',
        'first_tender' => 'boolean'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
