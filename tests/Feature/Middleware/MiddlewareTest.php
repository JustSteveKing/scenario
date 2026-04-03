<?php

declare(strict_types=1);

namespace JustSteveKing\Scenario\Tests\Feature\Middleware;

use Illuminate\Database\ConnectionInterface;
use JustSteveKing\Scenario\Context\Context;
use JustSteveKing\Scenario\Contracts\Action;
use JustSteveKing\Scenario\Engine\Blueprint;
use JustSteveKing\Scenario\Middleware\DatabaseTransactionMiddleware;
use JustSteveKing\Scenario\Middleware\LoggingMiddleware;
use JustSteveKing\Scenario\Scenario;
use JustSteveKing\Scenario\Support\Result;
use JustSteveKing\Scenario\Tests\PackageTestCase;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\LoggerInterface;

class SuccessAction implements Action
{
    public static bool $compensated = false;
    public function handle(): Result
    {
        return Result::success();
    }
    public function compensate(mixed $input, Context $context): void
    {
        self::$compensated = true;
    }
}

class CrashAction implements Action
{
    public function handle(): Result
    {
        throw new \Exception("Boom");
    }
    public function compensate(mixed $input, Context $context): void {}
}

class MiddlewareTestScenario implements \JustSteveKing\Scenario\Contracts\Scenario
{
    public function build(Blueprint $plan): void
    {
        $plan->add(SuccessAction::class);
    }
}

class CrashScenario implements \JustSteveKing\Scenario\Contracts\Scenario
{
    public function build(Blueprint $plan): void
    {
        $plan->add(SuccessAction::class)->add(CrashAction::class);
    }
}

class MiddlewareTest extends PackageTestCase
{
    use MockeryPHPUnitIntegration;

    #[Test]
    public function it_logs_scenario_lifecycle(): void
    {
        $logger = Mockery::mock(LoggerInterface::class);
        $logger->shouldReceive('info')->with(Mockery::pattern('/started/'))->once();
        $logger->shouldReceive('info')->with(Mockery::pattern('/completed successfully/'))->once();

        $this->app->instance(LoggerInterface::class, $logger);

        Scenario::for(MiddlewareTestScenario::class)
            ->through([LoggingMiddleware::class])
            ->run();
    }

    #[Test]
    public function it_wraps_execution_in_transaction(): void
    {
        $db = Mockery::mock(ConnectionInterface::class);
        $db->shouldReceive('beginTransaction')->once();
        $db->shouldReceive('commit')->once();
        $db->shouldNotReceive('rollBack');

        $this->app->instance(ConnectionInterface::class, $db);

        Scenario::for(MiddlewareTestScenario::class)
            ->through([DatabaseTransactionMiddleware::class])
            ->run();
    }

    #[Test]
    public function it_triggers_compensation_when_action_throws_exception(): void
    {
        SuccessAction::$compensated = false;

        $scenario = Scenario::for(CrashScenario::class)->run();

        $this->assertTrue($scenario->result()->isFailure());
        $this->assertEquals("Boom", $scenario->result()->error());

        $this->assertTrue(SuccessAction::$compensated);
    }
}
