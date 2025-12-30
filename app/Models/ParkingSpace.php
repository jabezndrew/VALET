<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * ParkingSpace Model
 *
 * @property int $id
 * @property int $sensor_id
 * @property string|null $space_code
 * @property int|null $floor_number
 * @property string|null $column_code
 * @property int|null $slot_number
 * @property bool $is_occupied
 * @property int|null $distance_cm
 * @property string $floor_level
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class ParkingSpace extends Model
{
    use HasFactory;

    protected $table = 'parking_spaces';

    /**
     * The attributes that are mass assignable.
     */
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
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'is_occupied' => 'boolean',
        'distance_cm' => 'integer',
        'sensor_id' => 'integer',
        'floor_number' => 'integer',
        'slot_number' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * The attributes that should be hidden for arrays.
     */
    protected $hidden = [];

    /**
     * Get all parking spaces for a specific floor (legacy)
     */
    public static function forFloor(string $floorLevel)
    {
        return static::where('floor_level', $floorLevel)->orderBy('sensor_id');
    }

    /**
     * Get all parking spaces for a specific floor number
     */
    public static function forFloorNumber(int $floorNumber)
    {
        return static::where('floor_number', $floorNumber)
            ->orderBy('column_code')
            ->orderBy('slot_number');
    }

    /**
     * Build space code from floor, column, and slot
     */
    public static function buildSpaceCode(int $floor, string $column, int $slot): string
    {
        return "{$floor}{$column}{$slot}";
    }

    /**
     * Parse space code into components
     */
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

    /**
     * Generate space code before saving
     */
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

    /**
     * Scope for occupied spaces
     */
    public function scopeOccupied($query)
    {
        return $query->where('is_occupied', true);
    }

    /**
     * Scope for available spaces
     */
    public function scopeAvailable($query)
    {
        return $query->where('is_occupied', false);
    }

    /**
     * Check if the space is available
     */
    public function isAvailable(): bool
    {
        return !$this->is_occupied;
    }

    /**
     * Mark space as occupied
     */
    public function markAsOccupied(): bool
    {
        return $this->update(['is_occupied' => true]);
    }

    /**
     * Mark space as available
     */
    public function markAsAvailable(): bool
    {
        return $this->update(['is_occupied' => false]);
    }

    /**
     * Get the sensor assignment for this parking space
     */
    public function sensorAssignment()
    {
        return $this->hasOne(SensorAssignment::class, 'space_code', 'space_code');
    }
}
