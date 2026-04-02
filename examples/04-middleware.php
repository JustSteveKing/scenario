<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use JustSteveKing\Scenario\Contracts\Middleware;
use JustSteveKing\Scenario\Context\Context;
use JustSteveKing\Scenario\Support\Result;
use JustSteveKing\Scenario\Scenario;
use JustSteveKing\Scenario\Contracts\Scenario as ScenarioContract;
use JustSteveKing\Scenario\Engine\Blueprint;

/**
 * 1. Define a Middleware to wrap the scenario in a "transaction" or log it.
 */
class LogScenarioMiddleware implements Middleware
{
    public function handle(mixed $input, Context $context, Closure $next): Result
    {
        echo "[Middleware] Starting scenario execution...\n";

        $result = $next($input, $context);

        $status = $result->isSuccess() ? 'SUCCESS' : 'FAILURE';
        echo "[Middleware] Scenario finished with status: {$status}\n";

        return $result;
    }
}

/**
 * 2. Define a simple action.
 */
class HelloAction implements \JustSteveKing\Scenario\Contracts\Action
{
    public function handle(): Result
    {
        echo "   -> Hello from Action!\n";
        return Result::success();
    }
    public function compensate(mixed $input, Context $context): void {}
}

/**
 * 3. Define the Scenario.
 */
class MiddlewareExampleScenario implements ScenarioContract
{
    public function build(Blueprint $plan): void
    {
        $plan->add(HelloAction::class);
    }
}

/**
 * 4. Run with Middleware.
 */
echo "--- Running Middleware Example ---\n";

Scenario::for(MiddlewareExampleScenario::class)
    ->through([LogScenarioMiddleware::class])
    ->run()
    ->onSuccess(fn() => print("Done!\n"));
