<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ParkingFloor extends Model
{
    use HasFactory;

    protected $fillable = [
        'floor_number',
        'floor_name',
        'is_active',
        'display_order'
    ];

    protected $casts = [
        'floor_number' => 'integer',
        'is_active' => 'boolean',
        'display_order' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function columns(): HasMany
    {
        return $this->hasMany(ParkingColumn::class, 'floor_id');
    }

    public function activeColumns(): HasMany
    {
        return $this->columns()->where('is_active', true)->orderBy('display_order');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order')->orderBy('floor_number');
    }
}
