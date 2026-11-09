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
     * Auto-detect any uploaded logo on disk and self-heal the setting.
     */
    public static function detectUploadedLogo(): ?string
    {
        $dirs = [
            public_path('uploads/logo'),
            base_path('public/uploads/logo'),
            base_path('../uploads/logo'),
        ];

        $candidates = [];
        foreach ($dirs as $dir) {
            if (is_dir($dir)) {
                $files = @scandir($dir);
                if (is_array($files)) {
                    foreach ($files as $f) {
                        if ($f === '.' || $f === '..' || str_starts_with($f, 'pwa_icon_') || $f === '.gitignore') {
                            continue;
                        }
                        $full = $dir . DIRECTORY_SEPARATOR . $f;
                        if (is_file($full) && filesize($full) > 500) {
                            $ext = strtolower(pathinfo($full, PATHINFO_EXTENSION));
                            if (in_array($ext, ['png', 'jpg', 'jpeg', 'webp', 'ico'])) {
                                $candidates[$full] = filemtime($full);
                            }
                        }
                    }
                }
            }
        }

        if (!empty($candidates)) {
            arsort($candidates);
            $best = array_key_first($candidates);
            $relative = 'uploads/logo/' . basename($best);
            try {
                if (\Illuminate\Support\Facades\Schema::hasTable('business_settings')) {
                    static::set('app_logo', $relative, 'branding');
                }
            } catch (\Throwable $e) {}
            return $relative;
        }

        return null;
    }

    /**
     * Auto-detect any uploaded owner signature on disk and self-heal the setting.
     */
    public static function detectUploadedSignature(): ?string
    {
        $dirs = [
            public_path('uploads/signature'),
            base_path('public/uploads/signature'),
            base_path('../uploads/signature'),
        ];

        $candidates = [];
        foreach ($dirs as $dir) {
            if (is_dir($dir)) {
                $files = @scandir($dir);
                if (is_array($files)) {
                    foreach ($files as $f) {
                        if ($f === '.' || $f === '..' || $f === '.gitignore') {
                            continue;
                        }
                        $full = $dir . DIRECTORY_SEPARATOR . $f;
                        if (is_file($full) && filesize($full) > 100) {
                            $candidates[$full] = filemtime($full);
                        }
                    }
                }
            }
        }

        if (!empty($candidates)) {
            // Prioritize owner_signature_active.png if exists, otherwise newest
            $best = null;
            foreach (array_keys($candidates) as $path) {
                if (basename($path) === 'owner_signature_active.png') {
                    $best = $path;
                    break;
                }
            }
            if (!$best) {
                arsort($candidates);
                $best = array_key_first($candidates);
            }
            $relative = 'uploads/signature/' . basename($best);
            try {
                if (\Illuminate\Support\Facades\Schema::hasTable('business_settings')) {
                    static::set('owner_signature', $relative, 'branding');
                }
            } catch (\Throwable $e) {}
            return $relative;
        }

        return null;
    }

    /**
     * Auto-detect any uploaded company stamp on disk and self-heal the setting.
     */
    public static function detectUploadedStamp(): ?string
    {
        $dirs = [
            public_path('uploads/stamp'),
            base_path('public/uploads/stamp'),
            base_path('../uploads/stamp'),
        ];

        $candidates = [];
        foreach ($dirs as $dir) {
            if (is_dir($dir)) {
                $files = @scandir($dir);
                if (is_array($files)) {
                    foreach ($files as $f) {
                        if ($f === '.' || $f === '..' || $f === '.gitignore') {
                            continue;
                        }
                        $full = $dir . DIRECTORY_SEPARATOR . $f;
                        if (is_file($full) && filesize($full) > 100) {
                            $candidates[$full] = filemtime($full);
                        }
                    }
                }
            }
        }

        if (!empty($candidates)) {
            // Prioritize company_stamp_active.png if exists, otherwise newest
            $best = null;
            foreach (array_keys($candidates) as $path) {
                if (basename($path) === 'company_stamp_active.png') {
                    $best = $path;
                    break;
                }
            }
            if (!$best) {
                arsort($candidates);
                $best = array_key_first($candidates);
            }
            $relative = 'uploads/stamp/' . basename($best);
            try {
                if (\Illuminate\Support\Facades\Schema::hasTable('business_settings')) {
                    static::set('company_stamp', $relative, 'branding');
                }
            } catch (\Throwable $e) {}
            return $relative;
        }

        return null;
    }

    /**
     * Sync all uploaded media (logo, signature, stamp) from disk into BusinessSettings.
     */
    public static function syncUploadedMediaFromDisk(): void
    {
        static::detectUploadedLogo();
        static::detectUploadedSignature();
        static::detectUploadedStamp();
    }

    /**
     * Determine if a custom app logo is configured and exists on disk.
     */
    public static function hasLogo(): bool
    {
        $logo = static::get('app_logo');
        if (empty($logo)) {
            $logo = static::detectUploadedLogo();
            if (empty($logo)) {
                return false;
            }
        }

        $exists = file_exists(public_path($logo))
            || file_exists(base_path('../' . $logo))
            || file_exists(base_path('public/' . $logo));

        if (!$exists) {
            $detected = static::detectUploadedLogo();
            return !empty($detected);
        }

        return true;
    }

    /**
     * Get the accessible public URL for the logo.
     */
    public static function logoUrl(): ?string
    {
        if (!static::hasLogo()) {
            return null;
        }

        $logo = static::get('app_logo');
        return $logo ? asset($logo) : null;
    }

    /**
     * Get Base64 data URI of the app logo for reliable DomPDF rendering.
     */
    public static function logoBase64(): ?string
    {
        if (!static::hasLogo()) {
            return null;
        }

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
            $sig = static::detectUploadedSignature();
            if (empty($sig)) {
                return false;
            }
        }

        $exists = file_exists(public_path($sig))
            || file_exists(base_path('../' . $sig))
            || file_exists(base_path('public/' . $sig));

        if (!$exists) {
            $detected = static::detectUploadedSignature();
            return !empty($detected);
        }

        return true;
    }

    /**
     * Get the accessible public URL for the owner signature.
     */
    public static function ownerSignatureUrl(): ?string
    {
        if (!static::hasOwnerSignature()) {
            return null;
        }

        $sig = static::get('owner_signature');
        return $sig ? asset($sig) : null;
    }

    /**
     * Get Base64 data URI of the owner signature for reliable DomPDF rendering.
     */
    public static function ownerSignatureBase64(): ?string
    {
        if (!static::hasOwnerSignature()) {
            return null;
        }

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
            $stamp = static::detectUploadedStamp();
            if (empty($stamp)) {
                return false;
            }
        }

        $exists = file_exists(public_path($stamp))
            || file_exists(base_path('../' . $stamp))
            || file_exists(base_path('public/' . $stamp));

        if (!$exists) {
            $detected = static::detectUploadedStamp();
            return !empty($detected);
        }

        return true;
    }

    /**
     * Get the accessible public URL for the company stamp.
     */
    public static function companyStampUrl(): ?string
    {
        if (!static::hasCompanyStamp()) {
            return null;
        }

        $stamp = static::get('company_stamp');
        return $stamp ? asset($stamp) : null;
    }

    /**
     * Get Base64 data URI of the company stamp for reliable DomPDF rendering.
     */
    public static function companyStampBase64(): ?string
    {
        if (!static::hasCompanyStamp()) {
            return null;
        }

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
    public static function renderSignatureAndStamp(
        bool $isPdf = false,
        int $height = 48,
        ?int $stampSize = null,
        string $style = ''
    ): string {
        return view('components.signature-stamp', [
            'isPdf' => $isPdf,
            'height' => $height,
            'stampSize' => $stampSize,
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
