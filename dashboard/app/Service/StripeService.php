<?php
declare(strict_types=1);

namespace App\Service;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Psr\SimpleCache\InvalidArgumentException;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;

class StripeService
{
    const string STRIPE_PLANS_CACHE_KEY = 'stripe_plans_%s';
    const int STRIPE_PLANS_CACHE_TTL = 600;
    const int DEFAULT_FETCH = 10;
    private StripeClient $client;
    private CacheRepository $cache;

    public function __construct(StripeClient $client, CacheRepository $cache)
    {
        $this->client = $client;
        $this->cache = $cache;
    }

    public function getPlansMap(): array
    {
        $map = [];
        foreach ($this->getPlans() as $plan) {
            $map[$plan->id] = $plan->product->name ?? '';
        }

        return $map;
    }

    /**
     * @throws ApiErrorException
     * @throws InvalidArgumentException
     */
    public function getPlans(): array
    {
        $params = [
            'active' => true,
            'limit' => self::DEFAULT_FETCH,
        ];

        $key = sprintf(self::STRIPE_PLANS_CACHE_KEY, http_build_query($params));

        $plans = $this->cache->get($key);
        if (is_array($plans)) {
            return $plans;
        }

        $plans = $this->loadPlans($params);

        $this->cache->put($key, $plans, self::STRIPE_PLANS_CACHE_TTL);

        return $plans;
    }

    /**
     * @throws ApiErrorException
     */
    private function loadPlans(array $params): array
    {
        $plansRaw = $this->client->plans->all($params);

        $plans = [];
        $latestPlan = null;
        foreach ($plansRaw->data as $plan) {
            $prod = $this->client->products->retrieve(
                $plan->product,
                []
            );
            if ($prod->active === true) {
                $plan->product = $prod;

                $plans[] = $plan;
            }

            $latestPlan = $plan;
        }

        if ($plansRaw->has_more) {
            $plans = array_merge($plans, $this->loadPlans(array_merge($params, [
                'starting_after' => $latestPlan->id,
            ])));
        }

        return $plans;
    }
}
