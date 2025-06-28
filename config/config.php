<?php

return [
    'app_name' => 'MyDynamicCliApp',
    'default_command' => 'help', // Command to run if no command is specified
    'command_paths' => [
        __DIR__ . '/../commands', // Directory where your command classes are located
    ],
    'greet_default_name' => 'Valued User', // A configurable option for the GreetCommand
   
    'database' => [
        'driver' => 'mysqli', // Explicitly set to mysqli
        'host' => 'localhost',
        'port' => 3306, // Default MySQL port
        'database' => 'rox', // IMPORTANT: Change this to your database name
        'username' => 'root',        // IMPORTANT: Change this to your database username
        'password' => 'root',            // IMPORTANT: Change this to your database password
    ],
    'migrations_path' => __DIR__ . '/../../migrations',
];
