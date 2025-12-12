<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ParkingConfig extends Model
{
    use HasFactory;

    protected $table = 'parking_config';

    protected $fillable = [
        'key',
        'value',
        'description'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public static function get(string $key, $default = null)
    {
        $config = static::where('key', $key)->first();
        return $config ? $config->value : $default;
    }

    public static function set(string $key, $value, ?string $description = null): self
    {
        return static::updateOrCreate(
            ['key' => $key],
            [
                'value' => $value,
                'description' => $description
            ]
        );
    }
}
