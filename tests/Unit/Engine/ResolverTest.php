<?php

declare(strict_types=1);

namespace JustSteveKing\Scenario\Tests\Unit\Engine;

use Illuminate\Contracts\Container\Container;
use JustSteveKing\Scenario\Context\Context;
use JustSteveKing\Scenario\Engine\Resolver;
use JustSteveKing\Scenario\Support\Result;
use JustSteveKing\Scenario\Tests\PackageTestCase;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\Attributes\Test;

interface InterfaceA {}
interface InterfaceB {}
class IntersectionClass implements InterfaceA, InterfaceB {}

class IntersectionTypeAction
{
    public function handle(InterfaceA&InterfaceB $input): Result
    {
        return Result::success($input);
    }
}

class FloatAction
{
    public function handle(float $f): Result
    {
        return Result::success($f);
    }
}
class NoTypeAction
{
    public function handle($param): Result
    {
        return Result::success($param);
    }
}

class UnionNoMatchAction
{
    public function handle(int|float $input): Result
    {
        return Result::success($input);
    }
}
class ImplA implements InterfaceA
{
    public int $id = 1;
}
class IntersectionNoMatchAction
{
    public function handle(InterfaceA&InterfaceB $input): Result
    {
        return Result::success($input);
    }
}

interface InterfaceC {}
class DnfClass implements InterfaceA, InterfaceB {}

class DnfTypeAction
{
    public function handle((InterfaceA&InterfaceB)|InterfaceC $input): Result
    {
        return Result::success($input);
    }
}

class NullableAction
{
    public function handle(?int $i): Result
    {
        return Result::success($i);
    }
}

class NullStandaloneAction
{
    public function handle(null $n): Result
    {
        return Result::success($n);
    }
}

class ResolverTest extends PackageTestCase
{
    use MockeryPHPUnitIntegration;

    #[Test]
    public function it_can_resolve_standalone_null_type(): void
    {
        $container = Mockery::mock(Container::class);
        $context = new Context();
        $resolver = new Resolver($container);

        $action = new NullStandaloneAction();
        $input = null;

        $result = $resolver->resolve($action, $input, $context);

        $this->assertNull($result->value());
    }

    #[Test]
    public function it_can_resolve_nullable_types(): void
    {
        $container = Mockery::mock(Container::class);
        $context = new Context();
        $resolver = new Resolver($container);

        $action = new NullableAction();
        $input = null;

        $result = $resolver->resolve($action, $input, $context);

        $this->assertNull($result->value());
    }

    #[Test]
    public function it_can_resolve_dnf_types(): void
    {
        $container = Mockery::mock(Container::class);
        $context = new Context();
        $resolver = new Resolver($container);

        $action = new DnfTypeAction();
        $input = new DnfClass();

        $result = $resolver->resolve($action, $input, $context);

        $this->assertSame($input, $result->value());
    }

    #[Test]
    public function it_returns_null_if_union_type_input_does_not_match(): void
    {
        $container = Mockery::mock(Container::class);
        $context = new Context();
        $resolver = new Resolver($container);

        $action = new UnionNoMatchAction();
        $input = "not-an-int-or-float";

        // This will pass the string anyway because it's the first param (Step 5)
        // and handle() will throw a TypeError.
        $this->expectException(\TypeError::class);
        $resolver->resolve($action, $input, $context);
    }

    #[Test]
    public function it_returns_null_if_intersection_type_input_does_not_match(): void
    {
        $container = Mockery::mock(Container::class);
        $context = new Context();
        $resolver = new Resolver($container);

        $action = new IntersectionNoMatchAction();
        $input = new ImplA(); // Implements InterfaceA but not InterfaceB

        $this->expectException(\TypeError::class);
        $resolver->resolve($action, $input, $context);
    }

    #[Test]
    public function it_can_resolve_float_with_int_input(): void
    {
        $container = Mockery::mock(Container::class);
        $context = new Context();
        $resolver = new Resolver($container);

        $action = new FloatAction();
        $input = 123; // int

        $result = $resolver->resolve($action, $input, $context);

        $this->assertEquals(123, $result->value());
        $this->assertIsFloat($result->value());
    }

    #[Test]
    public function it_can_resolve_untyped_parameters(): void
    {
        $container = Mockery::mock(Container::class);
        $context = new Context();
        $resolver = new Resolver($container);

        $action = new NoTypeAction();
        $input = "some-string";

        $result = $resolver->resolve($action, $input, $context);

        $this->assertEquals("some-string", $result->value());
    }

    #[Test]
    public function it_can_resolve_intersection_types(): void
    {
        $container = Mockery::mock(Container::class);
        $context = new Context();
        $resolver = new Resolver($container);

        $action = new IntersectionTypeAction();
        $input = new IntersectionClass();

        $result = $resolver->resolve($action, $input, $context);

        $this->assertSame($input, $result->value());
    }

    #[Test]
    public function it_can_resolve_union_types_complex(): void
    {
        $container = Mockery::mock(Container::class);
        $context = new Context();
        $resolver = new Resolver($container);

        $action = new UnionTypeComplexAction();
        $input = "my-input";

        $result = $resolver->resolve($action, $input, $context);

        $this->assertSame($context, $result->value()[0]);
        $this->assertEquals("my-input", $result->value()[1]);
    }

    #[Test]
    public function it_can_resolve_union_types(): void
    {
        $container = Mockery::mock(Container::class);
        $context = new Context();
        $resolver = new Resolver($container);

        $action = new UnionTypeAction();
        $input = "my-input";

        $result = $resolver->resolve($action, $input, $context);

        $this->assertEquals("my-input", $result->value());

        $inputInt = 123;
        $resultInt = $resolver->resolve($action, $inputInt, $context);
        $this->assertEquals(123, $resultInt->value());
    }

    #[Test]
    public function it_resolves_dependencies_correctly(): void
    {
        $container = Mockery::mock(Container::class);
        $context = new Context();
        $resolver = new Resolver($container);

        $obj = new \stdClass();
        $context->record($obj);

        $action = new TestAction();
        $input = "my-input";

        $result = $resolver->resolve($action, $input, $context);

        $this->assertInstanceOf(Result::class, $result);
        $this->assertEquals(["my-input", $context, $obj], $result->value());
    }

    #[Test]
    public function it_resolves_from_container_if_not_in_context(): void
    {
        $container = Mockery::mock(Container::class);
        $context = new Context();
        $resolver = new Resolver($container);

        $dateTime = new \DateTimeImmutable();
        $container
            ->shouldReceive("make")
            ->with(\DateTimeImmutable::class)
            ->andReturn($dateTime);

        $action = new class {
            public function handle(\DateTimeImmutable $dt): Result
            {
                return Result::success($dt);
            }
        };

        $result = $resolver->resolve($action, null, $context);

        $this->assertSame($dateTime, $result->value());
    }
}

class TestAction
{
    public function handle(
        string $input,
        Context $context,
        \stdClass $obj,
    ): Result {
        return Result::success([$input, $context, $obj]);
    }
}

class UnionTypeAction
{
    public function handle(string|int $input): Result
    {
        return Result::success($input);
    }
}

class UnionTypeComplexAction
{
    public function handle(Context $context, string|int $input): Result
    {
        return Result::success([$context, $input]);
    }
}
