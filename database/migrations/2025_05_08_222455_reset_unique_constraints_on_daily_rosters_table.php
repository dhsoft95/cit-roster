// Save this as a new migration file
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // List all the constraint names we need to drop
        $constraintsToRemove = [
            'daily_rosters_date_vehicle_crew_number_unique',
            'daily_rosters_roster_date_car_commander_id_unique',
            'daily_rosters_roster_date_crew_id_unique',
            'daily_rosters_roster_date_driver_id_unique'
        ];

        // Drop each constraint if it exists
        foreach ($constraintsToRemove as $constraint) {
            try {
                DB::statement("ALTER TABLE daily_rosters DROP INDEX `$constraint`");
                echo "Dropped index $constraint\n";
            } catch (\Exception $e) {
                echo "Failed to drop index $constraint: " . $e->getMessage() . "\n";
                // Continue with the next constraint
            }
        }

        // Add back only the constraint we need
        try {
            Schema::table('daily_rosters', function (Blueprint $table) {
                $table->unique(['roster_date', 'vehicle_id', 'crew_number'], 'daily_rosters_date_vehicle_crew_unique');
            });
            echo "Added new constraint daily_rosters_date_vehicle_crew_unique\n";
        } catch (\Exception $e) {
            echo "Failed to add new constraint: " . $e->getMessage() . "\n";
        }
    }

    public function down(): void
    {
        // We don't restore these constraints
    }
};
