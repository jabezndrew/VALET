<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SensorAssignment extends Model
{
    protected $fillable = [
        'mac_address',
        'sensor_index',
        'space_code',
        'device_name',
        'status',
        'identify_mode',
        'identify_started_at',
        'last_seen',
        'firmware_version',
        'notes'
    ];

    protected $casts = [
        'last_seen' => 'datetime',
        'identify_started_at' => 'datetime',
        'identify_mode' => 'boolean',
    ];

    // Relationships
    public function parkingSpace()
    {
        return $this->belongsTo(ParkingSpace::class, 'space_code', 'space_code');
    }

    // Status checks
    public function isAssigned(): bool
    {
        return !is_null($this->space_code) && $this->status === 'active';
    }

    public function markActive()
    {
        $this->update(['status' => 'active', 'last_seen' => now()]);
    }

    public function updateLastSeen()
    {
        $this->update(['last_seen' => now()]);
    }

    // Display helpers
    public function getIdentifier(): string
    {
        return "{$this->mac_address}:{$this->sensor_index}";
    }

    public function getDisplayName(): string
    {
        return $this->device_name ?? "Sensor {$this->sensor_index} ({$this->mac_address})";
    }

    // Identify mode (LED blinks to locate sensor physically)
    public function startIdentify(): void
    {
        $this->update(['identify_mode' => true, 'identify_started_at' => now()]);
    }

    public function stopIdentify(): void
    {
        $this->update(['identify_mode' => false, 'identify_started_at' => null]);
    }
}
