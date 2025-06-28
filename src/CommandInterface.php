<?php
// src/CommandInterface.php
namespace App;

interface CommandInterface
{
    /**
     * Get the name of the command (e.g., 'greet', 'info').
     * @return string
     */
    public function getName(): string;

    /**
     * Get a short description of the command.
     * @return string
     */
    public function getDescription(): string;

    /**
     * Execute the command logic.
     * @param array $args Arguments passed to the command (excluding the command name itself).
     * @param array $config Application configuration array.
     * @return int Exit code (0 for success, non-zero for error).
     */
    public function execute(array $args, array $config): int;
}
