<?php
declare(strict_types=1);

namespace App\Models\Stripe;

use Carbon\Carbon;
use Laravel\Cashier\SubscriptionBuilder;

class SubscriptionBuilderCustom extends SubscriptionBuilder
{
    private ?Carbon $trialEnds;

    public function __construct($owner, $name, $plans = [], ?Carbon $trialEnds = null)
    {
        parent::__construct($owner, $name, $plans);

        $this->trialEnds = $trialEnds;
    }

    protected function buildPayload(): array
    {
        $nextEvent = Carbon::now()->addMinutes(15);
        $properties = [
            'customer' => $this->getStripeCustomer()->id,
            'items' => collect($this->items)->values()->all(),
            'collection_method' => 'send_invoice', // 'charge_automatically', 'send_invoice',
            'days_until_due' => 14,
            'off_session' => true,
            //'trial_period_days' => 60,
            'proration_behavior' => 'none', //'create_prorations',
            'automatic_tax[enabled]' => true,
            'payment_settings[payment_method_types][]' => "customer_balance",
        ];

        if ($this->trialEnds) {
            $properties['trial_end'] = $this->trialEnds->utc()->unix();
        } else {
            $properties['billing_cycle_anchor'] = $nextEvent->utc()->unix();
        }

        return array_filter($properties);
    }
}
