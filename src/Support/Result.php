<?php

declare(strict_types=1);

namespace JustSteveKing\Scenario\Support;

class Result
{
    private function __construct(
        protected bool $success,
        protected mixed $value = null,
        protected ?string $error = null,
    ) {}

    public static function success(mixed $value = null): self
    {
        return new self(true, $value);
    }

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
