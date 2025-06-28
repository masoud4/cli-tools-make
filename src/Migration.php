<?php
// src/Migration.php
namespace App;

use mysqli;

abstract class Migration implements MigrationInterface
{
    /**
     * @var mysqli The mysqli database connection instance.
     */
    protected mysqli $mysqli;

    public function __construct(mysqli $mysqli)
    {
        $this->mysqli = $mysqli;
    }

    /**
     * Create a new database table.
     *
     * @param string $tableName The name of the table to create.
     * @param array $columns An associative array of column definitions.
     * Example: ['id' => 'INT AUTO_INCREMENT PRIMARY KEY', 'name' => 'VARCHAR(255) NOT NULL']
     * You can also pass a callable to define columns more flexibly (like Laravel),
     * but for simplicity, we'll start with a direct array.
     * @return bool True on success, false on failure.
     */
    protected function createTable(string $tableName, array $columns): bool
    {
        $columnDefinitions = [];
        foreach ($columns as $name => $definition) {
            $columnDefinitions[] = "$name $definition";
        }
        $sql = "CREATE TABLE IF NOT EXISTS `{$tableName}` (" . implode(', ', $columnDefinitions) . ")";

        echo Colorizer::blue("Executing SQL to create table {$tableName}: " . $sql) . PHP_EOL;
        if ($this->mysqli->query($sql)) {
            return true;
        } else {
            echo Colorizer::red("Error creating table {$tableName}: " . $this->mysqli->error) . PHP_EOL;
            return false;
        }
    }

    /**
     * Drop a database table.
     *
     * @param string $tableName The name of the table to drop.
     * @return bool True on success, false on failure.
     */
    protected function dropTable(string $tableName): bool
    {
        $sql = "DROP TABLE IF EXISTS `{$tableName}`";
        echo Colorizer::blue("Executing SQL to drop table {$tableName}: " . $sql) . PHP_EOL;
        if ($this->mysqli->query($sql)) {
            return true;
        } else {
            echo Colorizer::red("Error dropping table {$tableName}: " . $this->mysqli->error) . PHP_EOL;
            return false;
        }
    }

    /**
     * Add a new column to a table.
     *
     * @param string $tableName The name of the table.
     * @param string $columnName The name of the column to add.
     * @param string $definition The column definition (e.g., 'VARCHAR(255) NOT NULL', 'INT DEFAULT 0').
     * @return bool True on success, false on failure.
     */
    protected function addColumn(string $tableName, string $columnName, string $definition): bool
    {
        $sql = "ALTER TABLE `{$tableName}` ADD COLUMN `{$columnName}` {$definition}";
        echo Colorizer::blue("Executing SQL to add column {$columnName} to {$tableName}: " . $sql) . PHP_EOL;
        if ($this->mysqli->query($sql)) {
            return true;
        } else {
            echo Colorizer::red("Error adding column {$columnName} to {$tableName}: " . $this->mysqli->error) . PHP_EOL;
            return false;
        }
    }

    /**
     * Drop a column from a table.
     *
     * @param string $tableName The name of the table.
     * @param string $columnName The name of the column to drop.
     * @return bool True on success, false on failure.
     */
    protected function dropColumn(string $tableName, string $columnName): bool
    {
        $sql = "ALTER TABLE `{$tableName}` DROP COLUMN `{$columnName}`";
        echo Colorizer::blue("Executing SQL to drop column {$columnName} from {$tableName}: " . $sql) . PHP_EOL;
        if ($this->mysqli->query($sql)) {
            return true;
        } else {
            echo Colorizer::red("Error dropping column {$columnName} from {$tableName}: " . $this->mysqli->error) . PHP_EOL;
            return false;
        }
    }

    /**
     * Modify an existing column in a table.
     * Note: MODIFY COLUMN syntax can vary slightly by MySQL version and data type.
     * This is a basic implementation.
     *
     * @param string $tableName The name of the table.
     * @param string $columnName The name of the column to modify.
     * @param string $newDefinition The new column definition (e.g., 'VARCHAR(100) NOT NULL').
     * @return bool True on success, false on failure.
     */
    protected function modifyColumn(string $tableName, string $columnName, string $newDefinition): bool
    {
        $sql = "ALTER TABLE `{$tableName}` MODIFY COLUMN `{$columnName}` {$newDefinition}";
        echo Colorizer::blue("Executing SQL to modify column {$columnName} in {$tableName}: " . $sql) . PHP_EOL;
        if ($this->mysqli->query($sql)) {
            return true;
        } else {
            echo Colorizer::red("Error modifying column {$columnName} in {$tableName}: " . $this->mysqli->error) . PHP_EOL;
            return false;
        }
    }

    // Abstract methods to be implemented by child migration classes
    abstract public function up(): void;
    abstract public function down(): void;
}
