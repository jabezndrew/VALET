<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RfidScanLog extends Model
{
    protected $fillable = [
        'uid',
        'status',
        'message',
        'scan_type',
        'gate_mac',
        'user_name',
        'vehicle_plate',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];
}
