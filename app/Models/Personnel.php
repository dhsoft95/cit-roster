<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Personnel extends Model
{
    use HasFactory;
    protected $table = 'personnel';

    protected $fillable = [
        'name',
        'role',
        'status',
    ];

    public function permanentAssignment(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(PermanentAssignment::class, 'driver_id');
    }

    public function assignedVehicle()
    {
        return $this->hasOneThrough(
            Vehicle::class,
            PermanentAssignment::class,
            'driver_id',
            'id',
            'id',
            'vehicle_id'
        );
    }

    public function driverRosters(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(DailyRoster::class, 'driver_id');
    }

    public function commanderRosters(): HasMany
    {
        return $this->hasMany(DailyRoster::class, 'car_commander_id');
    }

    public function crewRosters(): HasMany
    {
        return $this->hasMany(DailyRoster::class, 'crew_id');
    }
}
