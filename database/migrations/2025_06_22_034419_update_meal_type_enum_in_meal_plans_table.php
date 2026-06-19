<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // The ALTER ... MODIFY ENUM syntax is MySQL-specific. SQLite (used in CI
        // for the JMeter job) has no ENUM type and no MODIFY clause — it stores
        // the column as TEXT — so the raw statements are only run on MySQL.
        if (DB::getDriverName() === 'mysql') {
            // Step 1: Temporarily allow both 'others' and 'snack'
            DB::statement("ALTER TABLE meal_plans MODIFY meal_type ENUM('breakfast', 'lunch', 'dinner', 'others', 'snack') NOT NULL");
        }

        // Step 2: Convert existing 'others' to 'snack' (harmless no-op on a fresh DB)
        DB::table('meal_plans')->where('meal_type', 'others')->update(['meal_type' => 'snack']);

        if (DB::getDriverName() === 'mysql') {
            // Step 3: Remove 'others' from the enum
            DB::statement("ALTER TABLE meal_plans MODIFY meal_type ENUM('breakfast', 'lunch', 'dinner', 'snack') NOT NULL");
        }
    }
};
