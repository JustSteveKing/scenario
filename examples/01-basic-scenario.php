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
 * 1. Define the input data for the scenario.
 */
readonly class RegisterUserData
{
    public function __construct(
        public string $name,
        public string $email,
    ) {}
}

/**
 * 2. Define the actions. Each action must implement JustSteveKing\Scenario\Contracts\Action.
 * Its logic resides in a handle() method, which is resolved by type-hinting its dependencies.
 */
class CreateUser implements Action
{
    public function handle(RegisterUserData $data): Result
    {
        echo "Creating user: {$data->name} ({$data->email})\n";

        // Let's simulate returning a newly created User object.
        $user = new stdClass();
        $user->id = 1;
        $user->name = $data->name;
        $user->email = $data->email;

        // On success, the Result value (if it's an object) is automatically recorded in the Scenario Context.
        return Result::success($user);
    }

    public function compensate(mixed $input, Context $context): void
    {
        echo "Reverting user creation for: {$input->email}\n";
    }
}

class SendWelcomeEmail implements Action
{
    // The handle method can type-hint the output of previous steps (stdClass) and the initial input (RegisterUserData).
    public function handle(stdClass $user, RegisterUserData $data): Result
    {
        echo "Sending welcome email to: {$user->email}\n";

        return Result::success();
    }

    public function compensate(mixed $input, Context $context): void
    {
        echo "Reverting welcome email (if possible)\n";
    }
}

/**
 * 3. Define the Scenario by implementing the build() method.
 */
class UserRegistrationScenario implements ScenarioContract
{
    public function build(Blueprint $plan): void
    {
        $plan->add(CreateUser::class)
             ->add(SendWelcomeEmail::class);
    }
}

/**
 * 4. Execute the scenario.
 */
echo "--- Starting User Registration ---\n";

Scenario::for(UserRegistrationScenario::class)
    ->run(new RegisterUserData(name: 'John Doe', email: 'john@example.com'))
    ->onSuccess(fn(Context $context) => print("Scenario completed successfully!\n"))
    ->onFailure(fn(string $error) => print("Scenario failed: $error\n"));

echo "--- Finished ---\n";
