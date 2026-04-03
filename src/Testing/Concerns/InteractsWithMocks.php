<?php

declare(strict_types=1);

namespace JustSteveKing\Scenario\Testing\Concerns;

use JustSteveKing\Scenario\Support\Result;

trait InteractsWithMocks
{
    /**
     * @var array<string, Result>
     */
    protected array $mocks = [];

    /**
     * Mock the result of a specific step (Action or Scenario) instead of executing it.
     *
     * @param class-string $stepClass The FQCN of the Action or Scenario.
     * @param Result $result The Result to return instead of executing.
     */
    public function mock(string $stepClass, Result $result): self
    {
        $this->mocks[$stepClass] = $result;

        return $this;
    }
}
