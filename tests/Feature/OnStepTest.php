<?php

declare(strict_types=1);

namespace JustSteveKing\Scenario\Tests\Feature;

use JustSteveKing\Scenario\Context\Context;
use JustSteveKing\Scenario\Contracts\Action;
use JustSteveKing\Scenario\Contracts\Scenario as ScenarioContract;
use JustSteveKing\Scenario\Engine\Blueprint;
use JustSteveKing\Scenario\Scenario;
use JustSteveKing\Scenario\Support\Result;
use JustSteveKing\Scenario\Tests\PackageTestCase;
use PHPUnit\Framework\Attributes\Test;

class OnStepTest extends PackageTestCase
{
    #[Test]
    public function it_fires_for_each_successful_action(): void
    {
        $fired = [];

        Scenario::for(TwoStepScenario::class)
            ->onStep(function (string $action, Result $result, Context $context) use (&$fired): void {
                $fired[] = $action;
            })
            ->run();

        $this->assertCount(2, $fired);
        $this->assertSame(StepOneAction::class, $fired[0]);
        $this->assertSame(StepTwoAction::class, $fired[1]);
    }

    #[Test]
    public function it_fires_for_a_failing_action(): void
    {
        $fired = [];

        Scenario::for(FailingStepScenario::class)
            ->onStep(function (string $action, Result $result, Context $context) use (&$fired): void {
                $fired[] = ['action' => $action, 'success' => $result->isSuccess()];
            })
            ->run();

        $this->assertCount(2, $fired);
        $this->assertTrue($fired[0]['success']);
        $this->assertFalse($fired[1]['success']);
    }

    #[Test]
    public function it_receives_the_updated_context_on_success(): void
    {
        $contextValues = [];

        Scenario::for(ContextCapturingScenario::class)
            ->onStep(function (string $action, Result $result, Context $context) use (&$contextValues): void {
                $obj = $context->get(\stdClass::class);
                $contextValues[] = $obj?->value ?? null;
            })
            ->run();

        // After the first step, the value should be recorded in context
        $this->assertSame('from-step', $contextValues[0]);
    }

    #[Test]
    public function it_fires_for_steps_inside_sub_scenarios(): void
    {
        $fired = [];

        Scenario::for(ParentWithSubScenario::class)
            ->onStep(function (string $action, Result $result, Context $context) use (&$fired): void {
                $fired[] = $action;
            })
            ->run();

        $this->assertContains(SubStepAction::class, $fired);
        $this->assertContains(ParentStepAction::class, $fired);
    }

    #[Test]
    public function it_supports_multiple_registered_callbacks(): void
    {
        $firstFired = [];
        $secondFired = [];

        Scenario::for(TwoStepScenario::class)
            ->onStep(function (string $action, Result $result, Context $context) use (&$firstFired): void {
                $firstFired[] = $action;
            })
            ->onStep(function (string $action, Result $result, Context $context) use (&$secondFired): void {
                $secondFired[] = $action;
            })
            ->run();

        $this->assertCount(2, $firstFired);
        $this->assertCount(2, $secondFired);
        $this->assertSame($firstFired, $secondFired);
    }

    #[Test]
    public function it_runs_normally_without_on_step_registered(): void
    {
        $scenario = Scenario::for(TwoStepScenario::class)->run();

        $this->assertTrue($scenario->result()->isSuccess());
    }
}

// --- Supporting classes ---

class StepOneAction implements Action
{
    public function handle(): Result
    {
        return Result::success();
    }

    public function compensate(mixed $input, Context $context): void {}
}

class StepTwoAction implements Action
{
    public function handle(): Result
    {
        return Result::success();
    }

    public function compensate(mixed $input, Context $context): void {}
}

class TwoStepScenario implements ScenarioContract
{
    public function build(Blueprint $plan): void
    {
        $plan->add(StepOneAction::class)->add(StepTwoAction::class);
    }
}

class FailingSecondAction implements Action
{
    public function handle(): Result
    {
        return Result::failure('Step failed');
    }

    public function compensate(mixed $input, Context $context): void {}
}

class FailingStepScenario implements ScenarioContract
{
    public function build(Blueprint $plan): void
    {
        $plan->add(StepOneAction::class)->add(FailingSecondAction::class);
    }
}

class ContextCapturingAction implements Action
{
    public function handle(): Result
    {
        $obj = new \stdClass();
        $obj->value = 'from-step';
        return Result::success($obj);
    }

    public function compensate(mixed $input, Context $context): void {}
}

class ContextCapturingScenario implements ScenarioContract
{
    public function build(Blueprint $plan): void
    {
        $plan->add(ContextCapturingAction::class);
    }
}

class SubStepAction implements Action
{
    public function handle(): Result
    {
        return Result::success();
    }

    public function compensate(mixed $input, Context $context): void {}
}

class SubStepScenario implements ScenarioContract
{
    public function build(Blueprint $plan): void
    {
        $plan->add(SubStepAction::class);
    }
}

class ParentStepAction implements Action
{
    public function handle(): Result
    {
        return Result::success();
    }

    public function compensate(mixed $input, Context $context): void {}
}

class ParentWithSubScenario implements ScenarioContract
{
    public function build(Blueprint $plan): void
    {
        $plan->add(SubStepScenario::class)->add(ParentStepAction::class);
    }
}
