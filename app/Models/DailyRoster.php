<?php

namespace App\Models;

use CWSPS154\UsersRolesPermissions\Models\HasRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DailyRoster extends Model
{
    use HasFactory;

    protected $fillable = [
        'roster_date',
        'vehicle_id',
        'driver_id',
        'car_commander_id',
        'crew_id',
        'crew_number',
    ];

    protected $casts = [
        'roster_date' => 'date',
        'crew_number' => 'integer',
    ];

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Personnel::class, 'driver_id');
    }

    public function carCommander(): BelongsTo
    {
        return $this->belongsTo(Personnel::class, 'car_commander_id');
    }

    public function crew(): BelongsTo
    {
        return $this->belongsTo(Personnel::class, 'crew_id');
    }

    /**
     * Define a self-referential relationship for assignments
     * This allows the repeater to work with the relationship method
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(DailyRoster::class, 'roster_date', 'roster_date');
    }

    /**
     * Get all crew members for a specific vehicle on a specific date
     */
    public static function getCrewMembers($date, $vehicleId)
    {
        return self::where('roster_date', $date)
            ->where('vehicle_id', $vehicleId)
            ->whereNotNull('crew_id')
            ->with('crew')
            ->orderBy('crew_number')
            ->get();
    }

    /**
     * Check if a crew member is already assigned to any vehicle on this date
     */
    public static function isCrewAssigned($date, $crewId)
    {
        return self::where('roster_date', $date)
            ->where('crew_id', $crewId)
            ->exists();
    }
}
