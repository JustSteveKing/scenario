<?php

declare(strict_types=1);

namespace JustSteveKing\Scenario;

use Closure;
use Illuminate\Contracts\Container\Container;
use JustSteveKing\Scenario\Context\Context;
use JustSteveKing\Scenario\Contracts\Middleware;
use JustSteveKing\Scenario\Engine\Blueprint;
use JustSteveKing\Scenario\Engine\Resolver;
use JustSteveKing\Scenario\Engine\Runner;
use JustSteveKing\Scenario\Support\Result;

class PendingScenario
{
    protected ?Result $result = null;
    protected Context $context;

    /**
     * @var array<class-string<Middleware>>
     */
    protected array $middleware = [];

    public function __construct(
        protected Blueprint $blueprint,
        protected Container $container,
    ) {
        $this->context = new Context();
    }

    /**
     * @param array<class-string<Middleware>> $middleware
     */
    public function through(array $middleware): self
    {
        $this->middleware = $middleware;

        return $this;
    }

    public function run(mixed $input = null): self
    {
        /** @var Resolver $resolver */
        $resolver = $this->container->make(Resolver::class);

        /** @var Runner $runner */
        $runner = $this->container->make(Runner::class, [
            'resolver' => $resolver,
            'container' => $this->container,
            'context' => $this->context,
        ]);

        $pipeline = array_reduce(
            array_reverse($this->middleware),
            function (Closure $next, string $middlewareClass) {
                return function (mixed $input, Context $context) use ($next, $middlewareClass): Result {
                    /** @var Middleware $middleware */
                    $middleware = $this->container->make($middlewareClass);
                    return $middleware->handle($input, $context, $next);
                };
            },
            function (mixed $input, Context $context) use ($runner): Result {
                return $runner->run($this->blueprint, $input);
            },
        );

        $this->result = $pipeline($input, $this->context);

        return $this;
    }

    public function onSuccess(Closure $callback): self
    {
        if ($this->result !== null && $this->result->isSuccess()) {
            $callback($this->context, $this->result->value());
        }

        return $this;
    }

    public function onFailure(Closure $callback): self
    {
        if ($this->result !== null && $this->result->isFailure()) {
            $callback($this->result->error(), $this->context);
        }

        return $this;
    }

    public function result(): ?Result
    {
        return $this->result;
    }

    public function context(): Context
    {
        return $this->context;
    }
}
