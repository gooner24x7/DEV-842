<?php
declare(strict_types=1);

namespace App\Models\Stripe;

use App\Models\SubscriptionProviderScope;
use Laravel\Cashier\Subscription as CashierSubscription;

/**
 * @property string $stripe_status
 */
class Subscription extends CashierSubscription
{
    const string DEFAULT_NAME = 'default';
    const string STRIPE_PROVIDER = 'stripe';

    public $trial_ends_at;
    public $ends_at;

    protected static function booted(): void
    {
        static::addGlobalScope(new SubscriptionProviderScope(self::STRIPE_PROVIDER));
    }
}
