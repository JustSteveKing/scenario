<?php

declare(strict_types=1);

namespace JustSteveKing\Scenario\Tests\Unit\Context;

use JustSteveKing\Scenario\Context\Context;
use JustSteveKing\Scenario\Tests\PackageTestCase;
use PHPUnit\Framework\Attributes\Test;
use stdClass;

class ContextTest extends PackageTestCase
{
    #[Test]
    public function it_can_record_and_retrieve_objects(): void
    {
        $context = new Context();
        $obj = new stdClass();
        $obj->id = 123;

        $context->record($obj);

        $this->assertSame($obj, $context->get(stdClass::class));
    }

    #[Test]
    public function it_overwrites_existing_class_entry(): void
    {
        $context = new Context();
        $obj1 = new stdClass();
        $obj1->id = 1;
        $obj2 = new stdClass();
        $obj2->id = 2;

        $context->record($obj1);
        $context->record($obj2);

        $this->assertSame($obj2, $context->get(stdClass::class));
    }

    #[Test]
    public function it_returns_null_if_class_not_found(): void
    {
        $context = new Context();

        $this->assertNull($context->get("NonExistentClass"));
    }

    #[Test]
    public function it_resolves_via_inheritance_and_interfaces(): void
    {
        $context = new Context();
        $obj = new ImplA();

        $context->record($obj);

        // Currently, it's expected to FAIL if it's strict class name matching.
        // Let's see if we want it to succeed.
        $this->assertSame($obj, $context->get(ImplA::class));
        $this->assertSame(
            $obj,
            $context->get(TestInterface::class),
            "Interface lookup should work.",
        );
    }
}

interface TestInterface {}
class ImplA implements TestInterface
{
    public int $id = 1;
}
class ImplB implements TestInterface
{
    public int $id = 2;
}
