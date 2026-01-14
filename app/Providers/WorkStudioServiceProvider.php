<?php

namespace App\Providers;

use App\Services\WorkStudio\Aggregation\AggregateCalculationService;
use App\Services\WorkStudio\Aggregation\AggregateDiffService;
use App\Services\WorkStudio\Aggregation\AggregateQueryService;
use App\Services\WorkStudio\Aggregation\AggregateStorageService;
use App\Services\WorkStudio\ApiCredentialManager;
use App\Services\WorkStudio\Contracts\WorkStudioApiInterface;
use App\Services\WorkStudio\Transformers\CircuitTransformer;
use App\Services\WorkStudio\Transformers\DDOTableTransformer;
use App\Services\WorkStudio\Transformers\PlannedUnitAggregateTransformer;
use App\Services\WorkStudio\WorkStudioApiService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\ServiceProvider;

class WorkStudioServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register transformers as singletons
        $this->app->singleton(DDOTableTransformer::class);
        $this->app->singleton(CircuitTransformer::class);
        $this->app->singleton(PlannedUnitAggregateTransformer::class);

        // Register credential manager
        $this->app->singleton(ApiCredentialManager::class);

        // Register main API service and bind to interface
        $this->app->singleton(WorkStudioApiService::class, function ($app) {
            return new WorkStudioApiService(
                $app->make(ApiCredentialManager::class),
                $app->make(DDOTableTransformer::class),
                $app->make(CircuitTransformer::class),
            );
        });
        $this->app->singleton(WorkStudioApiInterface::class, function ($app) {
            return $app->make(WorkStudioApiService::class);
        });

        // Register aggregation services
        $this->app->singleton(AggregateCalculationService::class, function ($app) {
            return new AggregateCalculationService(
                $app->make(WorkStudioApiService::class),
                $app->make(PlannedUnitAggregateTransformer::class),
            );
        });

        $this->app->singleton(AggregateStorageService::class);
        $this->app->singleton(AggregateDiffService::class);
        $this->app->singleton(AggregateQueryService::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        Http::macro('workstudio', function () {
            return Http::timeout(config('workstudio.timeout', 60))
                ->connectTimeout(config('workstudio.connect_timeout', 10))
                ->withOptions(['verify' => false]);
        });
    }
}
