<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use JustSteveKing\Scenario\Contracts\Action;
use JustSteveKing\Scenario\Contracts\Scenario as ScenarioContract;
use JustSteveKing\Scenario\Context\Context;
use JustSteveKing\Scenario\Engine\Blueprint;
use JustSteveKing\Scenario\Scenario;
use JustSteveKing\Scenario\Support\Result;

/**
 * 1. Define the input.
 */
readonly class OrderInput
{
    public function __construct(public int $id) {}
}

/**
 * 2. Define the actions.
 */
class ValidateOrderStep implements Action
{
    public function handle(OrderInput $input): Result
    {
        echo "   [Action] Validating order #{$input->id}...\n";
        return Result::success();
    }

    public function compensate(mixed $input, Context $context): void {}
}

class ReserveInventory implements Action
{
    public function handle(OrderInput $input): Result
    {
        echo "   [Action] Reserving inventory for order #{$input->id}...\n";

        $reservation = new stdClass();
        $reservation->ref = 'RES-' . $input->id;

        return Result::success($reservation);
    }

    public function compensate(mixed $input, Context $context): void
    {
        echo "   [Compensate] Releasing inventory reservation.\n";
    }
}

class SendConfirmationEmail implements Action
{
    public function handle(OrderInput $input, stdClass $reservation): Result
    {
        echo "   [Action] Sending confirmation for reservation {$reservation->ref}...\n";
        return Result::success();
    }

    public function compensate(mixed $input, Context $context): void {}
}

/**
 * 3. A scenario that fails at the final step to demonstrate onStep on failure.
 */
class PaymentGatewayDown implements Action
{
    public function handle(): Result
    {
        echo "   [Action] Contacting payment gateway... unavailable.\n";
        return Result::failure('Payment gateway unavailable');
    }

    public function compensate(mixed $input, Context $context): void {}
}

class OnStepExampleScenario implements ScenarioContract
{
    public function build(Blueprint $plan): void
    {
        $plan->add(ValidateOrderStep::class)
             ->add(ReserveInventory::class)
             ->add(SendConfirmationEmail::class);
    }
}

class OnStepFailingScenario implements ScenarioContract
{
    public function build(Blueprint $plan): void
    {
        $plan->add(ValidateOrderStep::class)
             ->add(ReserveInventory::class)
             ->add(PaymentGatewayDown::class);
    }
}

/**
 * 4. Run with onStep to observe each action as it completes.
 *    The callback receives the action class name, its Result, and the
 *    current Context — so objects recorded by previous steps are
 *    already available when the callback fires.
 */
echo "--- Running with onStep (success) ---\n";

Scenario::for(OnStepExampleScenario::class)
    ->onStep(function (string $action, Result $result, Context $context): void {
        $short = basename(str_replace('\\', '/', $action));
        $status = $result->isSuccess() ? 'OK' : 'FAILED';
        echo "   [onStep] {$short}: {$status}\n";
    })
    ->run(new OrderInput(id: 99))
    ->onSuccess(fn() => print("   Scenario completed!\n"));

echo "\n--- Running with onStep (failure) ---\n";

Scenario::for(OnStepFailingScenario::class)
    ->onStep(function (string $action, Result $result, Context $context): void {
        $short = basename(str_replace('\\', '/', $action));
        $status = $result->isSuccess() ? 'OK' : "FAILED ({$result->error()})";
        echo "   [onStep] {$short}: {$status}\n";
    })
    ->run(new OrderInput(id: 99))
    ->onFailure(fn(string $error) => print("   Scenario failed: {$error}\n"));

/**
 * 5. Multiple onStep callbacks — useful for separating concerns such as
 *    logging, metrics, and audit trails without coupling them together.
 */
echo "\n--- Multiple onStep callbacks ---\n";

Scenario::for(OnStepExampleScenario::class)
    ->onStep(function (string $action, Result $result, Context $context): void {
        $short = basename(str_replace('\\', '/', $action));
        echo "   [Logger]  Step finished: {$short}\n";
    })
    ->onStep(function (string $action, Result $result, Context $context): void {
        $short = basename(str_replace('\\', '/', $action));
        echo "   [Metrics] Incrementing counter for: {$short}\n";
    })
    ->run(new OrderInput(id: 42));
