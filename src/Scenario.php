<?php

declare(strict_types=1);

namespace JustSteveKing\Scenario;

use Illuminate\Container\Container as IlluminateContainer;
use JustSteveKing\Scenario\Engine\Blueprint;
use JustSteveKing\Scenario\Contracts\Scenario as ScenarioContract;
use JustSteveKing\Scenario\Testing\FakedPendingScenario;
use JustSteveKing\Scenario\Testing\ScenarioFake;

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
     * @var ScenarioFake|null
     */
    protected static ?ScenarioFake $fake = null;

    /**
     * Swap the Scenario execution with a fake to allow for testing without running actions.
     */
    public static function fake(): ScenarioFake
    {
        return self::$fake = new ScenarioFake();
    }

    /**
     * Clear the faked scenario instance.
     */
    public static function clearFake(): void
    {
        self::$fake = null;
    }

    /**
     * Assert that a scenario ran, optionally inspecting the input.
     *
     * @param class-string<ScenarioContract> $scenarioClass
     */
    public static function assertRan(string $scenarioClass, ?\Closure $callback = null): void
    {
        if (self::$fake !== null) {
            self::$fake->assertRan($scenarioClass, $callback);
        }
    }

    /**
     * Assert that a scenario did not run.
     *
     * @param class-string<ScenarioContract> $scenarioClass
     */
    public static function assertNotRan(string $scenarioClass): void
    {
        if (self::$fake !== null) {
            self::$fake->assertNotRan($scenarioClass);
        }
    }

    /**
     * Assert that no scenarios were run.
     */
    public static function assertNothingRan(): void
    {
        if (self::$fake !== null) {
            self::$fake->assertNothingRan();
        }
    }

    /**
     * Start a new scenario execution.
     *
     * @param class-string<ScenarioContract> $scenarioClass The FQCN of the Scenario to build.
     * @return PendingScenario A fluently configurable scenario ready for execution.
     */
    public static function for(string $scenarioClass): PendingScenario
    {
        if (self::$fake !== null) {
            return new FakedPendingScenario($scenarioClass, self::$fake);
        }

        $container = IlluminateContainer::getInstance();
        $container->instance(\Illuminate\Contracts\Container\Container::class, $container);

        $blueprint = new Blueprint();

        /** @var ScenarioContract $scenario */
        $scenario = $container->make($scenarioClass);
        $scenario->build($blueprint);

        return (new PendingScenario($blueprint, $container))->setScenarioClass($scenarioClass);
    }
}
