# Scenario: Type-Safe Business Orchestration

**Scenario** is a logic orchestration engine for PHP 8.5+ designed to replace "Fat Services" and messy "Action" patterns with a strictly typed, railway-oriented flow. It's built to bring structure to complex business processes using a type-safe context, saga rollbacks, and a powerful dependency resolution system.

---

## 🚀 Why Use Scenario?

- **No Magic**: Everything is discovered via Reflection. No unpredictable `__call` or `__get`.
- **Type-Safe Context**: Share data between steps via class-type injection.
- **Saga Pattern**: Automatic rollbacks (`compensate`) if any step fails.
- **Railway Oriented**: Every step returns a `Result` object. Success moves forward; failure stops the line.
- **Middlewares**: Wrap scenarios in transactions, telemetry, or custom logging.
- **Recursive**: Compose complex workflows by nesting scenarios within each other.

---

## 📦 Installation

```bash
composer require juststeveking/scenario
```

---

## 🛠️ Getting Started

### 1. Define your Input Data
Create a simple DTO or class to represent the starting payload of your scenario.

```php
readonly class RegisterUserData
{
    public function __construct(public string $name, public string $email) {}
}
```

### 2. Create an Action
Implement the `Action` contract. Logic goes into the `handle()` method, and reversal logic goes into `compensate()`.

```php
use JustSteveKing\Scenario\Contracts\Action;
use JustSteveKing\Scenario\Context\Context;
use JustSteveKing\Scenario\Support\Result;

class CreateUser implements Action
{
    public function handle(RegisterUserData $data): Result
    {
        // Your logic here...
        return Result::success($user); // Returns an object recorded in Context
    }

    public function compensate(mixed $input, Context $context): void
    {
        // Logic to revert the action...
    }
}
```

### 3. Define the Scenario
Implement the `Scenario` contract and define the steps in the `Blueprint`.

```php
use JustSteveKing\Scenario\Contracts\Scenario;
use JustSteveKing\Scenario\Engine\Blueprint;

class RegistrationScenario implements Scenario
{
    public function build(Blueprint $plan): void
    {
        $plan->add(CreateUser::class)
             ->add(SendWelcomeEmail::class);
    }
}
```

### 4. Run it!
Execute the scenario from your controller or service.

```php
use JustSteveKing\Scenario\Scenario;

Scenario::for(RegistrationScenario::class)
    ->run(new RegisterUserData('John Doe', 'john@example.com'))
    ->onSuccess(fn(Context $context) => ...)
    ->onFailure(fn(string $error) => ...);
```

---

## 📖 Key Concepts & Advanced Usage

### Class-Type Dependency Injection
The engine automatically resolves dependencies for your `handle()` methods. It looks in:
1. The **Scenario Context** (objects returned by previous steps).
2. The **Initial Input** passed to `run()`.
3. The **Action Payload** (defined in the blueprint).
4. The **Laravel Service Container**.

See [Context Sharing Example](examples/03-context-sharing.php).

### Saga Rollbacks (Compensation)
If an action returns `Result::failure($message)`, the engine stops and triggers the `compensate()` method for every successfully completed step in reverse order (LIFO).

See [Saga Compensation Example](examples/02-saga-compensation.php).

### Middleware & Hooks
Wrap your scenarios in pipelines for database transactions, telemetry, or logging.

See [Middleware Example](examples/04-middleware.php).

### Action Configuration (Payloads)
Pass specific data to an action directly in the blueprint. This is perfect for reusing generic actions with different configurations.

See [Action Payload Example](examples/05-action-payload.php).

### Sub-Scenarios (Recursive Flows)
You can add one scenario as a step within another. This allows you to build complex workflows from smaller, independent blocks.

---

## 🧪 Testing

The library is fully tested with PHPUnit.

```bash
composer test
```

For static analysis:

```bash
composer stan
```

---

## 🤝 Contributing

Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

---

## 📜 License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
