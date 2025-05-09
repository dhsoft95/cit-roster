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
        Schema::create('daily_rosters', function (Blueprint $table) {
            $table->id();
            $table->date('roster_date');
            $table->foreignId('vehicle_id')->constrained()->onDelete('cascade');
            $table->foreignId('driver_id')->constrained('personnel')->onDelete('cascade');
            $table->foreignId('car_commander_id')->constrained('personnel')->onDelete('cascade');
            $table->foreignId('crew_id')->constrained('personnel')->onDelete('cascade');
            $table->timestamps();

            // Ensure each vehicle has only one roster entry per day
            $table->unique(['roster_date', 'vehicle_id']);

            // Ensure personnel are not assigned to multiple vehicles on the same day
            $table->unique(['roster_date', 'driver_id']);
            $table->unique(['roster_date', 'car_commander_id']);
            $table->unique(['roster_date', 'crew_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_rosters');
    }
};
