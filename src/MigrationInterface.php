<?php
namespace masoud4;

interface MigrationInterface
{
    /**
     * Run the migrations.
     * This method is executed when the migration is run.
     *
     * @return void
     */
    public function up(): void;

    /**
     * Reverse the migrations.
     * This method is executed when the migration is rolled back.
     *
     * @return void
     */
    public function down(): void;
}
