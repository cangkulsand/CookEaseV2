<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Step 1: Temporarily allow both 'others' and 'snack'
        DB::statement("ALTER TABLE meal_plans MODIFY meal_type ENUM('breakfast', 'lunch', 'dinner', 'others', 'snack') NOT NULL");

        // Step 2: Convert existing 'others' to 'snack'
        DB::table('meal_plans')->where('meal_type', 'others')->update(['meal_type' => 'snack']);

        // Step 3: Remove 'others' from the enum
        DB::statement("ALTER TABLE meal_plans MODIFY meal_type ENUM('breakfast', 'lunch', 'dinner', 'snack') NOT NULL");
    }
};
