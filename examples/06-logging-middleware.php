<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Illuminate\Container\Container as IlluminateContainer;
use JustSteveKing\Scenario\Contracts\Action;
use JustSteveKing\Scenario\Contracts\Scenario as ScenarioContract;
use JustSteveKing\Scenario\Context\Context;
use JustSteveKing\Scenario\Engine\Blueprint;
use JustSteveKing\Scenario\Middleware\LoggingMiddleware;
use JustSteveKing\Scenario\Scenario;
use JustSteveKing\Scenario\Support\Result;

/**
 * 1. A minimal PSR-3 logger that writes to stdout.
 *
 *    In a real Laravel application, Psr\Log\LoggerInterface is already
 *    bound in the container — you simply pass LoggingMiddleware::class
 *    to ->through() and the framework handles injection automatically.
 *
 *    In this standalone example we bind it manually so the same code
 *    path is exercised.
 */
class StdoutLogger extends \Psr\Log\AbstractLogger
{
    public function log($level, string|\Stringable $message, array $context = []): void
    {
        $contextStr = empty($context) ? '' : ' ' . json_encode($context);
        echo "   [{$level}] {$message}{$contextStr}\n";
    }
}

IlluminateContainer::getInstance()->bind(
    \Psr\Log\LoggerInterface::class,
    fn() => new StdoutLogger(),
);

/**
 * 2. Define a simple two-step scenario.
 */
class ValidateOrder implements Action
{
    public function handle(): Result
    {
        echo "   Validating order...\n";
        return Result::success();
    }

    public function compensate(mixed $input, Context $context): void {}
}

class ChargeCard implements Action
{
    public function handle(): Result
    {
        echo "   Charging card...\n";
        return Result::success();
    }

    public function compensate(mixed $input, Context $context): void {}
}

class OrderScenario implements ScenarioContract
{
    public function build(Blueprint $plan): void
    {
        $plan->add(ValidateOrder::class)->add(ChargeCard::class);
    }
}

/**
 * 3. A scenario that fails mid-way, to show warning-level logging.
 */
class RejectPayment implements Action
{
    public function handle(): Result
    {
        echo "   Processing payment... declined.\n";
        return Result::failure('Card declined');
    }

    public function compensate(mixed $input, Context $context): void {}
}

class FailingOrderScenario implements ScenarioContract
{
    public function build(Blueprint $plan): void
    {
        $plan->add(ValidateOrder::class)->add(RejectPayment::class);
    }
}

/**
 * 4. Run both scenarios through LoggingMiddleware.
 */
echo "--- Successful scenario with LoggingMiddleware ---\n";

Scenario::for(OrderScenario::class)
    ->through([LoggingMiddleware::class])
    ->run()
    ->onSuccess(fn() => print("   Done!\n"));

echo "\n--- Failing scenario with LoggingMiddleware ---\n";

Scenario::for(FailingOrderScenario::class)
    ->through([LoggingMiddleware::class])
    ->run()
    ->onFailure(fn(string $error) => print("   Caught: {$error}\n"));
