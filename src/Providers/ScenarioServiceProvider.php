<?php

declare(strict_types=1);

namespace JustSteveKing\Scenario\Providers;

use Illuminate\Support\ServiceProvider;
use JustSteveKing\Scenario\Engine\Resolver;

class ScenarioServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(Resolver::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
