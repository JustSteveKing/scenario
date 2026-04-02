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
 * Define the input for the order process.
 */
readonly class PurchaseData
{
    public function __construct(
        public int $id,
        public int $amount,
    ) {}
}

/**
 * 1. Action to create an order entry.
 */
class CreateOrder implements Action
{
    public function handle(PurchaseData $data): Result
    {
        echo "Creating order with ID: {$data->id}\n";

        $order = new stdClass();
        $order->id = $data->id;
        $order->status = 'PENDING';

        return Result::success($order);
    }

    public function compensate(mixed $input, Context $context): void
    {
        echo "--- Compensation Triggered: Deleting Order {$input->id} ---\n";
    }
}

/**
 * 2. Action to process payment, which we will force to fail.
 */
class ProcessPayment implements Action
{
    public function handle(stdClass $order, PurchaseData $data): Result
    {
        echo "Processing payment for order {$order->id} for amount \${$data->amount}...\n";

        // Simulate a payment failure.
        return Result::failure('Payment processing failed due to insufficient funds.');
    }

    public function compensate(mixed $input, Context $context): void
    {
        // This won't run because this step was the one that failed.
        // Compensation only runs for steps that successfully completed.
        echo "--- Reverting Payment (should not see this) ---\n";
    }
}

/**
 * 3. Define the Order Scenario.
 */
class PurchaseOrderScenario implements ScenarioContract
{
    public function build(Blueprint $plan): void
    {
        $plan->add(CreateOrder::class)
             ->add(ProcessPayment::class);
    }
}

/**
 * 4. Execute the scenario and observe the rollback behavior.
 */
echo "--- Starting Purchase Order (This is expected to fail) ---\n";

Scenario::for(PurchaseOrderScenario::class)
    ->run(new PurchaseData(id: 42, amount: 500))
    ->onSuccess(fn() => print("Order placed successfully!\n"))
    ->onFailure(function (string $error) {
        echo "Order failed with error: {$error}\n";
        echo "Check the logs above; compensation should have been triggered for CreateOrder.\n";
    });

echo "--- Finished ---\n";
