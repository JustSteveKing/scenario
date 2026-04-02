<?php

declare(strict_types=1);

$examples = [
    '01-basic-scenario.php',
    '02-saga-compensation.php',
    '03-context-sharing.php',
    '04-middleware.php',
    '05-action-payload.php',
];

foreach ($examples as $example) {
    $path = __DIR__ . '/' . $example;

    if (!file_exists($path)) {
        echo "Example not found: {$example}\n";
        continue;
    }

    echo "\n" . str_repeat('=', 40) . "\n";
    echo "RUNNING: {$example}\n";
    echo str_repeat('=', 40) . "\n";

    passthru("php {$path}");
}

echo "\n" . str_repeat('=', 40) . "\n";
echo "All examples completed!\n";
echo str_repeat('=', 40) . "\n";
