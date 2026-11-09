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
     * Get Base64 data URI of the app logo for reliable DomPDF rendering.
     */
    public static function logoBase64(): ?string
    {
        $logo = static::get('app_logo');
        if (empty($logo)) {
            return null;
        }

        $candidates = [
            public_path($logo),
            base_path('../' . $logo),
            base_path('public/' . $logo),
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

    /**
     * Determine if a company stamp image is configured and exists on disk.
     */
    public static function hasCompanyStamp(): bool
    {
        $stamp = static::get('company_stamp');
        if (empty($stamp)) {
            return false;
        }

        return file_exists(public_path($stamp))
            || file_exists(base_path('../' . $stamp))
            || file_exists(base_path('public/' . $stamp));
    }

    /**
     * Get the accessible public URL for the company stamp.
     */
    public static function companyStampUrl(): ?string
    {
        $stamp = static::get('company_stamp');
        if (empty($stamp) || !static::hasCompanyStamp()) {
            return null;
        }

        return asset($stamp);
    }

    /**
     * Get Base64 data URI of the company stamp for reliable DomPDF rendering.
     */
    public static function companyStampBase64(): ?string
    {
        $stamp = static::get('company_stamp');
        if (empty($stamp)) {
            return null;
        }

        $candidates = [
            public_path($stamp),
            base_path('../' . $stamp),
            base_path('public/' . $stamp),
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

    /**
     * Determine if either owner signature or company stamp exists.
     */
    public static function hasSignatureOrStamp(): bool
    {
        return static::hasOwnerSignature() || static::hasCompanyStamp();
    }

    /**
     * Render the combined signature and stamp HTML markup.
     */
    public static function renderSignatureAndStamp(bool $isPdf = false, int $height = 48, ?int $stampSize = null, string $stampLeft = '0px', string $style = ''): string
    {
        return view('components.signature-stamp', [
            'isPdf' => $isPdf,
            'height' => $height,
            'stampSize' => $stampSize,
            'stampLeft' => $stampLeft,
            'style' => $style,
        ])->render();
    }

    /**
     * Get the configured commodity/goods term (e.g. "Barang Rongsok", "Material Scrap", "Barang Bekas").
     */
    public static function commodityTerm(string $default = 'Barang Rongsok'): string
    {
        $term = static::get('commodity_term');
        return !empty(trim((string) $term)) ? trim((string) $term) : $default;
    }
}
