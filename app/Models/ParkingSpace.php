<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * ParkingSpace Model
 *
 * @property int $id
 * @property int $sensor_id
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
        'is_occupied',
        'distance_cm',
        'floor_level',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'is_occupied' => 'boolean',
        'distance_cm' => 'integer',
        'sensor_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * The attributes that should be hidden for arrays.
     */
    protected $hidden = [];

    /**
     * Get all parking spaces for a specific floor
     */
    public static function forFloor(string $floorLevel)
    {
        return static::where('floor_level', $floorLevel)->orderBy('sensor_id');
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
}
