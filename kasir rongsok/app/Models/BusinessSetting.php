<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'group',
    ];

    protected static ?array $cachedSettings = null;

    public static function allKeyed(): array
    {
        if (static::$cachedSettings === null) {
            static::$cachedSettings = static::pluck('value', 'key')->all();
        }
        return static::$cachedSettings;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $all = static::allKeyed();
        return array_key_exists($key, $all) ? $all[$key] : $default;
    }

    public static function set(string $key, mixed $value, string $group = 'general'): static
    {
        static::$cachedSettings = null;
        return static::updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'group' => $group]
        );
    }

    /**
     * Determine if a custom app logo is configured and exists on disk.
     */
    public static function hasLogo(): bool
    {
        $logo = static::get('app_logo');
        if (empty($logo)) {
            return false;
        }

        return file_exists(public_path($logo))
            || file_exists(base_path('../' . $logo))
            || file_exists(base_path('public/' . $logo));
    }

    /**
     * Get the accessible public URL for the logo.
     */
    public static function logoUrl(): ?string
    {
        $logo = static::get('app_logo');
        if (empty($logo)) {
            return null;
        }

        return static::hasLogo() ? asset($logo) : null;
    }
}
