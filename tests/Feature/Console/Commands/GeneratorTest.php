<?php

declare(strict_types=1);

namespace JustSteveKing\Scenario\Tests\Feature\Console\Commands;

use Illuminate\Support\Facades\File;
use JustSteveKing\Scenario\Tests\PackageTestCase;
use PHPUnit\Framework\Attributes\Test;

class GeneratorTest extends PackageTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Clean up any generated files in the orchestra testbench app path
        if (File::exists(app_path('Scenarios'))) {
            File::deleteDirectory(app_path('Scenarios'));
        }
    }

    #[Test]
    public function it_can_generate_a_scenario(): void
    {
        $this->artisan('make:scenario', ['name' => 'ParseRSSFeed/ParserScenario'])
            ->assertExitCode(0);

        $path = app_path('Scenarios/ParseRSSFeed/ParserScenario.php');
        
        $this->assertTrue(File::exists($path));
        $this->assertStringContainsString('namespace App\Scenarios\ParseRSSFeed;', File::get($path));
        $this->assertStringContainsString('class ParserScenario implements Scenario', File::get($path));
    }

    #[Test]
    public function it_can_generate_a_scenario_action(): void
    {
        $this->artisan('make:scenario-action', ['name' => 'ParseRSSFeed/Actions/FetchFeed'])
            ->assertExitCode(0);

        $path = app_path('Scenarios/ParseRSSFeed/Actions/FetchFeed.php');
        
        $this->assertTrue(File::exists($path));
        $this->assertStringContainsString('namespace App\Scenarios\ParseRSSFeed\Actions;', File::get($path));
        $this->assertStringContainsString('class FetchFeed implements Action', File::get($path));
    }

    #[Test]
    public function it_can_generate_a_scenario_middleware(): void
    {
        $this->artisan('make:scenario-middleware', ['name' => 'Telemetry/LogScenario'])
            ->assertExitCode(0);

        $path = app_path('Scenarios/Telemetry/LogScenario.php');
        
        $this->assertTrue(File::exists($path));
        $this->assertStringContainsString('namespace App\Scenarios\Telemetry;', File::get($path));
        $this->assertStringContainsString('class LogScenario implements Middleware', File::get($path));
    }
}
