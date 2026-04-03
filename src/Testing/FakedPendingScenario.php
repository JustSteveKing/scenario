<?php

declare(strict_types=1);

namespace JustSteveKing\Scenario\Testing;

use Closure;
use Illuminate\Container\Container;
use JustSteveKing\Scenario\Engine\Blueprint;
use JustSteveKing\Scenario\PendingScenario;
use JustSteveKing\Scenario\Support\Result;

class FakedPendingScenario extends PendingScenario
{
    /**
     * @param  class-string  $scenarioClass
     */
    public function __construct(
        protected string $scenarioClass,
        protected ScenarioFake $fake,
    ) {
        parent::__construct(new Blueprint(), new Container());
    }

    public function mock(string $stepClass, Result $result): self
    {
        return $this;
    }

    public function onStep(Closure $callback): self
    {
        return $this;
    }

    public function through(array $middleware): self
    {
        return $this;
    }

    public function run(mixed $input = null): self
    {
        $this->fake->record($this->scenarioClass, $input);

        $this->result = Result::success();

        return $this;
    }

    public function onSuccess(Closure $callback): self
    {
        // Don't execute callback when faked
        return $this;
    }

    public function onFailure(Closure $callback): self
    {
        // Don't execute callback when faked
        return $this;
    }
}
