<?php

declare(strict_types=1);

namespace JustSteveKing\Scenario\Engine;

use Illuminate\Contracts\Container\Container;
use JustSteveKing\Scenario\Context\Context;
use JustSteveKing\Scenario\Contracts\Action;
use JustSteveKing\Scenario\Contracts\Scenario;
use JustSteveKing\Scenario\Support\Result;

class Runner
{
    /**
     * @var array<Action>
     */
    protected array $history = [];

    public function __construct(
        protected Resolver $resolver,
        protected Container $container,
        protected Context $context,
    ) {}

    public function run(Blueprint $blueprint, mixed $input): Result
    {
        $steps = $blueprint->getSteps();

        foreach ($steps as $step) {
            $stepClass = $step['class'];
            $payload = $step['payload'];

            $instance = $this->container->make($stepClass);

            if ($instance instanceof Scenario) {
                // It's a sub-scenario: resolve its blueprint and run recursively
                $subBlueprint = new Blueprint();
                $instance->build($subBlueprint);

                $result = $this->run($subBlueprint, $input);
                if ($result->isFailure()) {
                    // Compensation is handled at the level of the failure
                    return $result;
                }
                continue;
            }

            // @phpstan-ignore instanceof.alwaysTrue
            if (! $instance instanceof Action) {
                throw new \RuntimeException(sprintf('Step %s must implement Action or Scenario interface.', $stepClass));
            }

            // Execute the step via the Resolver, passing the action's specific payload
            $result = $this->resolver->resolve($instance, $input, $this->context, $payload);

            if (! $result instanceof Result) {
                // To maintain strictness, ensure actions return a Result object
                throw new \RuntimeException(sprintf('Action %s must return a Result object.', $stepClass));
            }

            if ($result->isFailure()) {
                $this->compensate($input);
                return $result;
            }

            // On success, track the step in history
            $this->history[] = $instance;

            // If the result contains a value (an object), save it to the context
            $value = $result->value();
            if (is_object($value)) {
                $this->context->record($value);
            }
        }

        return Result::success();
    }

    protected function compensate(mixed $input): void
    {
        // Saga pattern: trigger compensation in reverse order (LIFO)
        foreach (array_reverse($this->history) as $action) {
            $action->compensate($input, $this->context);
        }
    }
}
