<?php

declare(strict_types=1);

namespace JustSteveKing\Scenario\Contracts;

use JustSteveKing\Scenario\Engine\Blueprint;

interface Scenario
{
    /**
     * Define the business process steps.
     */
    public function build(Blueprint $plan): void;
}
