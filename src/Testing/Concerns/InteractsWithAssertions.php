<?php

declare(strict_types=1);

namespace JustSteveKing\Scenario\Testing\Concerns;

use JustSteveKing\Scenario\Context\Context;
use JustSteveKing\Scenario\Support\Result;
use PHPUnit\Framework\Assert;

trait InteractsWithAssertions
{
    /**
     * Get the final result of the scenario.
     */
    abstract public function result(): ?Result;

    /**
     * Get the shared scenario context.
     */
    abstract public function context(): Context;

    /**
     * Assert that the scenario completed successfully.
     */
    public function assertPassed(): self
    {
        Assert::assertTrue(
            $this->result() !== null && $this->result()->isSuccess(),
            'Failed asserting that the scenario passed.',
        );

        return $this;
    }

    /**
     * Assert that the scenario failed.
     */
    public function assertFailed(): self
    {
        Assert::assertTrue(
            $this->result() !== null && $this->result()->isFailure(),
            'Failed asserting that the scenario failed.',
        );

        return $this;
    }

    /**
     * Assert that a specific class or interface was recorded in the Context.
     *
     * @param class-string $class
     */
    public function assertContextHas(string $class, ?\Closure $callback = null): self
    {
        $object = $this->context()->get($class);

        Assert::assertNotNull(
            $object,
            "Failed asserting that the context has an object of type {$class}.",
        );

        if ($callback !== null) {
            Assert::assertTrue(
                $callback($object),
                "Failed asserting that the context object of type {$class} matches the given callback.",
            );
        }

        return $this;
    }
}
