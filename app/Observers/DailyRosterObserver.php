<?php

namespace App\Observers;

use App\Models\DailyRoster;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class DailyRosterObserver
{
    /**
     * Handle the DailyRoster "creating" event.
     */
    public function creating(DailyRoster $dailyRoster): void
    {
        $this->validateAssignments($dailyRoster);
    }

    /**
     * Handle the DailyRoster "updating" event.
     */
    public function updating(DailyRoster $dailyRoster): void
    {
        $this->validateAssignments($dailyRoster);
    }

    /**
     * Validate that assignments are valid
     * - Allows multiple crew members for the same vehicle
     * - Allows different car commanders for each crew member
     * - Prevents same crew member from being assigned to multiple vehicles
     * - Prevents same car commander from being assigned to multiple vehicles
     */
    private function validateAssignments(DailyRoster $dailyRoster): void
    {
        // Skip validation if any of the required fields are missing
        if (!$dailyRoster->roster_date || !$dailyRoster->vehicle_id ||
            !$dailyRoster->driver_id || !$dailyRoster->car_commander_id) {
            return;
        }

        // Check if this driver is already assigned to a different vehicle on this date
        $existingDriver = DailyRoster::where('roster_date', $dailyRoster->roster_date)
            ->where('driver_id', $dailyRoster->driver_id)
            ->where('vehicle_id', '!=', $dailyRoster->vehicle_id) // Allow same driver for same vehicle
            ->where('id', '!=', $dailyRoster->id)
            ->exists();

        if ($existingDriver) {
            throw ValidationException::withMessages([
                'driver_id' => "This driver is already assigned to another vehicle for {$dailyRoster->roster_date->format('F d, Y')}",
            ]);
        }

        // Check if this car commander is already assigned to any vehicle on this date
        $existingCommander = DailyRoster::where('roster_date', $dailyRoster->roster_date)
            ->where('car_commander_id', $dailyRoster->car_commander_id)
            ->where('id', '!=', $dailyRoster->id)
            ->exists();

        if ($existingCommander) {
            throw ValidationException::withMessages([
                'car_commander_id' => "This car commander is already assigned for {$dailyRoster->roster_date->format('F d, Y')}",
            ]);
        }

        // Check if this crew member is already assigned to any vehicle on this date
        if ($dailyRoster->crew_id) {
            $existingCrew = DailyRoster::where('roster_date', $dailyRoster->roster_date)
                ->where('crew_id', $dailyRoster->crew_id)
                ->where('id', '!=', $dailyRoster->id)
                ->exists();

            if ($existingCrew) {
                throw ValidationException::withMessages([
                    'crew_id' => "This crew member is already assigned for {$dailyRoster->roster_date->format('F d, Y')}",
                ]);
            }
        }

        // Check if there's already an entry for this vehicle with the same crew_number
        if ($dailyRoster->crew_id && $dailyRoster->crew_number) {
            $existingCrewNumber = DailyRoster::where('roster_date', $dailyRoster->roster_date)
                ->where('vehicle_id', $dailyRoster->vehicle_id)
                ->where('crew_number', $dailyRoster->crew_number)
                ->where('id', '!=', $dailyRoster->id)
                ->exists();

            if ($existingCrewNumber) {
                throw ValidationException::withMessages([
                    'crew_number' => "Crew position #{$dailyRoster->crew_number} is already filled for this vehicle on {$dailyRoster->roster_date->format('F d, Y')}",
                ]);
            }
        }

        // Validate that driver is consistent for this vehicle on this date
        $existingVehicleEntry = DailyRoster::where('roster_date', $dailyRoster->roster_date)
            ->where('vehicle_id', $dailyRoster->vehicle_id)
            ->where('id', '!=', $dailyRoster->id)
            ->first();

        if ($existingVehicleEntry) {
            // Ensure the driver matches
            if ($existingVehicleEntry->driver_id !== $dailyRoster->driver_id) {
                throw ValidationException::withMessages([
                    'driver_id' => "This vehicle already has a different driver assigned for {$dailyRoster->roster_date->format('F d, Y')}",
                ]);
            }

            // We no longer check for car commander consistency since we want different commanders
        }
    }
}
