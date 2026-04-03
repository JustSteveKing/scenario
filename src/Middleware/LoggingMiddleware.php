<?php

declare(strict_types=1);

namespace JustSteveKing\Scenario\Middleware;

use Closure;
use JustSteveKing\Scenario\Context\Context;
use JustSteveKing\Scenario\Contracts\Middleware;
use JustSteveKing\Scenario\Support\Result;
use Psr\Log\LoggerInterface;

class LoggingMiddleware implements Middleware
{
    public function __construct(
        protected LoggerInterface $logger,
    ) {}

    public function handle(mixed $input, Context $context, Closure $next): Result
    {
        $start = microtime(true);

        // Find the anonymous class holding the scenario name
        $scenarioName = 'Unknown Scenario';
        foreach ($context->all() as $obj) {
            if (property_exists($obj, 'value') && str_contains($obj::class, 'anonymous')) {
                /** @var string $scenarioName */
                $scenarioName = $obj->value;
                break;
            }
        }

        $this->logger->info("Scenario [{$scenarioName}] started.");

        $result = $next($input, $context);

        $duration = round((microtime(true) - $start) * 1000, 2);

        if ($result->isSuccess()) {
            $this->logger->info("Scenario [{$scenarioName}] completed successfully in {$duration}ms.");
        } else {
            $this->logger->warning("Scenario [{$scenarioName}] failed in {$duration}ms. Error: {$result->error()}");
        }

        return $result;
    }
}
