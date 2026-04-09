<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ParkingSpace extends Model
{
    use HasFactory;

    protected $table = 'parking_spaces';

    protected $fillable = [
        'sensor_id',
        'space_code',
        'floor_number',
        'column_code',
        'slot_number',
        'is_occupied',
        'distance_cm',
        'floor_level',
        'slot_name',
        'x_position',
        'y_position',
        'rotation',
        'is_active',
        'manual_override',
        'manual_status',
        'manual_override_at',
        'manual_override_expires',
        'manual_override_by',
        'override_reason',
        'malfunctioned',
        'malfunction_reason',
        'malfunction_reported_by',
        'malfunctioned_at',
    ];

    protected $casts = [
        'is_occupied' => 'boolean',
        'distance_cm' => 'integer',
        'sensor_id' => 'integer',
        'floor_number' => 'integer',
        'slot_number' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'manual_override' => 'boolean',
        'manual_override_at' => 'datetime',
        'manual_override_expires' => 'datetime',
        'malfunctioned' => 'boolean',
        'malfunctioned_at' => 'datetime',
    ];

    protected $hidden = [];

    // Auto-generate space_code before saving
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($space) {
            if ($space->floor_number && $space->column_code && $space->slot_number) {
                $space->space_code = self::buildSpaceCode(
                    $space->floor_number,
                    $space->column_code,
                    $space->slot_number
                );
            }
        });
    }

    // Static helpers
    public static function forFloor(string $floorLevel)
    {
        return static::where('floor_level', $floorLevel)->orderBy('sensor_id');
    }

    public static function forFloorNumber(int $floorNumber)
    {
        return static::where('floor_number', $floorNumber)
            ->orderBy('column_code')
            ->orderBy('slot_number');
    }

    public static function buildSpaceCode(int $floor, string $column, int $slot): string
    {
        return "{$floor}{$column}{$slot}";
    }

    // Parses e.g. "2A1" into [floor_number, column_code, slot_number]
    public static function parseSpaceCode(string $spaceCode): ?array
    {
        if (preg_match('/^(\d+)([A-Z]+)(\d+)$/', $spaceCode, $matches)) {
            return [
                'floor_number' => (int) $matches[1],
                'column_code' => $matches[2],
                'slot_number' => (int) $matches[3],
            ];
        }
        return null;
    }

    // Scopes
    public function scopeOccupied($query)
    {
        return $query->where('is_occupied', true);
    }

    public function scopeAvailable($query)
    {
        return $query->where('is_occupied', false);
    }

    // Status checks
    public function isAvailable(): bool
    {
        return !$this->is_occupied;
    }

    public function markAsOccupied(): bool
    {
        return $this->update(['is_occupied' => true]);
    }

    public function markAsAvailable(): bool
    {
        return $this->update(['is_occupied' => false]);
    }

    public function isManualOverrideActive(): bool
    {
        return (bool) $this->manual_override;
    }

    // Returns effective status factoring in malfunction and manual override
    public function getEffectiveStatus(): string
    {
        if ($this->malfunctioned) return 'malfunctioned';
        if ($this->isManualOverrideActive()) return $this->manual_status;
        return $this->is_occupied ? 'occupied' : 'available';
    }

    // Relationships
    public function sensorAssignment()
    {
        return $this->hasOne(SensorAssignment::class, 'space_code', 'space_code');
    }

    public function incidents()
    {
        return $this->hasMany(GuardIncident::class, 'space_code', 'space_code');
    }

    // Manual override
    public function setManualOverride(string $status, string $overrideBy = 'Guard', ?string $reason = null): bool
    {
        return $this->update([
            'manual_override' => true,
            'manual_status' => $status,
            'is_occupied' => $status === 'occupied',
            'manual_override_at' => now(),
            'manual_override_expires' => null,
            'manual_override_by' => $overrideBy,
            'override_reason' => $reason,
        ]);
    }

    public function clearManualOverride(): bool
    {
        return $this->update([
            'manual_override' => false,
            'manual_status' => null,
            'is_occupied' => false,
            'manual_override_at' => null,
            'manual_override_expires' => null,
            'manual_override_by' => null,
            'override_reason' => null,
        ]);
    }

    // Malfunction reporting
    public function reportMalfunction(string $reportedBy, ?string $reason = null): bool
    {
        return $this->update([
            'malfunctioned' => true,
            'malfunction_reason' => $reason,
            'malfunction_reported_by' => $reportedBy,
            'malfunctioned_at' => now(),
        ]);
    }

    public function clearMalfunction(): bool
    {
        return $this->update([
            'malfunctioned' => false,
            'malfunction_reason' => null,
            'malfunction_reported_by' => null,
            'malfunctioned_at' => null,
        ]);
    }
}
