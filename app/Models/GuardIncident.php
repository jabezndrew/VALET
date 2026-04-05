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
        'incident_at',
        'category',
        'notes',
        'involved_party',
        'action_taken',
        'status',
        'reported_by',
        'resolved_at',
        'resolved_by',
    ];

    protected $casts = [
        'incident_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function parkingSpace()
    {
        return $this->belongsTo(ParkingSpace::class, 'space_code', 'space_code');
    }

    public static function getCategoryLabel(string $category): string
    {
        return match($category) {
            'debris'       => 'Debris / Obstruction',
            'damaged'      => 'Damaged Spot',
            'blocked'      => 'Blocked Area',
            'light_issue'  => 'Light Issue',
            'sensor_issue' => 'Sensor Issue',
            'other'        => 'Other',
            default        => ucfirst($category),
        };
    }

    public static function getCategoryIcon(string $category): string
    {
        return match($category) {
            'debris'       => 'fa-trash',
            'damaged'      => 'fa-car-crash',
            'blocked'      => 'fa-ban',
            'light_issue'  => 'fa-lightbulb',
            'sensor_issue' => 'fa-wifi',
            default        => 'fa-exclamation-circle',
        };
    }

    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    public function scopeUnresolved($query)
    {
        return $query->whereIn('status', ['open', 'in_progress']);
    }
}
