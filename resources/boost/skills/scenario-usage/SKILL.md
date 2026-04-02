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
- `src/Contracts/Middleware.php`: Interface for wrapping scenario execution.
- `src/Engine/Blueprint.php`: Stores the sequence of steps.
- `src/Engine/Resolver.php`: Resolves `handle()` method dependencies.
- `src/Engine/Runner.php`: Executes the workflow and handles Saga rollbacks.
- `src/Middleware/LoggingMiddleware.php`: Built-in PSR-3 logging middleware.
- `src/Middleware/DatabaseTransactionMiddleware.php`: Built-in DB transaction middleware.
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
    ->through([LoggingMiddleware::class, DatabaseTransactionMiddleware::class])
    ->onStep(fn(string $action, Result $result, Context $context) => ...)
    ->run($cartData)
    ->onSuccess(fn(Context $context) => ...)
    ->onFailure(fn(string $error) => ...);
```

## Advanced Features

### Middleware

Implement `JustSteveKing\Scenario\Contracts\Middleware` to wrap the entire scenario execution in a cross-cutting concern — transactions, telemetry, rate-limiting, etc.

```php
use JustSteveKing\Scenario\Contracts\Middleware;

class MyMiddleware implements Middleware
{
    public function handle(mixed $input, Context $context, Closure $next): Result
    {
        // Before execution
        $result = $next($input, $context);
        // After execution
        return $result;
    }
}
```

Register one or more middleware classes via `->through()`. They wrap execution in the order given (outermost first):

```php
Scenario::for(CheckoutScenario::class)
    ->through([LoggingMiddleware::class, DatabaseTransactionMiddleware::class])
    ->run($input);
```

#### Built-in Middleware

**`LoggingMiddleware`** — wraps scenario execution with PSR-3 log entries.

- Logs `info` when the scenario starts.
- Logs `info` with `duration_ms` on success.
- Logs `warning` with `error` and `duration_ms` on failure.
- Resolves `Psr\Log\LoggerInterface` from the container. In Laravel this is automatic; the framework already binds it to `Illuminate\Log\LogManager`.

```php
use JustSteveKing\Scenario\Middleware\LoggingMiddleware;

Scenario::for(CheckoutScenario::class)
    ->through([LoggingMiddleware::class])
    ->run($input);
```

**`DatabaseTransactionMiddleware`** — wraps scenario execution in a database transaction.

- Calls `beginTransaction()` before execution.
- Calls `commit()` if the scenario returns `Result::success()`.
- Calls `rollBack()` if the scenario returns `Result::failure()`.
- Resolves `Illuminate\Database\ConnectionInterface` from the container, which maps to the default database connection in Laravel.
- Works alongside the Saga pattern: the Saga handles domain-level compensation (e.g. reversing an email send); the transaction middleware handles persistence-level atomicity.

```php
use JustSteveKing\Scenario\Middleware\DatabaseTransactionMiddleware;

Scenario::for(CheckoutScenario::class)
    ->through([DatabaseTransactionMiddleware::class])
    ->run($input);
```

**Recommended ordering** — place `LoggingMiddleware` outermost so it captures the full duration including transaction overhead:

```php
->through([LoggingMiddleware::class, DatabaseTransactionMiddleware::class])
```

### Step Hooks (`onStep`)

`onStep` fires a callback after every individual action completes, whether it succeeded or failed. Use it for per-step observability: audit trails, metrics, debugging.

```php
Scenario::for(CheckoutScenario::class)
    ->onStep(function (string $action, Result $result, Context $context): void {
        // $action  — fully-qualified class name of the action that just ran
        // $result  — its Result (check isSuccess() / isFailure())
        // $context — the context at this point; on success the action's value
        //            is already recorded here
    })
    ->run($input);
```

**Key behaviours:**

- Fires for every `Action` in the scenario, including actions inside sub-scenarios.
- On success: fires after the action's return value has been recorded in context, so `$context->get(...)` reflects the new state.
- On failure: fires before saga compensation begins, capturing the context at the moment of failure.
- Does NOT fire for sub-scenario boundaries — only for individual actions.
- Multiple `onStep` registrations are all called per step, in registration order.

```php
// Separate concerns without coupling them
Scenario::for(CheckoutScenario::class)
    ->onStep(fn($action, $result, $ctx) => Log::info('step', ['action' => $action]))
    ->onStep(fn($action, $result, $ctx) => Metrics::increment('scenario.step'))
    ->run($input);
```

`onStep` must be registered **before** `->run()`. `onSuccess` and `onFailure` are called **after** `->run()`.

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
6. **Middleware ordering**: `LoggingMiddleware` outermost, `DatabaseTransactionMiddleware` innermost.
7. **No exceptions**: Return `Result::failure()` instead of throwing — the railway pattern means `onStep`, `onFailure`, and saga compensation all fire correctly only when failures flow through the Result.
