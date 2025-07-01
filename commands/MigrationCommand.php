<?php
namespace masoud4\Commands;

use masoud4\CommandInterface;
use masoud4\Colorizer;
use mysqli;
use mysqli_sql_exception; 

class MigrationCommand implements CommandInterface
{
    private ?mysqli $mysqli = null; 
    private array $config;

    public function getName(): string
    {
        return 'migrate';
    }

    public function getDescription(): string
    {
        return 'Manages database migrations (make, run, rollback, reset) using mysqli.';
    }

    /**
     * Executes the migration command based on sub-commands.
     *
     * @param array $args Arguments passed to the command.
     * @param array $config Application configuration.
     * @return int Exit code.
     */
    public function execute(array $args, array $config): int
    {
        // Enable mysqli error reporting for development
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

        $this->config = $config;

        $subCommand = array_shift($args); // e.g., 'make', 'run', 'rollback', 'reset'

        if (empty($subCommand)) {
            echo Colorizer::yellow("Usage: php make.php migrate <sub-command> [arguments]") . PHP_EOL;
            echo Colorizer::yellow("Available sub-commands: make, run, rollback [--step=N], reset") . PHP_EOL;
            return 1;
        }

        try {
            switch ($subCommand) {
                case 'make':
                    return $this->makeMigration($args);
                case 'run':
                    return $this->runMigrations();
                case 'rollback':
                    return $this->rollbackMigration($args); // Pass args for --step
                case 'reset':
                    return $this->resetMigrations(); // New reset method
                default:
                    echo Colorizer::red("Unknown migrate sub-command: " . Colorizer::bold($subCommand)) . PHP_EOL;
                    return 1;
            }
        } catch (mysqli_sql_exception $e) {
            echo Colorizer::red("Database error: " . $e->getMessage()) . PHP_EOL;
            return 1;
        } catch (\Exception $e) { // Catch other general exceptions
            echo Colorizer::red("An error occurred: " . $e->getMessage()) . PHP_EOL;
            return 1;
        }
    }

    /**
     * Connects to the database using mysqli.
     * @return mysqli
     * @throws mysqli_sql_exception If connection fails.
     */
    private function getMysqli(): mysqli // Method name changed from getPdo to getMysqli
    {
        if ($this->mysqli) {
            return $this->mysqli;
        }

        $dbConfig = $this->config['database'] ?? [];

        $host = $dbConfig['host'] ?? 'localhost';
        $username = $dbConfig['username'] ?? 'root';
        $password = $dbConfig['password'] ?? '';
        $database = $dbConfig['database'] ?? '';
        $port = $dbConfig['port'] ?? 3306;

        try {
            $this->mysqli = new mysqli($host, $username, $password, $database, $port);
            if ($this->mysqli->connect_error) {
                throw new mysqli_sql_exception("Connection failed: " . $this->mysqli->connect_error, $this->mysqli->connect_errno);
            }
            echo Colorizer::green("Database connected successfully.") . PHP_EOL;
            return $this->mysqli;
        } catch (mysqli_sql_exception $e) {
            echo Colorizer::red("Database connection failed: " . $e->getMessage()) . PHP_EOL;
            throw $e; // Re-throw for outer error handling
        }
    }

