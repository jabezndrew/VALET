<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParkingColumn extends Model
{
    use HasFactory;

    protected $fillable = [
        'floor_id',
        'column_code',
        'column_name',
        'is_active',
        'display_order'
    ];

    protected $casts = [
        'floor_id' => 'integer',
        'is_active' => 'boolean',
        'display_order' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function floor(): BelongsTo
    {
        return $this->belongsTo(ParkingFloor::class, 'floor_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order')->orderBy('column_code');
    }

    public function scopeForFloor($query, int $floorId)
    {
        return $query->where('floor_id', $floorId);
    }
}
