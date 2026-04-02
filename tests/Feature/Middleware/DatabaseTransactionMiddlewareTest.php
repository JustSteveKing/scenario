<?php

declare(strict_types=1);

namespace JustSteveKing\Scenario\Tests\Feature\Middleware;

use Illuminate\Database\ConnectionInterface;
use JustSteveKing\Scenario\Context\Context;
use JustSteveKing\Scenario\Middleware\DatabaseTransactionMiddleware;
use JustSteveKing\Scenario\Support\Result;
use JustSteveKing\Scenario\Tests\PackageTestCase;
use PHPUnit\Framework\Attributes\Test;

class DatabaseTransactionMiddlewareTest extends PackageTestCase
{
    #[Test]
    public function it_commits_on_success(): void
    {
        $db = $this->createMock(ConnectionInterface::class);

        $db->expects($this->once())->method('beginTransaction');
        $db->expects($this->once())->method('commit');
        $db->expects($this->never())->method('rollBack');

        $middleware = new DatabaseTransactionMiddleware($db);
        $next = fn(mixed $input, Context $context): Result => Result::success();

        $result = $middleware->handle(null, new Context(), $next);

        $this->assertTrue($result->isSuccess());
    }

    #[Test]
    public function it_rolls_back_on_failure(): void
    {
        $db = $this->createMock(ConnectionInterface::class);

        $db->expects($this->once())->method('beginTransaction');
        $db->expects($this->never())->method('commit');
        $db->expects($this->once())->method('rollBack');

        $middleware = new DatabaseTransactionMiddleware($db);
        $next = fn(mixed $input, Context $context): Result => Result::failure('Something failed');

        $result = $middleware->handle(null, new Context(), $next);

        $this->assertTrue($result->isFailure());
        $this->assertSame('Something failed', $result->error());
    }

    #[Test]
    public function it_returns_the_result_unchanged(): void
    {
        $db = $this->createStub(ConnectionInterface::class);

        $value = new \stdClass();
        $middleware = new DatabaseTransactionMiddleware($db);
        $next = fn(mixed $input, Context $context): Result => Result::success($value);

        $result = $middleware->handle(null, new Context(), $next);

        $this->assertSame($value, $result->value());
    }
}
