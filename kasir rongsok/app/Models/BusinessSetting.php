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

    /**
     * Determine if an owner signature image is configured and exists on disk.
     */
    public static function hasOwnerSignature(): bool
    {
        $sig = static::get('owner_signature');
        if (empty($sig)) {
            return false;
        }

        return file_exists(public_path($sig))
            || file_exists(base_path('../' . $sig))
            || file_exists(base_path('public/' . $sig));
    }

    /**
     * Get the accessible public URL for the owner signature.
     */
    public static function ownerSignatureUrl(): ?string
    {
        $sig = static::get('owner_signature');
        if (empty($sig) || !static::hasOwnerSignature()) {
            return null;
        }

        return asset($sig);
    }

    /**
     * Get Base64 data URI of the owner signature for reliable DomPDF rendering.
     */
    public static function ownerSignatureBase64(): ?string
    {
        $sig = static::get('owner_signature');
        if (empty($sig)) {
            return null;
        }

        $candidates = [
            public_path($sig),
            base_path('../' . $sig),
            base_path('public/' . $sig),
        ];

        foreach ($candidates as $path) {
            if (file_exists($path) && is_file($path)) {
                $mime = @mime_content_type($path) ?: 'image/png';
                $content = @file_get_contents($path);
                if ($content !== false) {
                    return 'data:' . $mime . ';base64,' . base64_encode($content);
                }
            }
        }

        return null;
    }
}
