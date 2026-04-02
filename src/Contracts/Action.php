<?php

declare(strict_types=1);

namespace JustSteveKing\Scenario\Contracts;

use JustSteveKing\Scenario\Context\Context;

/**
 * Represents a single unit of work within a scenario.
 *
 * Implementations must provide a `handle()` method with a dynamic signature.
 * The engine will automatically resolve `handle()` arguments from the
 * Scenario Context, the initial input, action payloads, or the container.
 *
 * The `handle()` method MUST return a `JustSteveKing\Scenario\Support\Result`.
 */
interface Action
{
    /**
     * Revert the action's effects if the scenario fails.
     *
     * This is called by the Runner when a subsequent step fails,
     * following the Saga (LIFO) pattern.
     */
    public function compensate(mixed $input, Context $context): void;
}
