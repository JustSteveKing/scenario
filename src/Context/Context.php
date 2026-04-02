<?php

declare(strict_types=1);

namespace JustSteveKing\Scenario\Context;

class Context
{
    /**
     * @var array<string, object>
     */
    protected array $registry = [];

    /**
     * Add an object to the registry.
     */
    public function record(object $obj): void
    {
        $this->registry[$obj::class] = $obj;
    }

    /**
     * Retrieve an object from the registry.
     *
     * @param class-string $class
     */
    public function get(string $class): ?object
    {
        // 1. Direct match (fast path)
        if (isset($this->registry[$class])) {
            return $this->registry[$class];
        }

        // 2. Inheritance and interface lookup
        // We iterate in reverse to find the most recently recorded object that matches.
        foreach (array_reverse($this->registry) as $obj) {
            if ($obj instanceof $class) {
                return $obj;
            }
        }

        return null;
    }
}
