<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Vehicle Model
 *
 * @property int $id
 * @property string $plate_number
 * @property string $vehicle_make
 * @property string $vehicle_model
 * @property string $vehicle_color
 * @property string $vehicle_type
 * @property string $rfid_tag
 * @property int $owner_id
 * @property \Illuminate\Support\Carbon|null $expires_at
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read SysUser $owner
 */
class Vehicle extends Model
{
    use HasFactory;

    protected $table = 'vehicles';

    /**
     * The attributes that are mass assignable.
     */
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

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'is_active' => 'boolean',
        'expires_at' => 'datetime',
        'owner_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * The attributes that should be hidden for arrays.
     */
    protected $hidden = [];

    /**
     * Get the owner of the vehicle
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(SysUser::class, 'owner_id');
    }

    /**
     * Scope for active vehicles
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for expired vehicles
     */
    public function scopeExpired($query)
    {
        return $query->whereNotNull('expires_at')
            ->where('expires_at', '<', now());
    }

    /**
     * Scope for valid (active and not expired) vehicles
     */
    public function scopeValid($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>=', now());
            });
    }

    /**
     * Check if vehicle is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Check if vehicle is valid (active and not expired)
     */
    public function isValid(): bool
    {
        return $this->is_active && !$this->isExpired();
    }

    /**
     * Get the expiry status
     */
    public function getExpiryStatus(): string
    {
        if (!$this->expires_at) {
            return 'No Expiry';
        }

        if ($this->isExpired()) {
            return 'Expired';
        }

        $daysUntilExpiry = now()->diffInDays($this->expires_at, false);

        if ($daysUntilExpiry <= 7) {
            return 'Expiring Soon';
        }

        return 'Valid';
    }

    /**
     * Get days until expiry (negative if expired)
     */
    public function getDaysUntilExpiry(): ?int
    {
        if (!$this->expires_at) {
            return null;
        }

        return now()->diffInDays($this->expires_at, false);
    }

    /**
     * Find vehicle by RFID tag
     */
    public static function findByRfid(string $rfidTag): ?self
    {
        return static::where('rfid_tag', $rfidTag)->first();
    }

    /**
     * Find vehicle by plate number
     */
    public static function findByPlate(string $plateNumber): ?self
    {
        return static::where('plate_number', $plateNumber)->first();
    }
}
