<?php

declare(strict_types=1);

namespace JustSteveKing\Scenario\Support;

/**
 * Represents the outcome of a scenario step.
 *
 * This implementation follows the Railway Oriented Programming pattern,
 * where every step returns either a Success (with data) or a Failure (with an error).
 */
class Result
{
    private function __construct(
        protected bool $success,
        protected mixed $value = null,
        protected ?string $error = null,
    ) {}

    /**
     * Create a successful outcome.
     *
     * @param mixed|null $value An optional return value. If it's an object, it's recorded in the Context.
     */
    public static function success(mixed $value = null): self
    {
        return new self(true, $value);
    }

    /**
     * Create a failed outcome.
     *
     * @param string $error The reason for failure. This will stop the scenario line.
     */
    public static function failure(string $error): self
    {
        return new self(false, null, $error);
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }
    public function isFailure(): bool
    {
        return !$this->success;
    }
    public function value(): mixed
    {
        return $this->value;
    }
    public function error(): ?string
    {
        return $this->error;
    }
}
