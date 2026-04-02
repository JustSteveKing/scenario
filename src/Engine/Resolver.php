<?php

declare(strict_types=1);

namespace JustSteveKing\Scenario\Engine;

use Illuminate\Contracts\Container\Container;
use JustSteveKing\Scenario\Context\Context;
use ReflectionMethod;
use ReflectionNamedType;

class Resolver
{
    public function __construct(
        protected Container $container,
    ) {}

    /**
     * Resolve and invoke the handle method on the given action.
     *
     * @param array<string, mixed> $payload
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
