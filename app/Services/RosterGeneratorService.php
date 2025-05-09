<?php

namespace App\Services;

use App\Models\DailyRoster;
use App\Models\PermanentAssignment;
use App\Models\Personnel;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RosterGeneratorService
{
    /**
     * Generate a random roster for a specific date
     *
     * @param string|Carbon $date
     * @param int $crewCount Number of crew members per vehicle
     * @param int|null $maxVehicles Maximum number of vehicles to include
     * @return array
     */
    public function generateRoster($date, int $crewCount = 1, ?int $maxVehicles = null): array
    {
        if (!$date instanceof Carbon) {
            $date = Carbon::parse($date);
        }

        // Get all active vehicles with their permanent drivers
        $vehicles = Vehicle::where('status', 'active')
            ->with('permanentAssignment.driver')
            ->get();

        // Limit vehicles if specified
        if ($maxVehicles !== null && $maxVehicles > 0) {
            $vehicles = $vehicles->take($maxVehicles);
        }

        // Get available car commanders
        $carCommanders = Personnel::where('role', 'car_commander')
            ->where('status', 'active')
            ->get()
            ->shuffle(); // Randomize

        // Get available crew members
        $crewMembers = Personnel::where('role', 'crew')
            ->where('status', 'active')
            ->get()
            ->shuffle(); // Randomize

        $roster = [];
        $assignedCommanderIds = [];
        $assignedCrewIds = [];

        foreach ($vehicles as $vehicle) {
            // Skip vehicles without a permanent driver
            if (!$vehicle->permanentAssignment) {
                continue;
            }

            $driverId = $vehicle->permanentAssignment->driver_id;

            // Skip if the driver is not active
            $driver = Personnel::find($driverId);
            if (!$driver || $driver->status !== 'active') {
                continue;
            }

            // For this vehicle, we need multiple crew members based on crewCount
            $vehicleCrewMembers = [];
            $hasEnoughCrew = true;

            // Try to find the requested number of crew members
            for ($i = 0; $i < $crewCount; $i++) {
                $crewMember = $this->getRandomUnassignedPersonnel($crewMembers, $assignedCrewIds);

                if (!$crewMember) {
                    // Not enough crew members available
                    $hasEnoughCrew = false;
                    break;
                }

                // Add to the vehicle's crew list
                $vehicleCrewMembers[] = $crewMember;

                // Mark as assigned
                $assignedCrewIds[] = $crewMember->id;
            }

            // Skip this vehicle if we couldn't find enough crew members
            if (!$hasEnoughCrew) {
                // Return any assigned crew members back to the available pool
                foreach ($vehicleCrewMembers as $crewMember) {
                    $key = array_search($crewMember->id, $assignedCrewIds);
                    if ($key !== false) {
                        unset($assignedCrewIds[$key]);
                    }
                }
                continue;
            }

            // Create roster entries for this vehicle - one entry per crew member,
            // now with different car commanders for each entry
            foreach ($vehicleCrewMembers as $index => $crewMember) {
                // Find an available car commander for each crew member
                $commander = $this->getRandomUnassignedPersonnel($carCommanders, $assignedCommanderIds);

                // If we don't have enough commanders, skip this crew member
                if (!$commander) {
                    continue;
                }

                // Add commander to assigned list
                $assignedCommanderIds[] = $commander->id;

                // Create the roster entry
                $roster[] = [
                    'roster_date' => $date->format('Y-m-d'),
                    'vehicle_id' => $vehicle->id,
                    'driver_id' => $driverId,
                    'car_commander_id' => $commander->id,
                    'crew_id' => $crewMember->id,
                    'crew_number' => $index + 1, // Crew number starts from 1
                ];
            }
        }

        return $roster;
    }

    /**
     * Get a random unassigned personnel from a collection
     *
     * @param Collection $personnel
     * @param array $assignedIds
     * @return Personnel|null
     */
    private function getRandomUnassignedPersonnel(Collection $personnel, array $assignedIds)
    {
        // Filter out already assigned personnel
        $available = $personnel->filter(function ($person) use ($assignedIds) {
            return !in_array($person->id, $assignedIds);
        });

        // Return random one or null if none available
        return $available->isNotEmpty() ? $available->first() : null;
    }

    /**
     * Save the generated roster to the database
     *
     * @param array $roster
     * @return array Array with success status and message/count
     */
    public function saveRoster(array $roster): array
    {
        $successCount = 0;
        $errors = [];

        DB::beginTransaction();

        try {
            foreach ($roster as $entry) {
                try {
                    DailyRoster::create($entry);
                    $successCount++;
                } catch (\Exception $e) {
                    $vehicleId = $entry['vehicle_id'] ?? 'unknown';
                    $errors[] = "Failed to create roster entry for vehicle ID {$vehicleId}: " . $e->getMessage();
                    Log::error("Roster creation error: " . $e->getMessage(), $entry);
                }
            }

            if ($successCount === 0 && count($errors) > 0) {
                // If no entries were successful, rollback the transaction
                DB::rollBack();
                return [
                    'success' => false,
                    'message' => 'Failed to create any roster entries',
                    'errors' => $errors
                ];
            }

            DB::commit();
            return [
                'success' => true,
                'count' => $successCount,
                'errors' => $errors
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'message' => 'A database error occurred: ' . $e->getMessage(),
                'errors' => [$e->getMessage()]
            ];
        }
    }
}
