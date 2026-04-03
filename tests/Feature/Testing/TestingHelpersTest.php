<?php

declare(strict_types=1);

namespace JustSteveKing\Scenario\Tests\Feature\Testing;

use JustSteveKing\Scenario\Context\Context;
use JustSteveKing\Scenario\Contracts\Action;
use JustSteveKing\Scenario\Contracts\Scenario as ScenarioContract;
use JustSteveKing\Scenario\Engine\Blueprint;
use JustSteveKing\Scenario\Scenario;
use JustSteveKing\Scenario\Support\Result;
use JustSteveKing\Scenario\Tests\PackageTestCase;
use PHPUnit\Framework\Attributes\Test;

class TestDto
{
    public function __construct(public string $value) {}
}

class ReturnDto
{
    public function __construct(public string $message) {}
}

class ActionA implements Action
{
    public function handle(TestDto $dto): Result
    {
        return Result::success(new ReturnDto("Actual A"));
    }

    public function compensate(mixed $input, Context $context): void {}
}

class ActionB implements Action
{
    public function handle(TestDto $dto): Result
    {
        return Result::failure("Actual B failed");
    }

    public function compensate(mixed $input, Context $context): void {}
}

class SuccessScenario implements ScenarioContract
{
    public function build(Blueprint $plan): void
    {
        $plan->add(ActionA::class);
    }
}

class FailureScenario implements ScenarioContract
{
    public function build(Blueprint $plan): void
    {
        $plan->add(ActionB::class);
    }
}

class TestingHelpersTest extends PackageTestCase
{
    protected function tearDown(): void
    {
        Scenario::clearFake();
        parent::tearDown();
    }

    #[Test]
    public function it_can_fake_scenarios_and_assert_they_ran(): void
    {
        Scenario::fake();

        $dto = new TestDto('hello');
        Scenario::for(SuccessScenario::class)->run($dto);

        Scenario::assertRan(SuccessScenario::class);
        Scenario::assertRan(SuccessScenario::class, function (TestDto $input) {
            return $input->value === 'hello';
        });

        Scenario::assertNotRan(FailureScenario::class);
    }

    #[Test]
    public function it_can_assert_nothing_ran(): void
    {
        Scenario::fake();

        Scenario::assertNothingRan();
    }

    #[Test]
    public function it_can_mock_a_specific_action_within_a_scenario(): void
    {
        $dto = new TestDto('testing');

        // Normal run would return "Actual A"
        $mockedObj = new \stdClass();
        $mockedObj->value = "Mocked A";

        $pending = Scenario::for(SuccessScenario::class)
            ->mock(ActionA::class, Result::success($mockedObj))
            ->run($dto);

        $pending->assertPassed();
        $this->assertEquals("Mocked A", $pending->context()->get(\stdClass::class)->value);
    }

    #[Test]
    public function it_can_fluently_assert_success_and_context(): void
    {
        $dto = new TestDto('testing');

        Scenario::for(SuccessScenario::class)
            ->run($dto)
            ->assertPassed()
            ->assertContextHas(ReturnDto::class)
            ->assertContextHas(ReturnDto::class, function (ReturnDto $output) {
                return $output->message === 'Actual A';
            });
    }

    #[Test]
    public function it_can_fluently_assert_failure(): void
    {
        $dto = new TestDto('testing');

        Scenario::for(FailureScenario::class)
            ->run($dto)
            ->assertFailed();
    }
}
