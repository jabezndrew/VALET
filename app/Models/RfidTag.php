<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RfidTag extends Model
{
    protected $fillable = [
        'uid',
        'user_id',
        'vehicle_id',
        'status',
        'expiry_date',
        'notes'
    ];

    protected $casts = [
        'expiry_date' => 'date'
    ];

    public function user()
    {
        return $this->belongsTo(SysUser::class, 'user_id');
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function parkingEntries()
    {
        return $this->hasMany(ParkingEntry::class);
    }
}
