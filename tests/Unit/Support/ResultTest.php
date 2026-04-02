<?php

declare(strict_types=1);

namespace JustSteveKing\Scenario\Tests\Unit\Support;

use JustSteveKing\Scenario\Support\Result;
use JustSteveKing\Scenario\Tests\PackageTestCase;
use PHPUnit\Framework\Attributes\Test;

class ResultTest extends PackageTestCase
{
    #[Test]
    public function it_can_create_a_success_result(): void
    {
        $result = Result::success("data");

        $this->assertTrue($result->isSuccess());
        $this->assertFalse($result->isFailure());
        $this->assertEquals("data", $result->value());
        $this->assertNull($result->error());
    }

    #[Test]
    public function it_can_create_a_failure_result(): void
    {
        $result = Result::failure("Something went wrong");

        $this->assertFalse($result->isSuccess());
        $this->assertTrue($result->isFailure());
        $this->assertEquals("Something went wrong", $result->error());
        $this->assertNull($result->value());
    }
}
