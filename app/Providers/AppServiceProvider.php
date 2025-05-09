<?php

namespace App\Providers;

use App\Models\DailyRoster;
use App\Models\PermanentAssignment;
use App\Observers\DailyRosterObserver;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Set default string length for MySQL < 5.7.7
        Schema::defaultStringLength(191);

        // Register the observer
        DailyRoster::observe(DailyRosterObserver::class);

        // Add a model event on permanent assignment to sync with vehicle/personnel
        PermanentAssignment::created(function (PermanentAssignment $assignment) {
            // Check if there's an existing assignment for this vehicle
            $existingAssignment = PermanentAssignment::where('vehicle_id', $assignment->vehicle_id)
                ->where('id', '!=', $assignment->id)
                ->first();

            if ($existingAssignment) {
                $existingAssignment->delete();
            }

            // Check if there's an existing assignment for this driver
            $existingDriverAssignment = PermanentAssignment::where('driver_id', $assignment->driver_id)
                ->where('id', '!=', $assignment->id)
                ->first();

            if ($existingDriverAssignment) {
                $existingDriverAssignment->delete();
            }
        });
    }
}
