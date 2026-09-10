<?php
declare(strict_types=1);

namespace App\Providers;

use App\DataProvider\UserRoleDataProvider;
use App\Kafka\CrmContactsConsumer;
use App\Models\Stripe\Subscription;
use App\Models\Stripe\SubscriptionItem;
use App\Service\CreditSafeService;
use App\Service\StripeService;
use App\Service\UserService;
use Illuminate\Container\Container;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Laravel\Cashier\Cashier;
use RdKafka\Conf;
use RdKafka\Consumer;
use Stripe\StripeClient;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(StripeClient::class, static function (): StripeClient {
            $key = Config::get('services.stripe.secret');

            return new StripeClient($key);
        });

        $this->app->bind(StripeService::class, function (): StripeService {
            return new StripeService(
                $this->app->get(StripeClient::class),
                $this->app->get(CacheRepository::class)
            );
        });

        $this->app->bind(\Redis::class, function (): \Redis {
            return Redis::connection()->client();
        });

        $this->app->bind(CacheRepository::class, function (): CacheRepository {
            return Cache::store('redis');
        });

        $this->app->bind(Conf::class, static function (Container $app): Conf {
            $conf = new Conf();
            $conf->set('log_level', '0');
            //$conf->set('debug', 'all');
            $conf->set('security.protocol', config('app.security_protocol'));
            //$conf->set('sasl.mechanisms', 'PLAIN');
            //$conf->set('sasl.username', config('app.kafka_login', ''));
            //$conf->set('sasl.password', config('app.kafka_password', ''));
            $conf->set('enable.auto.commit', '0');
            $conf->set('auto.commit.interval.ms', '1e3');
            $conf->set('offset.store.sync.interval.ms', '60e3');
            $conf->set("group.id", 'dashboard');

            return $conf;
        });

        $this->app->bind(Consumer::class, static function (Container $app): Consumer {
            $consumer = new Consumer($app->get(Conf::class));
            $consumer->addBrokers(config('app.kafka_brokers'));

            return $consumer;
        });

        $this->app->bind(CrmContactsConsumer::class, static function (Container $app): CrmContactsConsumer {
            return new CrmContactsConsumer(
                $app->get(UserService::class),
                $app->get(UserRoleDataProvider::class)
            );
        });

        $this->app->bind(CreditSafeService::class, function (): CreditSafeService {
            return new CreditSafeService(
                $this->app->get(CacheRepository::class)
            );
        });
    }

    public function boot(): void
    {
        Schema::defaultStringLength(191);
        Cashier::useSubscriptionModel(Subscription::class);
        Cashier::useSubscriptionItemModel(SubscriptionItem::class);
    }
}
