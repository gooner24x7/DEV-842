<?php
declare(strict_types=1);

namespace App\Service;

use App\DataProvider\UserDataProvider;
use App\Models\Stripe\Subscription;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Exceptions\PaymentActionRequired;
use Laravel\Cashier\Exceptions\PaymentFailure;
use RedisException;
use Stripe\Subscription as StripeSubscription;

class SubscriptionService
{
    const string FREE_PLAN_CODE = 'free';
    private UserDataProvider $userDataProvider;
    private StripeService $stripeService;

    public function __construct(UserDataProvider $userDataProvider, StripeService $stripeService)
    {
        $this->userDataProvider = $userDataProvider;
        $this->stripeService = $stripeService;
    }

    /**
     * @throws PaymentFailure
     * @throws PaymentActionRequired|RedisException
     */
    public function setSubscriptionForUser(User $user, ?array $newPlan, ?Carbon $trialEnds = null): void
    {
        //TODO: use repository
        $currentPlan = is_array($newPlan) ? $newPlan[0] : null;

        if ($currentPlan === self::FREE_PLAN_CODE) {
            $user->is_free_account = true;
        } else {
            $user->is_free_account = false;
        }

        $user->save();

        if ($currentPlan === self::FREE_PLAN_CODE) {
            return;
        }

        $subscription = $this->userDataProvider->getUserStripeSubscription($user->getId(), false);

        if (empty($newPlan) && $subscription) {
            Log::debug('remove current plan');
            $this->cancelSubscription($subscription);

            $this->userDataProvider->touch($user->getId());
            return;
        }

        if (!$subscription) {
            $this->createSubscription($user, $newPlan, $trialEnds);
            $this->userDataProvider->touch($user->getId());
            return;
        }

        if (!$user->subscribedToPlan($newPlan, Subscription::DEFAULT_NAME)) {
            $this->userDataProvider->touch($user->getId());

            $this->cancelSubscription($subscription);
            $this->createSubscription($user, $newPlan, $trialEnds);
        }
    }

    private function cancelSubscription(Subscription $subscription): void
    {
        if ($subscription->cancelled()) {
            return;
        }

        $subscription->cancelNow();
    }

    /**
     * @throws PaymentFailure
     * @throws PaymentActionRequired
     */
    public function createSubscription(User $user, $plans, ?Carbon $trialEnds = null): void
    {
        $user->newSubscription(Subscription::DEFAULT_NAME, $plans, $trialEnds)->create();
    }

    /**
     * @throws RedisException
     */
    public function getUserSubscription(User $user): ?Subscription
    {
        //TODO: check the payment method and use relevant service
        return $this->userDataProvider->getUserStripeSubscription($user->getId());
    }

    /**
     * @throws Exception
     */
    public function ensureSubscribed(User $user): void
    {
        if (config("app.subscriptions_enabled") === false) {
            return;
        }

        /** @var User $billingUser */
        $billingUser = $user?->billingUser;
        if ($billingUser && $billingUser->is_free_account) {
            return;
        }

        if ($billingUser && $this->noActiveStripeSubscription($billingUser)) {
            throw new Exception('No active subscription');
        }
    }

    private function noActiveStripeSubscription(User $billingUser): bool
    {
        $subscription = $billingUser->subscription(Subscription::DEFAULT_NAME);
        return !$subscription || in_array($subscription->stripe_status, [
                StripeSubscription::STATUS_UNPAID,
                StripeSubscription::STATUS_INCOMPLETE_EXPIRED,
                StripeSubscription::STATUS_CANCELED,
            ]);
    }

    /**
     * @throws RedisException
     */
    public function getUserStripeSubscriptionName(int $userId): string
    {
        /** @var Subscription $subscription */
        $subscription = $this->userDataProvider->getUserStripeSubscription($userId);
        return $this->getStripeSubscriptionNameForSubscription($subscription);
    }

    public function getStripeSubscriptionNameForSubscription(?Subscription $subscription): string
    {
        $stripePlansMap = $this->getStripePlansMap();
        $stripePlan = $subscription['stripe_plan'] ?? '';
        return $stripePlansMap[$stripePlan] ?? '-';
    }

    public function getStripePlansMap(): array
    {
        if (config('app.subscriptions_enabled') === false) {
            return [];
        }

        return $this->stripeService->getPlansMap();
    }
}
