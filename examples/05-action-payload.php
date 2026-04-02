<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use JustSteveKing\Scenario\Contracts\Action;
use JustSteveKing\Scenario\Contracts\Scenario as ScenarioContract;
use JustSteveKing\Scenario\Context\Context;
use JustSteveKing\Scenario\Engine\Blueprint;
use JustSteveKing\Scenario\Scenario;
use JustSteveKing\Scenario\Support\Result;

/**
 * 1. An action that utilizes payload-driven configuration.
 */
class ConfigurableAction implements Action
{
    /**
     * The Resolver will match these names with the 'payload' array in the blueprint.
     */
    public function handle(string $mode, bool $verbose): Result
    {
        echo "   -> Running in mode: {$mode} (Verbose: " . ($verbose ? 'ON' : 'OFF') . ")\n";
        return Result::success();
    }

    public function compensate(mixed $input, Context $context): void {}
}

/**
 * 2. Define the Scenario using payloads for configuration.
 */
class ConfigExampleScenario implements ScenarioContract
{
    public function build(Blueprint $plan): void
    {
        // We add the same action twice but with different configurations (payloads).
        $plan->add(ConfigurableAction::class, ['mode' => 'PRODUCTION', 'verbose' => false])
             ->add(ConfigurableAction::class, ['mode' => 'DEBUG', 'verbose' => true]);
    }
}

/**
 * 3. Run the scenario.
 */
echo "--- Running Configuration (Payload) Example ---\n";

Scenario::for(ConfigExampleScenario::class)->run();

echo "--- Finished ---\n";
