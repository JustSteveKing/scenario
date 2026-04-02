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
        private readonly ConnectionInterface $db,
    ) {}

    public function handle(mixed $input, Context $context, Closure $next): Result
    {
        $this->db->beginTransaction();

        $result = $next($input, $context);

        $result->isSuccess()
            ? $this->db->commit()
            : $this->db->rollBack();

        return $result;
    }
}
