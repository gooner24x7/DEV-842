<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Service\StripeService;
use App\Service\SubscriptionService;
use App\Service\UserService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class SubscriptionsController extends Controller
{
    private StripeService $stripeService;
    private UserService $userService;
    private SubscriptionService $subscriptionService;

    public function __construct(StripeService $stripeService, UserService $userService, SubscriptionService $subscriptionService)
    {
        $this->stripeService = $stripeService;
        $this->userService = $userService;
        $this->subscriptionService = $subscriptionService;
    }

    public function stripePlansAvailable(): JsonResponse
    {
        if (!config('app.subscriptions_enabled')) {
            return new JsonResponse([], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse($this->stripeService->getPlans());
    }

    public function userStripePlan(string $id): JsonResponse
    {
        $user = $this->userService->getById((int)$id, false);
        if (!$user) {
            return new JsonResponse('Wrong user', Response::HTTP_NOT_FOUND);
        }

        if ($user->is_free_account) {
            return new JsonResponse([['stripe_plan' => SubscriptionService::FREE_PLAN_CODE]]);
        }

        return new JsonResponse([
            $this->subscriptionService->getUserSubscription($user)
        ]);
    }
}
