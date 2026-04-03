<?php

declare(strict_types=1);

namespace JustSteveKing\Scenario\Console\Commands;

use Illuminate\Console\GeneratorCommand;

class ScenarioMakeCommand extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:scenario';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new Scenario class';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Scenario';

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub(): string
    {
        return __DIR__ . '/../../../stubs/scenario.stub';
    }

    /**
     * Get the default namespace for the class.
     *
     * @param  string  $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace): string
    {
        return $rootNamespace . '\Scenarios';
    }
}
