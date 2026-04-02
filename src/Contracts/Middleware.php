<?php

declare(strict_types=1);

namespace JustSteveKing\Scenario\Contracts;

use Closure;
use JustSteveKing\Scenario\Context\Context;
use JustSteveKing\Scenario\Support\Result;

interface Middleware
{
    /**
     * Handle the scenario execution.
     *
     * @param mixed $input The initial input passed to the scenario.
     * @param Context $context The current scenario context.
     * @param Closure(mixed, Context): Result $next The next middleware or the scenario runner.
     * @return Result
     */
    public function handle(mixed $input, Context $context, Closure $next): Result;
}
