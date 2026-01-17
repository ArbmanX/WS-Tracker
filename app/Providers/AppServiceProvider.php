<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Enable strict mode in non-production to catch N+1 queries,
        // silently discarded attributes, and accessing missing attributes
        Model::shouldBeStrict(! $this->app->isProduction());
    }
}
