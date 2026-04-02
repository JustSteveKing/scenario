<?php

declare(strict_types=1);

namespace JustSteveKing\Scenario\Tests\Feature;

use Illuminate\Container\Container;
use JustSteveKing\Scenario\Context\Context;
use JustSteveKing\Scenario\Contracts\Action;
use JustSteveKing\Scenario\Contracts\Scenario as ScenarioContract;
use JustSteveKing\Scenario\Engine\Blueprint;
use JustSteveKing\Scenario\Scenario;
use JustSteveKing\Scenario\Support\Result;
use JustSteveKing\Scenario\Tests\PackageTestCase;
use PHPUnit\Framework\Attributes\Test;

class ScenarioTest extends PackageTestCase
{
    #[Test]
    public function it_can_inject_payload_data_into_actions(): void
    {
        $scenario = Scenario::for(PayloadScenario::class)->run();

        $this->assertNotNull($scenario->result());
        $this->assertTrue($scenario->result()->isSuccess());
        $this->assertEquals("Welcome!", $scenario->context()->get(\stdClass::class)->template);
        $this->assertEquals(5, $scenario->context()->get(\stdClass::class)->count);
    }

    #[Test]
    public function it_can_run_sub_scenarios(): void
    {
        $scenario = Scenario::for(ParentScenario::class)->run();

        $this->assertNotNull($scenario->result());
        $this->assertTrue($scenario->result()->isSuccess());
        $this->assertEquals("From Sub", $scenario->context()->get(\stdClass::class)->value);
    }

    #[Test]
    public function it_compensates_sub_scenarios_globally(): void
    {
        // Force failure in ParentAction (which runs after SubScenario)
        $scenario = Scenario::for(ParentScenarioWithFailure::class)->run();

        $this->assertNotNull($scenario->result());
        $this->assertTrue($scenario->result()->isFailure());

        // We need a way to check if SubScenario actions were compensated.
        // Let's use a static or shared state in test.
        $this->assertTrue(SubAction::$compensated);
    }

    #[Test]
    public function it_can_run_with_middleware(): void
    {
        $scenario = Scenario::for(EmptyScenario::class)
            ->through([IncrementMiddleware::class])
            ->run();

        $this->assertNotNull($scenario->result());
        $this->assertTrue($scenario->result()->isSuccess());
        $this->assertEquals(1, $scenario->context()->get(\stdClass::class)->value);
    }

    #[Test]
    public function it_can_fail_from_middleware(): void
    {
        $scenario = Scenario::for(EmptyScenario::class)
            ->through([FailureMiddleware::class])
            ->run();

        $this->assertNotNull($scenario->result());
        $this->assertTrue($scenario->result()->isFailure());
        $this->assertEquals("Middleware failure", $scenario->result()->error());
    }

    #[Test]
    public function it_can_inject_interfaces_between_steps(): void
    {
        $scenario = Scenario::for(InterfaceScenario::class)->run();

        $this->assertNotNull($scenario->result());
        $this->assertTrue($scenario->result()->isSuccess());
    }

    protected function setUp(): void
    {
        parent::setUp();
        // Reset container to avoid state bleed
        Container::setInstance(new Container());

        SubAction::$compensated = false;
    }

    #[Test]
    public function it_can_run_a_successful_scenario(): void
    {
        $successCalled = false;
        $failureCalled = false;

        $scenario = Scenario::for(PurchaseOrderScenario::class)
            ->run(new PurchaseData(id: 123))
            ->onSuccess(function ($context) use (&$successCalled) {
                $successCalled = true;
            })
            ->onFailure(function ($error) use (&$failureCalled) {
                $failureCalled = true;
            });

        $this->assertNotNull($scenario->result());
        $this->assertTrue($scenario->result()->isSuccess());
        $this->assertTrue($successCalled);
        $this->assertFalse($failureCalled);
    }

    #[Test]
    public function it_compensates_on_failure(): void
    {
        $successCalled = false;
        $failureCalled = false;
        $errorMsg = null;

        // Using ID 999 causes ChargePaymentAction to fail
        $scenario = Scenario::for(PurchaseOrderScenario::class)
            ->run(new PurchaseData(id: 999))
            ->onSuccess(function ($context) use (&$successCalled) {
                $successCalled = true;
            })
            ->onFailure(function ($error) use (&$failureCalled, &$errorMsg) {
                $failureCalled = true;
                $errorMsg = $error;
            });

        $this->assertNotNull($scenario->result());
        $this->assertTrue($scenario->result()->isFailure());
        $this->assertFalse($successCalled);
        $this->assertTrue($failureCalled);
        $this->assertEquals("Payment Failed", $errorMsg);
    }

    #[Test]
    public function it_throws_exception_if_action_returns_non_result(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            sprintf(
                "Action %s must return a Result object.",
                InvalidAction::class,
            ),
        );

        Scenario::for(InvalidScenario::class)->run();
    }

    #[Test]
    public function it_throws_exception_if_dependency_cannot_be_resolved(): void
    {
        $this->expectException(
            \Illuminate\Contracts\Container\BindingResolutionException::class,
        );

        Scenario::for(DependencyNotMetScenario::class)->run();
    }

    #[Test]
    public function it_handles_empty_scenarios(): void
    {
        $scenario = Scenario::for(EmptyScenario::class)->run();

        $this->assertNotNull($scenario->result());
        $this->assertTrue($scenario->result()->isSuccess());
    }
}

