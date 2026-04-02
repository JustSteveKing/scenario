<?php

declare(strict_types=1);

namespace JustSteveKing\Scenario\Contracts;

use JustSteveKing\Scenario\Context\Context;

interface Action
{
    /**
     * Revert the action's effects if the scenario fails.
     */
    public function compensate(mixed $input, Context $context): void;
}
