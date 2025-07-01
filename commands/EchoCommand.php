<?php
// commands/EchoCommand.php
namespace masoud4\Commands;

use masoud4\CommandInterface;
use masoud4\Colorizer;

class EchoCommand implements CommandInterface
{
    public function getName(): string
    {
        return 'echo';
    }

    public function getDescription(): string
    {
        return 'Outputs the provided arguments to the console, optionally with color.';
    }

    /**
     * Executes the echo command.
     * Arguments can be colored using --color=<color_name>
     *
     * @param array $args Arguments passed to the command.
     * @param array $config Application configuration.
     * @return int Exit code.
     */
    public function execute(array $args, array $config): int
    {
        // Check for --color option
        $outputColor = '';
        $filteredArgs = [];
        foreach ($args as $arg) {
            if (str_starts_with($arg, '--color=')) {
                $colorName = strtolower(explode('=', $arg, 2)[1]);
                switch ($colorName) {
                    case 'red': $outputColor = Colorizer::RED; break;
                    case 'green': $outputColor = Colorizer::GREEN; break;
                    case 'blue': $outputColor = Colorizer::BLUE; break;
                    case 'yellow': $outputColor = Colorizer::YELLOW; break;
                    case 'cyan': $outputColor = Colorizer::CYAN; break;
                    case 'magenta': $outputColor = Colorizer::MAGENTA; break;
                    default:
                        echo Colorizer::yellow("Warning: Unknown color '{$colorName}'. Using default.") . PHP_EOL;
                        break;
                }
            } else {
                $filteredArgs[] = $arg; // Keep non-color arguments
            }
        }

        $textToEcho = implode(' ', $filteredArgs);

        if (empty($textToEcho)) {
            echo Colorizer::yellow("Usage: php make.php echo <text_to_echo> [--color=<color_name>]") . PHP_EOL;
            return 0;
        }

        echo Colorizer::apply($textToEcho, $outputColor) . PHP_EOL;

        return 0; // Success
    }
}
