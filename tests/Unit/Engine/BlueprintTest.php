<?php

declare(strict_types=1);

namespace JustSteveKing\Scenario\Tests\Unit\Engine;

use JustSteveKing\Scenario\Contracts\Action;
use JustSteveKing\Scenario\Engine\Blueprint;
use JustSteveKing\Scenario\Tests\PackageTestCase;
use PHPUnit\Framework\Attributes\Test;

class BlueprintTest extends PackageTestCase
{
    #[Test]
    public function it_can_add_and_retrieve_steps(): void
    {
        $blueprint = new Blueprint();

        /** @var class-string<Action> $action1 */
        $action1 = "Action1";
        /** @var class-string<Action> $action2 */
        $action2 = "Action2";

        $blueprint->add($action1, ['foo' => 'bar'])->add($action2);

        $this->assertEquals([
            ['class' => 'Action1', 'payload' => ['foo' => 'bar']],
            ['class' => 'Action2', 'payload' => []],
        ], $blueprint->getSteps());
    }
}
