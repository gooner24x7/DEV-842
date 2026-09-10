<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class SubscriptionProviderScope implements Scope
{
    protected string $provider;

    public function __construct($provider)
    {
        $this->provider = $provider;
    }

    public function apply(Builder $builder, Model $model): void
    {
        $builder->where('provider', $this->provider);
    }
}
