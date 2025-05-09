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
        Schema::table('daily_rosters', function (Blueprint $table) {
            // Drop the unique constraint
            $table->dropUnique('daily_rosters_roster_date_vehicle_id_unique');

            // Add a unique constraint that includes crew_number
            $table->unique(['roster_date', 'vehicle_id', 'crew_number'], 'daily_rosters_date_vehicle_crew_number_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('daily_rosters', function (Blueprint $table) {
            // Drop the new unique constraint
            $table->dropUnique('daily_rosters_date_vehicle_crew_number_unique');

            // Restore the original unique constraint
            $table->unique(['roster_date', 'vehicle_id']);
        });
    }
};
