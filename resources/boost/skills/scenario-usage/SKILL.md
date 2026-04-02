# Skill: Scenario Usage

This skill provides expert guidance for using the **Scenario** package — a type-safe, railway-oriented business logic orchestration engine for PHP 8.5+.

## Core Philosophy

- **No Magic**: No `__call` or `__get`. Everything is discovered via Reflection.
- **Type-Safe Context**: Data is shared between steps via class-type injection.
- **Saga Pattern**: Automatic LIFO rollbacks (`compensate`) on failure.
- **Railway Oriented**: Success moves forward; `Result::failure()` stops the line.

## Architecture & File Map

- `src/Contracts/Action.php`: Interface for units of work.
- `src/Contracts/Scenario.php`: Interface for defining workflows.
- `src/Engine/Blueprint.php`: Stores the sequence of steps.
- `src/Engine/Resolver.php`: Resolves `handle()` method dependencies.
- `src/Engine/Runner.php`: Executes the workflow and handles Saga rollbacks.
- `src/Support/Result.php`: The outcome object (`success` or `failure`).

## Implementation Instructions

### 1. Defining an Action

Every action MUST implement `JustSteveKing\Scenario\Contracts\Action`.
It MUST provide a `handle()` method with a dynamic signature.

```php
use JustSteveKing\Scenario\Contracts\Action;
use JustSteveKing\Scenario\Context\Context;
use JustSteveKing\Scenario\Support\Result;

class ProcessPayment implements Action
{
    /**
     * The Resolver automatically injects dependencies:
     * - From Scenario Context (objects from previous steps)
     * - From Initial Input
     * - From Action Payload (defined in blueprint)
     * - From Laravel Container
     */
    public function handle(Order $order, PaymentGateway $gateway, string $currency): Result
    {
        $payment = $gateway->charge($order->total, $currency);

        if (!$payment->successful()) {
            return Result::failure("Payment failed: " . $payment->error());
        }

        return Result::success($payment); // Recorded in Context as Payment object
    }

    public function compensate(mixed $input, Context $context): void
    {
        // Revert logic here (e.g., refund if possible)
    }
}
```

### 2. Defining a Scenario

Every scenario MUST implement `JustSteveKing\Scenario\Contracts\Scenario`.

```php
use JustSteveKing\Scenario\Contracts\Scenario;
use JustSteveKing\Scenario\Engine\Blueprint;

class CheckoutScenario implements Scenario
{
    public function build(Blueprint $plan): void
    {
        $plan->add(CreateOrder::class)
             ->add(ProcessPayment::class, ['currency' => 'USD']) // Payload injection
             ->add(SendConfirmation::class);
    }
}
```

### 3. Running a Scenario

Use the `Scenario` class for a fluent execution experience.

```php
use JustSteveKing\Scenario\Scenario;

Scenario::for(CheckoutScenario::class)
    ->through([DatabaseTransactionMiddleware::class]) // Optional Middleware
    ->run($cartData)
    ->onSuccess(fn(Context $context) => ...)
    ->onFailure(fn(string $error) => ...);
```

## Advanced Features

### Middleware
Implement `JustSteveKing\Scenario\Contracts\Middleware` to wrap execution in transactions or telemetry.

### Sub-Scenarios
You can add a Scenario class directly to a `Blueprint`. Rollbacks are handled globally across the parent and sub-scenario.

```php
$plan->add(ValidationScenario::class);
```

### Context Injection
If an Action returns an object in `Result::success($obj)`, that object is recorded by its class name (or interface) in the `Context` and can be type-hinted by any subsequent step.

## Best Practices

1. **Strict Typing**: Always use return types and parameter types in `handle()` methods.
2. **Immutable Input**: Use readonly DTOs for the initial scenario input.
3. **Atomic Actions**: Keep actions small and focused on a single responsibility.
4. **Descriptive Failures**: Provide meaningful error messages in `Result::failure()`.
5. **Idempotent Compensations**: Ensure `compensate()` can be called safely even if it partially ran before.
