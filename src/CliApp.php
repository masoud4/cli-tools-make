<?php
namespace masoud4;

use ReflectionClass;
use Throwable;

class CliApp
{
    private string $appName;
    private string $defaultCommand;
    private array $config;

    /** @var CommandInterface[] */
    private array $commands = [];

    /**
     * @param array $config The application configuration array.
     */
    public function __construct(array $config)
    {
        $this->config = $config;
        $this->appName = $config['app_name'] ?? 'MyCliApp';
        $this->defaultCommand = $config['default_command'] ?? 'help';

        // Add a built-in help command
        $this->addCommand(new class implements CommandInterface {
            private array $appCommands = []; // To store a reference to the main app's commands

            // This is a bit of a trick for built-in commands that need app context.
            // In a larger framework, you might use a service container.
            public function __construct(?array $appCommands = null) {
                if ($appCommands) {
                    $this->appCommands = $appCommands;
                }
            }

            public function getName(): string { return 'help'; }
            public function getDescription(): string { return 'Displays this help message for ' . Colorizer::bold($this->appCommands['app_name'] ?? 'your app') . '.'; }
            public function execute(array $args, array $config): int {
                // Adjust script name to match user's common usage (make.php)
                $scriptName = 'make.php'; // Updated to reflect user's common entry point name

                echo Colorizer::bold(Colorizer::blue("Usage: php {$scriptName} <command> [arguments]")) . PHP_EOL;
                echo PHP_EOL;
                // FIX: Changed Colorizer::underline to Colorizer::apply with UNDERLINE constant
                echo Colorizer::apply("Available Commands:", '', '', Colorizer::UNDERLINE) . PHP_EOL;

                // Sort commands alphabetically by name for cleaner output
                uksort($this->appCommands['commands'], function($a, $b) {
                    return strcmp($a, $b);
                });

                foreach ($this->appCommands['commands'] as $command) {
                    echo "  " . Colorizer::green(str_pad($command->getName(), 15)) . " " . $command->getDescription() . PHP_EOL;
                }
                echo PHP_EOL;
                echo Colorizer::yellow("Run 'php {$scriptName} <command> --help' for specific command usage (if supported by command).") . PHP_EOL;
                return 0;
            }
        });
    }

    /**
     * Registers a command with the application.
     * @param CommandInterface $command The command instance to add.
     */
    public function addCommand(CommandInterface $command): void
    {
        // For the help command, inject the app's commands
        if ($command->getName() === 'help' && method_exists($command, '__construct')) {
            $reflection = new ReflectionClass($command);
            $constructor = $reflection->getConstructor();
            // Check if the constructor expects an array for commands (our hacky way for help command)
            if ($constructor && $constructor->getNumberOfParameters() > 0 &&
                $constructor->getParameters()[0]->getType()?->getName() === 'array') {
                $this->commands[$command->getName()] = $reflection->newInstanceArgs([['commands' => &$this->commands, 'app_name' => $this->appName]]);
            } else {
                $this->commands[$command->getName()] = $command;
            }
        } else {
            $this->commands[$command->getName()] = $command;
        }
    }

    /**
     * Discovers and loads command classes from specified directories.
     * This method enables dynamic command addition.
     *
     * @param array $paths An array of directories to scan for command classes.
     * @throws \ReflectionException
     */
    public function loadCommandsFromPaths(array $paths): void
    {
        foreach ($paths as $path) {
            if (!is_dir($path)) {
                echo Colorizer::yellow("Warning: Command path '{$path}' does not exist.") . PHP_EOL;
                continue;
            }

            $files = scandir($path);
            foreach ($files as $file) {
                if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
                    $className = pathinfo($file, PATHINFO_FILENAME);
                    // Assuming PSR-4 namespace App\Commands\ for command files
                    $fullClassName = "masoud4\\Commands\\" . $className;

                    // Check if the class exists and implements CommandInterface
                    if (class_exists($fullClassName)) {
                        $reflection = new ReflectionClass($fullClassName);
                        if ($reflection->implementsInterface(CommandInterface::class) && !$reflection->isAbstract()) {
                            try {
                                $this->addCommand($reflection->newInstance()); // Commands typically don't need constructor args unless specific config
                                // If your commands need global config passed to constructor, you'd do:
                                // $this->addCommand($reflection->newInstance($this->config));
                            } catch (Throwable $e) {
                                echo Colorizer::red("Error instantiating command '{$fullClassName}': " . $e->getMessage()) . PHP_EOL;
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Runs the CLI application, parsing arguments and executing the appropriate command.
     * @return int The exit code of the executed command or an error code.
     */
    public function run(): int
    {
        global $argv; // Get command-line arguments

        // Remove the script name from arguments
        $args = array_slice($argv, 1);
        $commandName = $this->defaultCommand;

        if (!empty($args)) {
            $commandName = array_shift($args); // First argument is the command name
        }

        if (isset($this->commands[$commandName])) {
            $command = $this->commands[$commandName];
            try {
                // Pass the full config array to the command's execute method
                return $command->execute($args, $this->config);
            } catch (Throwable $e) {
                echo Colorizer::red("Error executing command '" . Colorizer::bold($commandName) . "': ") . $e->getMessage() . PHP_EOL;
                return 1; // General error
            }
        } else {
            echo Colorizer::red("Unknown command: " . Colorizer::bold($commandName)) . PHP_EOL;
            // Adjust script name to match user's common usage (make.php)
            echo Colorizer::yellow("Type 'php make.php help' for a list of commands.") . PHP_EOL; // Updated here
            return 1; // Command not found error
        }
    }
}
