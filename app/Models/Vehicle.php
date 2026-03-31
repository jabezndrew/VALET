<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Vehicle extends Model
{
    use HasFactory;

    protected $table = 'vehicles';

    protected $fillable = [
        'plate_number',
        'vehicle_make',
        'vehicle_model',
        'vehicle_color',
        'vehicle_type',
        'rfid_tag',
        'owner_id',
        'expires_at',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'expires_at' => 'date',
        'owner_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $hidden = [];

    // Relationships
    public function owner(): BelongsTo
    {
        return $this->belongsTo(SysUser::class, 'owner_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(SysUser::class, 'owner_id');
    }

    public function rfidTag()
    {
        return $this->hasOne(RfidTag::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeExpired($query)
    {
        return $query->whereNotNull('expires_at')
            ->where('expires_at', '<', now());
    }

    public function scopeValid($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>=', now());
            });
    }

    // Status helpers
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function isValid(): bool
    {
        return $this->is_active && !$this->isExpired();
    }

    public function getExpiryStatus(): string
    {
        if (!$this->expires_at) return 'No Expiry';
        if ($this->isExpired()) return 'Expired';

        $daysUntilExpiry = now()->diffInDays($this->expires_at, false);
        if ($daysUntilExpiry <= 7) return 'Expiring Soon';

        return 'Valid';
    }

    public function getDaysUntilExpiry(): ?int
    {
        if (!$this->expires_at) return null;
        return now()->diffInDays($this->expires_at, false);
    }

    // Static finders
    public static function findByRfid(string $rfidTag): ?self
    {
        return static::where('rfid_tag', $rfidTag)->first();
    }

    public static function findByPlate(string $plateNumber): ?self
    {
        return static::where('plate_number', $plateNumber)->first();
    }
}
