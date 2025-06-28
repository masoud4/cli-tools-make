<?php
// commands/GreetCommand.php
namespace App\Commands;

use App\CommandInterface;
use App\Colorizer;

class GreetCommand implements CommandInterface
{
    public function getName(): string
    {
        return 'greet';
    }

    public function getDescription(): string
    {
        return 'Greets the given name or a default name from config.';
    }

    /**
     * @param array $args Arguments passed to the command.
     * @param array $config Application configuration.
     * @return int Exit code.
     */
    public function execute(array $args, array $config): int
    {
        // Retrieve default name from config
        $defaultName = $config['greet_default_name'] ?? 'World';
        $name = $args[0] ?? $defaultName;

        echo Colorizer::apply("Hello, " . Colorizer::bold($name) . "!", Colorizer::CYAN) . PHP_EOL;
        return 0; // Success
    }
}
