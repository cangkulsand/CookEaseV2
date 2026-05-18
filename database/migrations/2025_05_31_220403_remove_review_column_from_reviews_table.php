<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasColumn('reviews', 'review')) {
            Schema::table('reviews', function (Blueprint $table) {
                $table->dropColumn('review');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Add the 'review' column back if you want to rollback this migration
        if (! Schema::hasColumn('reviews', 'review')) {
            Schema::table('reviews', function (Blueprint $table) {
                $table->text('review')->nullable();
            });
        }
    }
};
