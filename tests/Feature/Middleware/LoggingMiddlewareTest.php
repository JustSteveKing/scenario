<?php

declare(strict_types=1);

namespace JustSteveKing\Scenario\Tests\Feature\Middleware;

use Closure;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\LoggerInterface;
use JustSteveKing\Scenario\Context\Context;
use JustSteveKing\Scenario\Middleware\LoggingMiddleware;
use JustSteveKing\Scenario\Support\Result;
use JustSteveKing\Scenario\Tests\PackageTestCase;

class LoggingMiddlewareTest extends PackageTestCase
{
    #[Test]
    public function it_logs_start_and_success(): void
    {
        $logger = $this->createMock(LoggerInterface::class);

        $logger->expects($this->exactly(2))
            ->method('info')
            ->willReturnCallback(function (string $message, array $context = []) {
                static $call = 0;
                $call++;

                if ($call === 1) {
                    $this->assertSame('Scenario [Unknown Scenario] started.', $message);
                }

                if ($call === 2) {
                    $this->assertStringContainsString('completed successfully', $message);
                }
            });

        $logger->expects($this->never())->method('warning');

        $middleware = new LoggingMiddleware($logger);
        $next = fn(mixed $input, Context $context): Result => Result::success();

        $result = $middleware->handle(null, new Context(), $next);

        $this->assertTrue($result->isSuccess());
    }

    #[Test]
    public function it_logs_start_and_failure(): void
    {
        $logger = $this->createMock(LoggerInterface::class);

        $logger->expects($this->once())
            ->method('info')
            ->with('Scenario [Unknown Scenario] started.');

        $logger->expects($this->once())
            ->method('warning')
            ->willReturnCallback(function (string $message) {
                $this->assertStringContainsString('failed', $message);
                $this->assertStringContainsString('Something went wrong', $message);
            });

        $middleware = new LoggingMiddleware($logger);
        $next = fn(mixed $input, Context $context): Result => Result::failure('Something went wrong');

        $result = $middleware->handle(null, new Context(), $next);

        $this->assertTrue($result->isFailure());
        $this->assertSame('Something went wrong', $result->error());
    }

    #[Test]
    public function it_passes_input_and_context_to_next(): void
    {
        $logger = $this->createStub(LoggerInterface::class);

        $input = new \stdClass();
        $context = new Context();
        $capturedInput = null;
        $capturedContext = null;

        $next = function (mixed $i, Context $c) use (&$capturedInput, &$capturedContext): Result {
            $capturedInput = $i;
            $capturedContext = $c;
            return Result::success();
        };

        $middleware = new LoggingMiddleware($logger);
        $middleware->handle($input, $context, Closure::fromCallable($next));

        $this->assertSame($input, $capturedInput);
        $this->assertSame($context, $capturedContext);
    }
}
