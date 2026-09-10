<?php

declare(strict_types=1);

namespace App\Providers;

use App\Service\BoqAllocator\AiProviderService;
use App\Service\BoqAllocator\BoqAllocationEngine;
use App\Service\BoqAllocator\BoqParserService;
use Illuminate\Support\ServiceProvider;

class BoqAllocatorServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/boq-allocator.php',
            'boq-allocator'
        );

        $this->app->singleton(BoqParserService::class, function () {
            return new BoqParserService();
        });

        $this->app->singleton(AiProviderService::class, function ($app) {
            return new AiProviderService(null, config('boq-allocator'));
        });

        $this->app->singleton(BoqAllocationEngine::class, function ($app) {
            return new BoqAllocationEngine(
                $app->make(BoqParserService::class),
                config('boq-allocator')
            );
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../../config/boq-allocator.php' => config_path('boq-allocator.php'),
            ], 'boq-allocator-config');

            $this->publishes([
                __DIR__ . '/../../templates' => storage_path('app/templates'),
            ], 'boq-allocator-templates');
        }
    }
}
