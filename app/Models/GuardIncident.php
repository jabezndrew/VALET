<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GuardIncident extends Model
{
    use HasFactory;

    protected $fillable = [
        'space_code',
        'floor_level',
        'category',
        'notes',
        'status',
        'reported_by',
        'resolved_at',
        'resolved_by',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];

    public function parkingSpace()
    {
        return $this->belongsTo(ParkingSpace::class, 'space_code', 'space_code');
    }

    public static function getCategoryLabel($category)
    {
        return match($category) {
            'debris' => 'Debris/Obstruction',
            'damaged' => 'Damaged Spot',
            'blocked' => 'Blocked Area',
            'light_issue' => 'Light Issue',
            'sensor_issue' => 'Sensor Issue',
            'other' => 'Other',
            default => ucfirst($category),
        };
    }
}
