<?php

declare(strict_types=1);

namespace JustSteveKing\Scenario;

use Illuminate\Container\Container as IlluminateContainer;
use JustSteveKing\Scenario\Engine\Blueprint;
use JustSteveKing\Scenario\Contracts\Scenario as ScenarioContract;

/**
 * The entry point for the Scenario engine.
 *
 * Use `Scenario::for()` to define the business process you want to run.
 * This facade handles the instantiation of the scenario class,
 * building its blueprint, and returning a `PendingScenario`.
 */
class Scenario
{
    /**
     * Start a new scenario execution.
     *
     * @param class-string<ScenarioContract> $scenarioClass The FQCN of the Scenario to build.
     * @return PendingScenario A fluently configurable scenario ready for execution.
     */
    public static function for(string $scenarioClass): PendingScenario
    {
        $container = IlluminateContainer::getInstance();

        $blueprint = new Blueprint();

        /** @var ScenarioContract $scenario */
        $scenario = $container->make($scenarioClass);
        $scenario->build($blueprint);

        return new PendingScenario($blueprint, $container);
    }
}
