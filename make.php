<?php
// app.php

// 1. Load Composer's autoloader
require_once __DIR__ . '/vendor/autoload.php';

// 2. Load configuration
$config = require_once __DIR__ . '/config/config.php';

// 3. Instantiate the CLI application
use masoud4\CliApp;
$app = new CliApp($config);

// 4. Dynamically load commands from specified paths in config
// This is where commands are "dynamically added" at runtime.
try {
    $app->loadCommandsFromPaths($config['command_paths']);
} catch (Throwable $e) {
    // Handle potential errors during command loading (e.g., Reflection errors)
    echo \masoud4\Colorizer::red("Failed to load commands: " . $e->getMessage()) . PHP_EOL;
    exit(1);
}

// 5. Run the application
exit($app->run());
