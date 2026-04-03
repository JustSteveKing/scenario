<?php

declare(strict_types=1);

namespace JustSteveKing\Scenario\Engine;

use Illuminate\Contracts\Container\Container;
use JustSteveKing\Scenario\Context\Context;
use JustSteveKing\Scenario\Contracts\Action;
use JustSteveKing\Scenario\Contracts\Scenario;
use JustSteveKing\Scenario\Support\Result;

/**
 * The Runner is the execution heart of the Scenario engine.
 *
 * It iterates through a scenario's `Blueprint` and invokes each `Action`
 * through the `Resolver`. If a `Result::isFailure()` is returned, the
 * Runner will halt and initiate the Saga (LIFO) rollback by calling
 * the `compensate()` method for all previously successful actions.
 */
class Runner
{
    /**
     * @var array<Action> The stack of successfully completed actions for rollback.
     */
    protected array $history = [];

    /**
     * @var array<int, \Closure(string, Result, Context): void>
     */
    protected array $stepCallbacks = [];

    /**
     * @var array<string, Result>
     */
    protected array $mocks = [];

    /**
     * @param array<int, \Closure(string, Result, Context): void> $stepCallbacks
     * @param array<string, Result> $mocks
     */
    public function __construct(
        protected Resolver $resolver,
        protected Container $container,
        protected Context $context,
        array $stepCallbacks = [],
        array $mocks = [],
    ) {
        $this->stepCallbacks = $stepCallbacks;
        $this->mocks = $mocks;
    }

    /**
     * Run the scenario's blueprint steps.
     *
     * @param Blueprint $blueprint The sequence of actions and sub-scenarios.
     * @param mixed $input The initial start-up data for the scenario.
     * @return Result The final success or failure state of the entire process.
     */
    public function run(Blueprint $blueprint, mixed $input): Result
    {
        $steps = $blueprint->getSteps();

        foreach ($steps as $step) {
            $stepClass = $step['class'];
            $payload = $step['payload'];

            if (isset($this->mocks[$stepClass])) {
                $result = $this->mocks[$stepClass];

                // Track mock in history if it's an action and it succeeded
                if ($result->isSuccess()) {
                    // Try to make instance for history tracking if we can
                    try {
                        $instance = $this->container->make($stepClass);
                        if ($instance instanceof Action) {
                            $this->history[] = $instance;
                        }
                    } catch (\Exception $e) {
                    }

                    $value = $result->value();
                    if (is_object($value)) {
                        $this->context->record($value);
                    }
                }

                if ($result->isFailure()) {
                    $this->fireStepCallbacks($stepClass, $result);
                    $this->compensate($input);
                    return $result;
                }

                $this->fireStepCallbacks($stepClass, $result);
                continue;
            }

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
            try {
                $result = $this->resolver->resolve($instance, $input, $this->context, $payload);

                if (! $result instanceof Result) {
                    // To maintain strictness, ensure actions return a Result object
                    throw new \RuntimeException(sprintf('Action %s must return a Result object.', $stepClass));
                }
            } catch (\Illuminate\Contracts\Container\BindingResolutionException|\RuntimeException $e) {
                // Structural errors should bubble up
                throw $e;
            } catch (\Throwable $e) {
                // Business logic errors should trigger compensation
                $result = Result::failure($e->getMessage());
            }

            if ($result->isFailure()) {
                $this->fireStepCallbacks($stepClass, $result);
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

            $this->fireStepCallbacks($stepClass, $result);
        }

        return Result::success();
    }

    /**
     * Invoke all registered step callbacks with the completed action's details.
     */
    private function fireStepCallbacks(string $stepClass, Result $result): void
    {
        foreach ($this->stepCallbacks as $callback) {
            $callback($stepClass, $result, $this->context);
        }
    }

    /**
     * Execute the Saga rollback in LIFO order.
     *
     * @param mixed $input The same input provided during scenario start.
     */
    protected function compensate(mixed $input): void
    {
        // Saga pattern: trigger compensation in reverse order (LIFO)
        foreach (array_reverse($this->history) as $action) {
            $action->compensate($input, $this->context);
        }
    }
}
