<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ParkingEntry extends Model
{
    protected $fillable = [
        'entry_type',
        'rfid_tag_id',
        'guest_access_id',
        'user_id',
        'vehicle_plate',
        'entry_time',
        'exit_time',
        'duration_minutes',
        'status',
        'entry_gate_mac',
        'exit_gate_mac'
    ];

    protected $casts = [
        'entry_time' => 'datetime',
        'exit_time' => 'datetime'
    ];

    public function rfidTag()
    {
        return $this->belongsTo(RfidTag::class);
    }

    public function guestAccess()
    {
        return $this->belongsTo(GuestAccess::class);
    }

    public function user()
    {
        return $this->belongsTo(SysUser::class, 'user_id');
    }
}
