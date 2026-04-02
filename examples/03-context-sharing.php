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
 * Multiple custom classes that will be recorded in the context.
 */
readonly class OrderId
{
    public function __construct(public string $id) {}
}
readonly class CustomerId
{
    public function __construct(public int $id) {}
}

/**
 * 1. Action that registers an OrderId in the context.
 */
class IdentifyOrder implements Action
{
    public function handle(string $orderNumber): Result
    {
        echo "Identifying Order: {$orderNumber}\n";
        return Result::success(new OrderId($orderNumber));
    }

    public function compensate(mixed $input, Context $context): void {}
}

/**
 * 2. Action that registers a CustomerId in the context.
 */
class IdentifyCustomer implements Action
{
    public function handle(string $orderNumber): Result
    {
        echo "Identifying Customer for Order: {$orderNumber}\n";
        return Result::success(new CustomerId(101));
    }

    public function compensate(mixed $input, Context $context): void {}
}

/**
 * 3. Action that requires BOTH objects from the context.
 */
class LinkOrderToCustomer implements Action
{
    /**
     * The engine will resolve OrderId and CustomerId from the Scenario Context
     * because they were recorded in previous steps.
     */
    public function handle(OrderId $order, CustomerId $customer): Result
    {
        echo "Linking Order '{$order->id}' to Customer #{$customer->id}.\n";
        return Result::success();
    }

    public function compensate(mixed $input, Context $context): void {}
}

/**
 * 4. Define the scenario.
 */
class OrderLinkingScenario implements ScenarioContract
{
    public function build(Blueprint $plan): void
    {
        $plan->add(IdentifyOrder::class)
             ->add(IdentifyCustomer::class)
             ->add(LinkOrderToCustomer::class);
    }
}

/**
 * 5. Run the scenario.
 */
echo "--- Starting Context Sharing Example ---\n";

Scenario::for(OrderLinkingScenario::class)
    ->run('ORD-12345')
    ->onSuccess(function (Context $context) {
        $order = $context->get(OrderId::class);
        $customer = $context->get(CustomerId::class);

        echo "Success! Scenario finished with Order: {$order->id} and Customer: {$customer->id}.\n";
    });

echo "--- Finished ---\n";
