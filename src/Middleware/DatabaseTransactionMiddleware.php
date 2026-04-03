<?php

declare(strict_types=1);

namespace JustSteveKing\Scenario\Middleware;

use Closure;
use Illuminate\Database\ConnectionInterface;
use JustSteveKing\Scenario\Context\Context;
use JustSteveKing\Scenario\Contracts\Middleware;
use JustSteveKing\Scenario\Support\Result;

class DatabaseTransactionMiddleware implements Middleware
{
    public function __construct(
        protected ConnectionInterface $connection,
    ) {}

    public function handle(mixed $input, Context $context, Closure $next): Result
    {
        $this->connection->beginTransaction();

        try {
            $result = $next($input, $context);

            if ($result->isFailure()) {
                $this->connection->rollBack();
                return $result;
            }

            $this->connection->commit();
            return $result;
        } catch (\Throwable $e) {
            $this->connection->rollBack();
            throw $e;
        }
    }
}
