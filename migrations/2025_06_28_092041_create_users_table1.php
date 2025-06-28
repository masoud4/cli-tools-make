<?php

use App\Migration; // Make sure this namespace is correct for your base Migration class
// No need for 'use mysqli;' here, as you access it via $this->mysqli from the base Migration class.

class Migration_2025_06_28_092041_Createuserstable1 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        // Example: Create a 'users' table
        $sql = "
            CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                email VARCHAR(255) UNIQUE NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )
        ";
        if ($this->mysqli->query($sql)) {
            echo "Migration 'Migration_2025_06_28_092041_Createuserstable1' UP: Created users table." . PHP_EOL;
        } else {
            echo "Migration 'Migration_2025_06_28_092041_Createuserstable1' UP failed: " . $this->mysqli->error . PHP_EOL;
            // Optionally throw an exception here to halt migration if critical
            // throw new \mysqli_sql_exception($this->mysqli->error, $this->mysqli->errno);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        // Example: Drop the 'users' table
        $sql = "DROP TABLE IF EXISTS users";
        if ($this->mysqli->query($sql)) {
            echo "Migration 'Migration_2025_06_28_092041_Createuserstable1' DOWN: Dropped users table." . PHP_EOL;
        } else {
            echo "Migration 'Migration_2025_06_28_092041_Createuserstable1' DOWN failed: " . $this->mysqli->error . PHP_EOL;
        }
    }
}