class PurchaseData
{
    public function __construct(public int $id) {}
}

class CreateOrderAction implements Action
{
    public array $history = [];

    public function handle(PurchaseData $data): Result
    {
        if ($data->id === 0) {
            return Result::failure("Invalid ID");
        }

        $this->history[] = "Order Created: " . $data->id;
        return Result::success(new \stdClass());
    }

    public function compensate(mixed $input, Context $context): void
    {
        $this->history[] = "Order Reverted: " . $input->id;
    }
}

class ChargePaymentAction implements Action
{
    public array $history = [];

    public function handle(\stdClass $order, PurchaseData $data): Result
    {
        if ($data->id === 999) {
            return Result::failure("Payment Failed");
        }

        $this->history[] = "Payment Charged for: " . $data->id;
        return Result::success();
    }

    public function compensate(mixed $input, Context $context): void
    {
        $this->history[] = "Payment Reverted: " . $input->id;
    }
}

class PurchaseOrderScenario implements ScenarioContract
{
    public function build(Blueprint $plan): void
    {
        $plan->add(CreateOrderAction::class)->add(ChargePaymentAction::class);
    }
}

class InvalidAction implements Action
{
    /** @phpstan-ignore return.type */
    public function handle(): string
    {
        return "Not a Result";
    }

    public function compensate(mixed $input, Context $context): void {}
}

class InvalidScenario implements ScenarioContract
{
    public function build(Blueprint $plan): void
    {
        /** @phpstan-ignore argument.type */
        $plan->add(InvalidAction::class);
    }
}

interface MissingDependency {}

class DependencyNotMetAction implements Action
{
    public function handle(MissingDependency $neverBound): Result
    {
        return Result::success();
    }

    public function compensate(mixed $input, Context $context): void {}
}

class DependencyNotMetScenario implements ScenarioContract
{
    public function build(Blueprint $plan): void
    {
        $plan->add(DependencyNotMetAction::class);
    }
}

class EmptyScenario implements ScenarioContract
{
    public function build(Blueprint $plan): void {}
}

interface UserInterface
{
    public function getName(): string;
}
class ConcreteUser implements UserInterface
{
    public function getName(): string
    {
        return "John";
    }
}

class CreateUserAction implements Action
{
    public function handle(): Result
    {
        return Result::success(new ConcreteUser());
    }
    public function compensate(mixed $input, Context $context): void {}
}

class CheckUserAction implements Action
{
    public function handle(UserInterface $user): Result
    {
        if ($user->getName() === "John") {
            return Result::success();
        }
        return Result::failure("Wrong user");
    }
    public function compensate(mixed $input, Context $context): void {}
}

class InterfaceScenario implements ScenarioContract
{
    public function build(Blueprint $plan): void
    {
        $plan->add(CreateUserAction::class)->add(CheckUserAction::class);
    }
}

class IncrementMiddleware implements \JustSteveKing\Scenario\Contracts\Middleware
{
    public function handle(mixed $input, Context $context, \Closure $next): Result
    {
        $count = $context->get(\stdClass::class) ?? new \stdClass();
        /** @phpstan-ignore property.notFound */
        $count->value = ($count->value ?? 0) + 1;
        $context->record($count);

        return $next($input, $context);
    }
}

class FailureMiddleware implements \JustSteveKing\Scenario\Contracts\Middleware
{
    public function handle(mixed $input, Context $context, \Closure $next): Result
    {
        return Result::failure("Middleware failure");
    }
}

class SubAction implements Action
{
    public static bool $compensated = false;

    public function handle(): Result
    {
        $obj = new \stdClass();
        $obj->value = "From Sub";
        return Result::success($obj);
    }
    public function compensate(mixed $input, Context $context): void
    {
        self::$compensated = true;
    }
}

class SubScenario implements ScenarioContract
{
    public function build(Blueprint $plan): void
    {
        $plan->add(SubAction::class);
    }
}

class ParentScenario implements ScenarioContract
{
    public function build(Blueprint $plan): void
    {
        $plan->add(SubScenario::class);
    }
}

class ParentActionWithFailure implements Action
{
    public function handle(\stdClass $obj): Result
    {
        return Result::failure("Forced failure");
    }
    public function compensate(mixed $input, Context $context): void {}
}

class ParentScenarioWithFailure implements ScenarioContract
{
    public function build(Blueprint $plan): void
    {
        $plan->add(SubScenario::class)->add(ParentActionWithFailure::class);
    }
}

class PayloadAction implements Action
{
    public function handle(string $template, int $count): Result
    {
        $obj = new \stdClass();
        /** @phpstan-ignore property.notFound */
        $obj->template = $template;
        /** @phpstan-ignore property.notFound */
        $obj->count = $count;
        return Result::success($obj);
    }
    public function compensate(mixed $input, Context $context): void {}
}

class PayloadScenario implements ScenarioContract
{
    public function build(Blueprint $plan): void
    {
        $plan->add(PayloadAction::class, ['template' => 'Welcome!', 'count' => 5]);
    }
}