    /**
     * Ensures the migrations table exists.
     */
    private function ensureMigrationsTableExists(): void
    {
        try {
            $mysqli = $this->getMysqli();
            $sql = "CREATE TABLE IF NOT EXISTS migrations (
                id INT AUTO_INCREMENT PRIMARY KEY,
                migration VARCHAR(255) NOT NULL UNIQUE,
                batch INT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )";
            $mysqli->query($sql); // Using query for DDL
            echo Colorizer::blue("Migrations table checked/created.") . PHP_EOL;

            // --- NEW DEBUGGING: List current tables in the connected database ---
            echo Colorizer::yellow("--- Tables in current database ---") . PHP_EOL;
            $result = $mysqli->query("SHOW TABLES");
            if ($result) {
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_array(MYSQLI_NUM)) {
                        echo Colorizer::yellow("  - " . $row[0]) . PHP_EOL;
                    }
                } else {
                    echo Colorizer::yellow("  No tables found in this database.") . PHP_EOL;
                }
                $result->free();
            } else {
                echo Colorizer::red("  Failed to list tables: " . $mysqli->error) . PHP_EOL;
            }
            echo Colorizer::yellow("----------------------------------") . PHP_EOL;
            // --- END NEW DEBUGGING ---

        } catch (mysqli_sql_exception $e) {
            echo Colorizer::red("Error creating migrations table: " . $e->getMessage()) . PHP_EOL;
            exit(1);
        }
    }

    /**
     * Creates a new migration file.
     * @param array $args Arguments (expected: migration name).
     * @return int
     */
    private function makeMigration(array $args): int
    {
        $migrationName = $args[0] ?? null;
        if (empty($migrationName)) {
            echo Colorizer::yellow("Usage: php make.php migrate make <migration_name>") . PHP_EOL;
            return 1;
        }

        $timestamp = date('Y_m_d_His');
        $className = 'Migration_' . $timestamp . '_' . ucfirst(str_replace(['-', '_'], '', $migrationName));
        $fileName = $timestamp . '_' . strtolower($migrationName) . '.php';
        $migrationsPath = rtrim($this->config['migrations_path'], '/') . '/';

        // --- DEBUGGING OUTPUT ADDED HERE ---
        echo Colorizer::yellow("Attempting to create migration in path: " . $migrationsPath) . PHP_EOL;
        // --- END DEBUGGING OUTPUT ---

        if (!is_dir($migrationsPath)) {
            // --- DEBUGGING OUTPUT ADDED HERE ---
            echo Colorizer::yellow("Migrations directory '{$migrationsPath}' does not exist. Attempting to create...") . PHP_EOL;
            // --- END DEBUGGING OUTPUT ---

            // Added check for mkdir success
            if (!mkdir($migrationsPath, 0777, true)) {
                echo Colorizer::red("Failed to create migrations directory: {$migrationsPath}. Check permissions.") . PHP_EOL;
                return 1;
            }
            echo Colorizer::blue("Created migrations directory: {$migrationsPath}") . PHP_EOL;
        }

        $filePath = $migrationsPath . $fileName;

        // --- UPDATED TEMPLATE FOR NEW MIGRATIONS TO USE HELPER METHODS ACTIVELY ---
        $template = <<<EOT
<?php

use masoud4\Migration; 

class {$className} extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        // Example: Create a 'users' table
        \$this->createTable('users', [
            'id' => 'INT AUTO_INCREMENT PRIMARY KEY',
            'name' => 'VARCHAR(255) NOT NULL',
            'email' => 'VARCHAR(255) UNIQUE NOT NULL',
            'created_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
            'updated_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'
        ]);
        //
        // Example: Add a 'phone' column to 'users' table
        // \$this->addColumn('users', 'phone', 'VARCHAR(20) NULL AFTER email');
        //
        // Example: Modify 'name' column in 'users' table
        // \$this->modifyColumn('users', 'name', 'VARCHAR(100) NOT NULL');

        echo "Migration '{$className}' UP completed." . PHP_EOL;
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        // Example: Drop the 'users' table
        \$this->dropTable('users');
        //
        // Example: Drop the 'phone' column from 'users' table
        // \$this->dropColumn('users', 'phone');

        echo "Migration '{$className}' DOWN completed." . PHP_EOL;
    }
}
EOT;
        // --- END UPDATED TEMPLATE ---

        // Added check for file_put_contents success
        if (file_put_contents($filePath, $template) !== false) {
            echo Colorizer::green("Created Migration: " . Colorizer::bold($fileName)) . PHP_EOL;
            echo Colorizer::yellow("You can now define your schema changes in '{$filePath}'. Use \$this->createTable(), \$this->addColumn(), etc.") . PHP_EOL; // Updated reminder
            // Removed specific "uncomment" reminder as it's now active code for basic table creation/dropping

            // --- NEW DEBUGGING OUTPUT FOR FILE CONTENTS ---
            if (file_exists($filePath)) {
                $fileSize = filesize($filePath);
                $fileContentPreview = substr(file_get_contents($filePath), 0, 200) . '...'; // Show first 200 chars
                echo Colorizer::yellow("Verification: File '{$fileName}' created successfully. Size: {$fileSize} bytes. Content starts with: " . PHP_EOL . $fileContentPreview) . PHP_EOL;
            } else {
                echo Colorizer::red("Verification failed: File '{$fileName}' does not exist after creation attempt.") . PHP_EOL;
            }
            // --- END NEW DEBUGGING OUTPUT ---

            return 0;
        } else {
            // --- DEBUGGING OUTPUT ADDED HERE ---
            echo Colorizer::red("Failed to create migration file at: {$filePath}. Check permissions and disk space.") . PHP_EOL;
            // --- END DEBUGGING OUTPUT ---
            return 1;
        }
    }

    /**
     * Runs all pending migrations.
     * @return int
     */
    private function runMigrations(): int
    {
        $this->ensureMigrationsTableExists();
        $mysqli = $this->getMysqli();
        $migrationsPath = rtrim($this->config['migrations_path'], '/') . '/';

        if (!is_dir($migrationsPath)) {
            echo Colorizer::red("Migrations directory not found: {$migrationsPath}") . PHP_EOL;
            return 1;
        }

        $executedMigrations = $this->getExecutedMigrations();
        $migrationFiles = scandir($migrationsPath);
        sort($migrationFiles); // Ensure migrations run in order

        $newBatch = $this->getNextBatchNumber();
        $hasRunAny = false;

        foreach ($migrationFiles as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) === 'php' && $file !== '.' && $file !== '..') {
                $className = 'Migration_' . str_replace(['_', '.php'], '', substr($file, 0, 19)) . '_' . ucfirst(str_replace(['-', '_'], '', substr($file, 20, -4)));
                $matches = [];
                if (preg_match('/^(\d{4}_\d{2}_\d{2}_\d{6})_(.*)\.php$/', $file, $matches)) {
                    $baseName = str_replace(['-', '_'], '', $matches[2]);
                    $className = 'Migration_' . $matches[1] . '_' . ucfirst($baseName);
                } else {
                    $className = 'Migration_' . str_replace(['_', '.php'], '', $file);
                }

                require_once $migrationsPath . $file;

                if (!in_array($file, $executedMigrations)) {
                    if (class_exists($className) && (new \ReflectionClass($className))->implementsInterface(\masoud4\MigrationInterface::class)) {
                        $migration = new $className($mysqli); // Pass mysqli to migration constructor
                        echo Colorizer::blue("Running migration: " . Colorizer::bold($file)) . PHP_EOL;
                        try {
                            $migration->up();
                            $stmt = $mysqli->prepare("INSERT INTO migrations (migration, batch) VALUES (?, ?)");
                            $stmt->bind_param('si', $file, $newBatch);
                            $stmt->execute();
                            echo Colorizer::green("Migrated: {$file}") . PHP_EOL;
                            $hasRunAny = true;
                        } catch (mysqli_sql_exception $e) {
                            echo Colorizer::red("Failed to run migration {$file}: " . $e->getMessage()) . PHP_EOL;
                            return 1;
                        }
                    } else {
                        echo Colorizer::yellow("Warning: Skipping file '{$file}'. Class '{$className}' not found or does not implement MigrationInterface.") . PHP_EOL;
                    }
                }
            }
        }

        if (!$hasRunAny) {
            echo Colorizer::yellow("No new migrations to run.") . PHP_EOL;
        } else {
            echo Colorizer::green("Migrations complete for batch " . Colorizer::bold($newBatch) . ".") . PHP_EOL;
        }
        return 0;
    }

    /**
     * Rolls back migrations.
     * Can rollback the last batch, or a specific number of steps.
     * @param array $args Arguments, typically for --step=N
     * @return int
     */
    private function rollbackMigration(array $args): int
    {
        $this->ensureMigrationsTableExists();
        $mysqli = $this->getMysqli();
        $migrationsPath = rtrim($this->config['migrations_path'], '/') . '/';

        $rollbackSteps = 1; // Default to rolling back 1 batch (last batch)
        foreach ($args as $arg) {
            if (preg_match('/^--step=(\d+)$/', $arg, $matches)) {
                $rollbackSteps = (int)$matches[1];
                break;
            }
        }

        if ($rollbackSteps <= 0) {
            echo Colorizer::red("Invalid step count. Must be a positive integer.") . PHP_EOL;
            return 1;
        }

        $migrationsToRollback = [];
        if ($rollbackSteps > 0) {
            // Get the N most recent migrations, ordered newest first
            $stmt = $mysqli->prepare("SELECT migration, batch FROM migrations ORDER BY id DESC LIMIT ?");
            $stmt->bind_param('i', $rollbackSteps);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $migrationsToRollback[] = $row['migration'];
            }
            $stmt->close();

            if (empty($migrationsToRollback)) {
                echo Colorizer::yellow("No migrations found to rollback for the last " . Colorizer::bold($rollbackSteps) . " step(s).") . PHP_EOL;
                return 0;
            }

            echo Colorizer::blue("Attempting to rollback " . Colorizer::bold(count($migrationsToRollback)) . " migration(s).") . PHP_EOL;

        } else {
            // Default behavior: rollback last batch
            $lastBatch = $this->getCurrentBatchNumber();
            if ($lastBatch === 0) {
                echo Colorizer::yellow("No migrations to rollback.") . PHP_EOL;
                return 0;
            }

            $stmt = $mysqli->prepare("SELECT migration FROM migrations WHERE batch = ? ORDER BY id DESC");
            $stmt->bind_param('i', $lastBatch);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $migrationsToRollback[] = $row['migration'];
            }
            $stmt->close();

            if (empty($migrationsToRollback)) {
                echo Colorizer::yellow("No migrations found in batch " . Colorizer::bold($lastBatch) . " to rollback.") . PHP_EOL;
                return 0;
            }
            echo Colorizer::blue("Rolling back batch " . Colorizer::bold($lastBatch) . ".") . PHP_EOL;
        }


        foreach ($migrationsToRollback as $file) {
            $filePath = $migrationsPath . $file;
            $className = 'Migration_' . str_replace(['_', '.php'], '', substr($file, 0, 19)) . '_' . ucfirst(str_replace(['-', '_'], '', substr($file, 20, -4)));
            $matches = [];
            if (preg_match('/^(\d{4}_\d{2}_\d{2}_\d{6})_(.*)\.php$/', $file, $matches)) {
                $baseName = str_replace(['-', '_'], '', $matches[2]);
                $className = 'Migration_' . $matches[1] . '_' . ucfirst($baseName);
            } else {
                $className = 'Migration_' . str_replace(['_', '.php'], '', $file);
            }

            if (file_exists($filePath)) {
                require_once $filePath; // Ensure the class is loaded

                if (class_exists($className) && (new \ReflectionClass($className))->implementsInterface(\masoud4\MigrationInterface::class)) {
                    $migration = new $className($mysqli); // Pass mysqli to migration constructor
                    echo Colorizer::blue("Rolling back: " . Colorizer::bold($file)) . PHP_EOL;
                    try {
                        $migration->down();
                        $stmt = $mysqli->prepare("DELETE FROM migrations WHERE migration = ?");
                        $stmt->bind_param('s', $file);
                        $stmt->execute();
                        $stmt->close();
                        echo Colorizer::green("Rolled back: {$file}") . PHP_EOL;
                    } catch (mysqli_sql_exception $e) {
                        echo Colorizer::red("Failed to rollback migration {$file}: " . $e->getMessage()) . PHP_EOL;
                        return 1;
                    }
                } else {
                    echo Colorizer::red("Error: Class '{$className}' not found or does not implement MigrationInterface for file '{$file}'.") . PHP_EOL;
                    return 1;
                }
            } else {
                echo Colorizer::red("Error: Migration file not found: {$filePath}") . PHP_EOL;
                return 1;
            }
        }

        echo Colorizer::green("Rollback complete.") . PHP_EOL;
        return 0;
    }

    /**
     * Rolls back all migrations.
     * @return int
     */
    private function resetMigrations(): int
    {
        $this->ensureMigrationsTableExists();
        $mysqli = $this->getMysqli();
        $migrationsPath = rtrim($this->config['migrations_path'], '/') . '/';

        $stmt = $mysqli->query("SELECT migration FROM migrations ORDER BY id DESC");
        $migrationsToRollback = [];
        if ($stmt) {
             while ($row = $stmt->fetch_assoc()) {
                $migrationsToRollback[] = $row['migration'];
            }
            $stmt->free();
        } else {
            echo Colorizer::red("Failed to fetch executed migrations for reset: " . $mysqli->error) . PHP_EOL;
            return 1;
        }


        if (empty($migrationsToRollback)) {
            echo Colorizer::yellow("No migrations to reset.") . PHP_EOL;
            return 0;
        }

        echo Colorizer::blue("Attempting to reset all " . Colorizer::bold(count($migrationsToRollback)) . " migrations.") . PHP_EOL;

        foreach ($migrationsToRollback as $file) {
            $filePath = $migrationsPath . $file;
            $className = 'Migration_' . str_replace(['_', '.php'], '', substr($file, 0, 19)) . '_' . ucfirst(str_replace(['-', '_'], '', substr($file, 20, -4)));
            $matches = [];
            if (preg_match('/^(\d{4}_\d{2}_\d{2}_\d{6})_(.*)\.php$/', $file, $matches)) {
                $baseName = str_replace(['-', '_'], '', $matches[2]);
                $className = 'Migration_' . $matches[1] . '_' . ucfirst($baseName);
            } else {
                $className = 'Migration_' . str_replace(['_', '.php'], '', $file);
            }

            if (file_exists($filePath)) {
                require_once $filePath;

                if (class_exists($className) && (new \ReflectionClass($className))->implementsInterface(\masoud4\MigrationInterface::class)) {
                    $migration = new $className($mysqli);
                    echo Colorizer::blue("Rolling back: " . Colorizer::bold($file)) . PHP_EOL;
                    try {
                        $migration->down();
                        $stmt = $mysqli->prepare("DELETE FROM migrations WHERE migration = ?");
                        $stmt->bind_param('s', $file);
                        $stmt->execute();
                        $stmt->close();
                        echo Colorizer::green("Rolled back: {$file}") . PHP_EOL;
                    } catch (mysqli_sql_exception $e) {
                        echo Colorizer::red("Failed to rollback migration {$file}: " . $e->getMessage()) . PHP_EOL;
                        return 1;
                    }
                } else {
                    echo Colorizer::red("Error: Class '{$className}' not found or does not implement MigrationInterface for file '{$file}'.") . PHP_EOL;
                    return 1;
                }
            } else {
                echo Colorizer::red("Error: Migration file not found: {$filePath}") . PHP_EOL;
                return 1;
            }
        }

        echo Colorizer::green("All migrations have been reset.") . PHP_EOL;
        return 0;
    }


    /**
     * Gets a list of migrations already executed.
     * @return array
     */
    private function getExecutedMigrations(): array
    {
        $mysqli = $this->getMysqli();
        $result = $mysqli->query("SELECT migration FROM migrations");
        $migrations = [];
        while ($row = $result->fetch_assoc()) {
            $migrations[] = $row['migration'];
        }
        $result->free();
        return $migrations;
    }

    /**
     * Gets the next available batch number.
     * @return int
     */
    private function getNextBatchNumber(): int
    {
        $mysqli = $this->getMysqli();
        $result = $mysqli->query("SELECT MAX(batch) FROM migrations");
        $maxBatch = $result->fetch_column();
        $result->free();
        return (int) $maxBatch + 1;
    }

    /**
     * Gets the current (last) batch number.
     * @return int
     */
    private function getCurrentBatchNumber(): int
    {
        $mysqli = $this->getMysqli();
        $result = $mysqli->query("SELECT MAX(batch) FROM migrations");
        $currentBatch = $result->fetch_column();
        $result->free();
        return (int) $currentBatch;
    }
}
