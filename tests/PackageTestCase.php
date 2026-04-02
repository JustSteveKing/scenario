<?php

declare(strict_types=1);

namespace JustSteveKing\Scenario\Tests;

use Orchestra\Testbench\TestCase;

abstract class PackageTestCase extends TestCase
{
    /**
     * Get package providers.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return array<int, class-string<\Illuminate\Support\ServiceProvider>>
     */
    protected function getPackageProviders($app): array
    {
        return [
            \JustSteveKing\Scenario\Providers\ScenarioServiceProvider::class,
        ];
    }
}
