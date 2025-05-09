<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vehicle extends Model
{
    use HasFactory;

    protected $fillable = [
        'registration_number',
        'status',
    ];

    public function permanentAssignment(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(PermanentAssignment::class);
    }

    public function permanentDriver()
    {
        return $this->hasOneThrough(
            Personnel::class,
            PermanentAssignment::class,
            'vehicle_id',
            'id',
            'id',
            'driver_id'
        );
    }

    public function dailyRosters(): HasMany
    {
        return $this->hasMany(DailyRoster::class);
    }
}
