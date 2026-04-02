<?php

declare(strict_types=1);

namespace JustSteveKing\Scenario\Engine;

use JustSteveKing\Scenario\Contracts\Action;
use JustSteveKing\Scenario\Contracts\Scenario;

class Blueprint
{
    /**
     * @var array<array{class: class-string<Action|Scenario>, payload: array<string, mixed>}>
     */
    protected array $steps = [];

    /**
     * @param class-string<Action|Scenario> $action
     * @param array<string, mixed> $payload
     */
    public function add(string $action, array $payload = []): self
    {
        $this->steps[] = [
            'class' => $action,
            'payload' => $payload,
        ];

        return $this;
    }

    /**
     * @return array<array{class: class-string<Action|Scenario>, payload: array<string, mixed>}>
     */
    public function getSteps(): array
    {
        return $this->steps;
    }
}
