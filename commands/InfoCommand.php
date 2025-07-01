<?php
// commands/InfoCommand.php
namespace masoud4\Commands;

use masoud4\CommandInterface;
use masoud4\Colorizer;

class InfoCommand implements CommandInterface
{
    public function getName(): string
    {
        return 'info';
    }

    public function getDescription(): string
    {
        return 'Displays system information.';
    }

    /**
     * @param array $args Arguments passed to the command.
     * @param array $config Application configuration.
     * @return int Exit code.
     */
    public function execute(array $args, array $config): int
    {
        echo Colorizer::apply("--- System Information for " . Colorizer::bold($config['app_name'] ?? 'Your App') . " ---", Colorizer::YELLOW, '', Colorizer::BOLD) . PHP_EOL;
        echo Colorizer::green("PHP Version: ") . phpversion() . PHP_EOL;
        echo Colorizer::green("Operating System: ") . PHP_OS . PHP_EOL;
        echo Colorizer::green("Current Directory: ") . getcwd() . PHP_EOL;

        if (!empty($args)) {
            echo Colorizer::yellow("Received extra arguments for info command: ") . implode(', ', $args) . PHP_EOL;
        }

        echo Colorizer::apply("-----------------------------------------", Colorizer::YELLOW, '', Colorizer::BOLD) . PHP_EOL;
        return 0; // Success
    }
}
