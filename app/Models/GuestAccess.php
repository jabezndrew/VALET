<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GuestAccess extends Model
{
    protected $table = 'guest_access';

    protected $fillable = [
        'guest_id',
        'name',
        'vehicle_plate',
        'phone',
        'purpose',
        'valid_from',
        'valid_until',
        'status',
        'created_by',
        'notes'
    ];

    protected $casts = [
        'valid_from' => 'datetime',
        'valid_until' => 'datetime'
    ];

    public function creator()
    {
        return $this->belongsTo(SysUser::class, 'created_by');
    }

    public function parkingEntries()
    {
        return $this->hasMany(ParkingEntry::class);
    }
}
