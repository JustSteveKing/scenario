<?php

declare(strict_types=1);

namespace JustSteveKing\Scenario\Tests\Unit\Engine;

use Illuminate\Contracts\Container\Container;
use JustSteveKing\Scenario\Context\Context;
use JustSteveKing\Scenario\Contracts\Action;
use JustSteveKing\Scenario\Engine\Blueprint;
use JustSteveKing\Scenario\Engine\Resolver;
use JustSteveKing\Scenario\Engine\Runner;
use JustSteveKing\Scenario\Support\Result;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use JustSteveKing\Scenario\Tests\PackageTestCase;
use PHPUnit\Framework\Attributes\Test;

class RunnerTest extends PackageTestCase
{
    use MockeryPHPUnitIntegration;

    #[Test]
    public function it_provides_modified_context_to_compensate(): void
    {
        $container = Mockery::mock(Container::class);
        $resolver = Mockery::mock(Resolver::class);
        $context = new Context();
        $runner = new Runner($resolver, $container, $context);

        $blueprint = new Blueprint();
        /** @var class-string<Action> $actionClass1 */
        $actionClass1 = "Action1";
        /** @var class-string<Action> $actionClass2 */
        $actionClass2 = "Action2";
        $blueprint->add($actionClass1)->add($actionClass2);

        $action1 = Mockery::mock(Action::class);
        $action2 = Mockery::mock(Action::class);
        $container->shouldReceive("make")->with("Action1")->andReturn($action1);
        $container->shouldReceive("make")->with("Action2")->andReturn($action2);

        $obj = new \stdClass();
        $resolver->shouldReceive("resolve")
            ->andReturn(Result::success($obj), Result::failure("Fail"));

        $action1->shouldReceive("compensate")->with("input", $context)->once()->andReturnUsing(function ($input, $context) use ($obj) {
            // Verify context has the object from the successful step
            $this->assertSame($obj, $context->get(\stdClass::class));
        });

        $runner->run($blueprint, "input");
    }

    #[Test]
    public function it_runs_successful_steps_and_records_results(): void
    {
        $container = Mockery::mock(Container::class);
        $resolver = Mockery::mock(Resolver::class);
        $context = new Context();
        $runner = new Runner($resolver, $container, $context);

        $blueprint = new Blueprint();
        /** @var class-string<Action> $actionClass */
        $actionClass = "MyAction";
        $blueprint->add($actionClass);

        $action = Mockery::mock(Action::class);
        $container->shouldReceive("make")->with("MyAction")->andReturn($action);

        $obj = new \stdClass();
        $resolver
            ->shouldReceive("resolve")
            ->with($action, "input-data", $context, [])
            ->andReturn(Result::success($obj));

        $result = $runner->run($blueprint, "input-data");

        $this->assertTrue($result->isSuccess());
        $this->assertSame($obj, $context->get(\stdClass::class));
    }

    #[Test]
    public function it_stops_and_compensates_on_failure(): void
    {
        $container = Mockery::mock(Container::class);
        $resolver = Mockery::mock(Resolver::class);
        $context = new Context();
        $runner = new Runner($resolver, $container, $context);

        $blueprint = new Blueprint();
        /** @var class-string<Action> $actionClass1 */
        $actionClass1 = "Action1";
        /** @var class-string<Action> $actionClass2 */
        $actionClass2 = "Action2";
        $blueprint->add($actionClass1)->add($actionClass2);

        $action1 = Mockery::mock(Action::class);
        $action2 = Mockery::mock(Action::class);
        $container->shouldReceive("make")->with("Action1")->andReturn($action1);
        $container->shouldReceive("make")->with("Action2")->andReturn($action2);

        $resolver
            ->shouldReceive("resolve")
            ->with($action1, "input", $context, [])
            ->andReturn(Result::success());

        $resolver
            ->shouldReceive("resolve")
            ->with($action2, "input", $context, [])
            ->andReturn(Result::failure("Stop!"));

        // Expect compensation for action1 in reverse order
        $action1->shouldReceive("compensate")->with("input", $context)->once();
        $action2->shouldNotReceive("compensate");

        $result = $runner->run($blueprint, "input");

        $this->assertTrue($result->isFailure());
        $this->assertEquals("Stop!", $result->error());
    }
}
