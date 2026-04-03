<?php

declare(strict_types=1);

namespace JustSteveKing\Scenario\Testing;

use Closure;
use PHPUnit\Framework\Assert;

class ScenarioFake
{
    /**
     * @var array<string, array<int, array{input: mixed}>>
     */
    protected array $recorded = [];

    /**
     * Record a scenario execution.
     */
    public function record(string $scenarioClass, mixed $input): void
    {
        if (!isset($this->recorded[$scenarioClass])) {
            $this->recorded[$scenarioClass] = [];
        }

        $this->recorded[$scenarioClass][] = ['input' => $input];
    }

    /**
     * Assert that a scenario ran, optionally inspecting the input.
     *
     * @param class-string $scenarioClass
     */
    public function assertRan(string $scenarioClass, ?Closure $callback = null): void
    {
        Assert::assertTrue(
            isset($this->recorded[$scenarioClass]) && count($this->recorded[$scenarioClass]) > 0,
            "The expected [{$scenarioClass}] scenario was not run.",
        );

        if ($callback !== null) {
            $matched = false;
            foreach ($this->recorded[$scenarioClass] as $record) {
                if ($callback($record['input'])) {
                    $matched = true;
                    break;
                }
            }
            Assert::assertTrue(
                $matched,
                "The expected [{$scenarioClass}] scenario was run, but the input did not match the given callback.",
            );
        }
    }

    /**
     * Assert that a scenario did not run.
     *
     * @param class-string $scenarioClass
     */
    public function assertNotRan(string $scenarioClass): void
    {
        Assert::assertFalse(
            isset($this->recorded[$scenarioClass]) && count($this->recorded[$scenarioClass]) > 0,
            "The unexpected [{$scenarioClass}] scenario was run.",
        );
    }

    /**
     * Assert that no scenarios were run.
     */
    public function assertNothingRan(): void
    {
        Assert::assertEmpty(
            $this->recorded,
            "Scenarios were unexpectedly run.",
        );
    }
}
