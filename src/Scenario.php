<?php

declare(strict_types=1);

namespace JustSteveKing\Scenario;

use Illuminate\Container\Container as IlluminateContainer;
use JustSteveKing\Scenario\Engine\Blueprint;
use JustSteveKing\Scenario\Contracts\Scenario as ScenarioContract;

class Scenario
{
    /**
     * Entry point to define which scenario to run.
     *
     * @param class-string<ScenarioContract> $scenarioClass
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
