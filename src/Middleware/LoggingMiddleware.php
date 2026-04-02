<?php

declare(strict_types=1);

namespace JustSteveKing\Scenario\Middleware;

use Closure;
use Psr\Log\LoggerInterface;
use JustSteveKing\Scenario\Context\Context;
use JustSteveKing\Scenario\Contracts\Middleware;
use JustSteveKing\Scenario\Support\Result;

class LoggingMiddleware implements Middleware
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    public function handle(mixed $input, Context $context, Closure $next): Result
    {
        $start = hrtime(true);

        $this->logger->info('Scenario started');

        $result = $next($input, $context);

        $elapsed = (hrtime(true) - $start) / 1_000_000;

        if ($result->isSuccess()) {
            $this->logger->info('Scenario completed', ['duration_ms' => $elapsed]);
        } else {
            $this->logger->warning('Scenario failed', [
                'error' => $result->error(),
                'duration_ms' => $elapsed,
            ]);
        }

        return $result;
    }
}
