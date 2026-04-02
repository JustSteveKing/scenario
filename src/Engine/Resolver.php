<?php

declare(strict_types=1);

namespace JustSteveKing\Scenario\Engine;

use Illuminate\Contracts\Container\Container;
use JustSteveKing\Scenario\Context\Context;
use ReflectionMethod;
use ReflectionNamedType;

/**
 * The Resolver is the orchestration brain of the Scenario engine.
 *
 * It uses reflection to dynamically resolve the arguments of an action's
 * `handle()` method. It follows a strict prioritization strategy:
 *
 * 1.  The `Context` object itself if type-hinted.
 * 2.  The `payload` specifically passed in the blueprint for this step.
 * 3.  Any object already recorded in the `Context` that matches the type hint.
 * 4.  The initial `input` if it matches the type hint or is an untyped first parameter.
 * 5.  The Laravel Service Container for any other type-hinted classes.
 */
class Resolver
{
    public function __construct(
        protected Container $container,
    ) {}

    /**
     * Resolve the dependencies for an action's `handle()` method and invoke it.
     *
     * @param object $action The instance of the Action being executed.
     * @param mixed $input The initial data payload passed to the scenario.
     * @param Context $context The current shared state across all scenario steps.
     * @param array<string, mixed> $payload Optional configuration defined in the blueprint.
     * @return mixed The result of the `handle()` invocation.
     */
    public function resolve(object $action, mixed $input, Context $context, array $payload = []): mixed
    {
        $reflection = new ReflectionMethod($action, 'handle');
        $parameters = $reflection->getParameters();

        $dependencies = [];
        $inputUsed = false;

        foreach ($parameters as $parameter) {
            $type = $parameter->getType();

            // 1. Resolve Context itself
            if ($type instanceof ReflectionNamedType && $type->getName() === Context::class) {
                $dependencies[] = $context;
                continue;
            }

            // 2. Resolve from payload by name
            $paramName = $parameter->getName();
            if (array_key_exists($paramName, $payload)) {
                $dependencies[] = $payload[$paramName];
                continue;
            }

            // 3. Try to resolve from Context registry
            if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
                /** @var class-string $className */
                $className = $type->getName();
                $contextObject = $context->get($className);
                if ($contextObject !== null) {
                    $dependencies[] = $contextObject;
                    continue;
                }
            }

            // 4. Try to resolve from input if not already used
            if (!$inputUsed && $this->typeMatches($type, $input)) {
                $dependencies[] = $input;
                $inputUsed = true;
                continue;
            }

            // 5. Try to resolve from Laravel Container (only for Named Types that aren't built-in)
            if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
                /** @var class-string $className */
                $className = $type->getName();
                $dependencies[] = $this->container->make($className);
                continue;
            }

            // 6. Fallback: If it's the first param and we haven't used input, try passing it anyway
            if (!$inputUsed && count($dependencies) === 0) {
                $dependencies[] = $input;
                $inputUsed = true;
                continue;
            }

            $dependencies[] = null;
        }

        return $reflection->invoke($action, ...$dependencies);
    }

    /**
     * Check if a value matches a reflection type.
     */
    protected function typeMatches(?\ReflectionType $type, mixed $value): bool
    {
        if ($type === null) {
            return true;
        }

        if ($value === null) {
            return $type->allowsNull();
        }

        if ($type instanceof \ReflectionNamedType) {
            $typeName = $type->getName();
            if ($type->isBuiltin()) {
                // PHP 8.2+ allows 'null' as a standalone type name in ReflectionNamedType
                if ($typeName === 'null') {
                    return false; // Already checked $value === null above
                }
                return gettype($value) === $typeName || ($typeName === 'float' && is_int($value));
            }
            return $value instanceof $typeName;
        }

        if ($type instanceof \ReflectionUnionType) {
            foreach ($type->getTypes() as $unionType) {
                if ($this->typeMatches($unionType, $value)) {
                    return true;
                }
            }
            return false;
        }

        if ($type instanceof \ReflectionIntersectionType) {
            foreach ($type->getTypes() as $intersectionType) {
                if (!$this->typeMatches($intersectionType, $value)) {
                    return false;
                }
            }
            return true;
        }

        return false;
    }
}
