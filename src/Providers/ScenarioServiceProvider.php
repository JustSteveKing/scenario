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
        if ($this->app->runningInConsole()) {
            $this->commands([
                \JustSteveKing\Scenario\Console\Commands\ScenarioMakeCommand::class,
                \JustSteveKing\Scenario\Console\Commands\ActionMakeCommand::class,
                \JustSteveKing\Scenario\Console\Commands\MiddlewareMakeCommand::class,
            ]);
        }
    }
}
