<?php

namespace App\Services;

use App\Models\BusinessSetting;
use Illuminate\Support\Facades\Log;
use Throwable;

class IconGeneratorService
{
    /**
     * Standard icon resolutions for Windows Desktop, Explorer, Taskbar, and DPI scaling.
     */
    private const ICON_SIZES = [256, 128, 64, 48, 32, 16];

    /**
     * Resolve project root dynamically (finding the folder containing launchers and icons).
     */
    public static function resolveProjectRoot(): string
    {
        $parent = realpath(base_path('..'));
        if ($parent && (file_exists($parent . DIRECTORY_SEPARATOR . 'START_KASIR_RONGSOK.vbs') || file_exists($parent . DIRECTORY_SEPARATOR . 'setup_shortcuts.ps1') || file_exists($parent . DIRECTORY_SEPARATOR . 'app_icon.ico'))) {
            return $parent;
        }

        $base = realpath(base_path());
        if ($base && (file_exists($base . DIRECTORY_SEPARATOR . 'START_KASIR_RONGSOK.vbs') || file_exists($base . DIRECTORY_SEPARATOR . 'setup_shortcuts.ps1'))) {
            return $base;
        }

        return $parent ?: base_path('..');
    }

    /**
     * Resolve active icon path dynamically, generating default if none exists or is corrupt.
     */
    public static function resolveActiveIconPath(): string
    {
        $projectRoot = self::resolveProjectRoot();
        $candidates = [
            $projectRoot . DIRECTORY_SEPARATOR . 'app_logo_active.ico',
            $projectRoot . DIRECTORY_SEPARATOR . 'app_icon.ico',
            public_path('favicon.ico'),
        ];

        foreach ($candidates as $cand) {
            if (file_exists($cand) && filesize($cand) > 500) {
                return $cand;
            }
        }

        // None exists or valid: generate dynamically from current logo or default
        $targetIco = $projectRoot . DIRECTORY_SEPARATOR . 'app_icon.ico';
        self::generateCurrentOrFallbackIco($targetIco);

        return $targetIco;
    }

    /**
     * Generate an ICO from the active business logo, or default if no logo exists.
     */
    public static function generateCurrentOrFallbackIco(?string $targetIcoPath = null): bool
    {
        $projectRoot = self::resolveProjectRoot();
        $targetIco = $targetIcoPath ?: ($projectRoot . DIRECTORY_SEPARATOR . 'app_icon.ico');

        $activeLogoRelative = BusinessSetting::get('app_logo');
        if ($activeLogoRelative) {
            $possiblePaths = [
                public_path($activeLogoRelative),
                base_path($activeLogoRelative),
                $projectRoot . DIRECTORY_SEPARATOR . 'kasir rongsok' . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . $activeLogoRelative,
                $projectRoot . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . $activeLogoRelative,
            ];

            foreach ($possiblePaths as $path) {
                if (file_exists($path) && filesize($path) > 0) {
                    $activeTarget = $projectRoot . DIRECTORY_SEPARATOR . 'app_logo_active.ico';
                    $success = self::generateIcoFromImage($path, $activeTarget);
                    if ($success) {
                        @copy($activeTarget, $targetIco);
                        @copy($activeTarget, public_path('favicon.ico'));
                        self::syncWindowsDesktopShortcut($activeTarget);
                        return true;
                    }
                }
            }
        }

        // Fallback: generate crisp default ICO
        $success = self::generateDefaultIco($targetIco);
        if ($success) {
            @copy($targetIco, public_path('favicon.ico'));
            self::syncWindowsDesktopShortcut($targetIco);
        }

        return $success;
    }

    /**
     * Convert an image file (PNG, JPG, WEBP) into a multi-resolution Windows ICO file.
     * Uses 32-bit BGRA DIB with 1-bit AND mask for standard sizes (128, 64, 48, 32, 16)
     * and PNG for 256px to guarantee crisp rendering without black background on all Windows versions.
     */
    public static function generateIcoFromImage(string $sourceImagePath, string $targetIcoPath, array $sizes = self::ICON_SIZES): bool
    {
        try {
            if (!file_exists($sourceImagePath)) {
                return false;
            }

            $imgData = @file_get_contents($sourceImagePath);
            if (!$imgData) {
                return false;
            }

            $sourceImg = @imagecreatefromstring($imgData);
            if (!$sourceImg) {
                return false;
            }

            $srcW = imagesx($sourceImg);
            $srcH = imagesy($sourceImg);
            $aspect = $srcW / max(1, $srcH);

            $frames = [];

            foreach ($sizes as $size) {
                $canvas = imagecreatetruecolor($size, $size);
                imagealphablending($canvas, false);
                imagesavealpha($canvas, true);
                $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
                imagefilledrectangle($canvas, 0, 0, $size, $size, $transparent);
                imagealphablending($canvas, true);

                $padding = max(1, (int) round($size * 0.04));
                $avail = $size - ($padding * 2);

                if ($aspect >= 1) {
                    $dstW = $avail;
                    $dstH = (int) round($dstW / $aspect);
                    $dstX = $padding;
                    $dstY = (int) round(($size - $dstH) / 2);
                } else {
                    $dstH = $avail;
                    $dstW = (int) round($dstH * $aspect);
                    $dstY = $padding;
                    $dstX = (int) round(($size - $dstW) / 2);
                }

                imagecopyresampled($canvas, $sourceImg, $dstX, $dstY, 0, 0, $dstW, $dstH, $srcW, $srcH);

                if ($size === 256) {
                    // Vista+ large frame as PNG
                    imagealphablending($canvas, false);
                    imagesavealpha($canvas, true);
                    ob_start();
                    imagepng($canvas, null, 9);
                    $frames[$size] = [
                        'type' => 'png',
                        'data' => ob_get_clean(),
                        'bpp' => 32,
                    ];
                } else {
                    // 128, 64, 48, 32, 16 as standard DIB with 1-bit AND mask for 100% Windows GDI compatibility
                    $frames[$size] = [
                        'type' => 'dib',
                        'data' => self::buildDibFrame($canvas),
                        'bpp' => 32,
                    ];
                }

                imagedestroy($canvas);
            }

            imagedestroy($sourceImg);

            if (empty($frames)) {
                return false;
            }

            $icoData = self::packIcoBinaryWithFrames($frames);
            @file_put_contents($targetIcoPath, $icoData);

            // Generate companion PWA icons (192px & 512px)
            $sourceDir = dirname($sourceImagePath);
            self::generatePwaIcons($sourceImagePath, $sourceDir);

            // Also copy to public/uploads/logo if different
            $publicUploadLogo = public_path('uploads/logo');
            if (is_dir($publicUploadLogo) && realpath($sourceDir) !== realpath($publicUploadLogo)) {
                self::generatePwaIcons($sourceImagePath, $publicUploadLogo);
            }

            return true;
        } catch (Throwable $e) {
            Log::warning('Gagal menghasilkan icon ICO: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Generate standard 192px and 512px PNG icons for Android/iOS PWA manifests.
     */
    public static function generatePwaIcons(string $sourceImagePath, string $targetDir): void
    {
        try {
            if (!file_exists($sourceImagePath) || !is_dir($targetDir)) {
                return;
            }

            $imgData = @file_get_contents($sourceImagePath);
            if (!$imgData) return;

            $sourceImg = @imagecreatefromstring($imgData);
            if (!$sourceImg) return;

            $srcW = imagesx($sourceImg);
            $srcH = imagesy($sourceImg);
            $aspect = $srcW / max(1, $srcH);

            $pwaConfigs = [
                ['size' => 192, 'file' => 'pwa_icon_192.png', 'pad' => 0.05],
                ['size' => 512, 'file' => 'pwa_icon_512.png', 'pad' => 0.05],
                ['size' => 512, 'file' => 'pwa_icon_maskable.png', 'pad' => 0.15],
            ];

            foreach ($pwaConfigs as $cfg) {
                $size = $cfg['size'];
                $canvas = imagecreatetruecolor($size, $size);
                imagealphablending($canvas, false);
                imagesavealpha($canvas, true);
                $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
                imagefilledrectangle($canvas, 0, 0, $size, $size, $transparent);
                imagealphablending($canvas, true);

                $padding = (int) round($size * $cfg['pad']);
                $avail = $size - ($padding * 2);

                if ($aspect >= 1) {
                    $dstW = $avail;
                    $dstH = (int) round($dstW / $aspect);
                    $dstX = $padding;
                    $dstY = (int) round(($size - $dstH) / 2);
                } else {
                    $dstH = $avail;
                    $dstW = (int) round($dstH * $aspect);
                    $dstY = $padding;
                    $dstX = (int) round(($size - $dstW) / 2);
                }

                imagecopyresampled($canvas, $sourceImg, $dstX, $dstY, 0, 0, $dstW, $dstH, $srcW, $srcH);

                imagealphablending($canvas, false);
                imagesavealpha($canvas, true);
                imagepng($canvas, $targetDir . DIRECTORY_SEPARATOR . $cfg['file'], 9);
                imagedestroy($canvas);
            }

            imagedestroy($sourceImg);
        } catch (Throwable $e) {
            Log::warning('Gagal generate PWA icons: ' . $e->getMessage());
        }
    }

    /**
     * Generate default Kasir Rongsok emerald recycle ICO when no logo is uploaded.
     * Guaranteed to have zero black box on any Windows PC.
     */
    public static function generateDefaultIco(string $targetIcoPath, array $sizes = self::ICON_SIZES): bool
    {
        try {
            $frames = [];

            foreach ($sizes as $size) {
                $canvas = imagecreatetruecolor($size, $size);
                imagealphablending($canvas, false);
                imagesavealpha($canvas, true);
                $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
                imagefilledrectangle($canvas, 0, 0, $size, $size, $transparent);
                imagealphablending($canvas, true);

                // Emerald green circle badge
                $emerald = imagecolorallocate($canvas, 5, 150, 105);
                $darkEmerald = imagecolorallocate($canvas, 4, 120, 87);
                $white = imagecolorallocate($canvas, 255, 255, 255);

                $center = (int) round($size / 2);
                $outerRadius = (int) round($size * 0.94);
                $innerRadius = (int) round($size * 0.78);

                imagefilledellipse($canvas, $center, $center, $outerRadius, $outerRadius, $emerald);
                imagefilledellipse($canvas, $center, $center, $innerRadius, $innerRadius, $darkEmerald);

                // Clear typography for various resolutions
                if ($size >= 32) {
                    $font = ($size >= 128) ? 5 : ($size >= 48 ? 3 : 1);
                    $text1 = "KASIR";
                    $text2 = "RONGSOK";
                    $fw = imagefontwidth($font);
                    $fh = imagefontheight($font);
                    imagestring($canvas, $font, (int) round($center - (strlen($text1) * $fw / 2)), (int) round($center - $fh - 1), $text1, $white);
                    if ($size >= 48) {
                        imagestring($canvas, $font, (int) round($center - (strlen($text2) * $fw / 2)), (int) round($center + 2), $text2, $white);
                    }
                }

                if ($size === 256) {
                    imagealphablending($canvas, false);
                    imagesavealpha($canvas, true);
                    ob_start();
                    imagepng($canvas, null, 9);
                    $frames[$size] = [
                        'type' => 'png',
                        'data' => ob_get_clean(),
                        'bpp' => 32,
                    ];
                } else {
                    $frames[$size] = [
                        'type' => 'dib',
                        'data' => self::buildDibFrame($canvas),
                        'bpp' => 32,
                    ];
                }

                imagedestroy($canvas);
            }

            if (empty($frames)) {
                return false;
            }

            $icoData = self::packIcoBinaryWithFrames($frames);
            @file_put_contents($targetIcoPath, $icoData);

            return true;
        } catch (Throwable $e) {
            Log::warning('Gagal membuat default ICO: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Build standard Windows DIB bitmap frame (BITMAPINFOHEADER + BGRA 32bpp + 1-bit AND mask).
     * Windows GDI / Explorer requires this format for sizes 16, 32, 48 to avoid blank black rendering.
     */
    private static function buildDibFrame($gdImage): string
    {
        $width = imagesx($gdImage);
        $height = imagesy($gdImage);

        $xorData = '';
        $andData = '';
        $andRowBytes = (int) ceil($width / 32) * 4; // 32-bit aligned row in bytes

        // Windows DIB scanlines are ordered bottom-to-top
        for ($y = $height - 1; $y >= 0; $y--) {
            $andRowBits = '';
            for ($x = 0; $x < $width; $x++) {
                $rgba = imagecolorat($gdImage, $x, $y);
                $gdAlpha = ($rgba >> 24) & 0x7F;
                $r = ($rgba >> 16) & 0xFF;
                $g = ($rgba >> 8) & 0xFF;
                $b = $rgba & 0xFF;

                // GD alpha: 0 (opaque) .. 127 (transparent)
                // Windows alpha: 255 (opaque) .. 0 (transparent)
                $winAlpha = (int) round((127 - $gdAlpha) * 255 / 127);

                // Pre-multiply or zero out fully transparent pixels so GDI doesn't draw black
                if ($winAlpha === 0) {
                    $r = 0;
                    $g = 0;
                    $b = 0;
                }

                $xorData .= chr($b) . chr($g) . chr($r) . chr($winAlpha);

                // In 1-bit AND mask: 1 = transparent, 0 = opaque
                $andRowBits .= ($winAlpha === 0) ? '1' : '0';
            }

            // Pad scanline to 32-bit boundary
            $andRowBits = str_pad($andRowBits, $andRowBytes * 8, '0', STR_PAD_RIGHT);
            for ($b = 0; $b < $andRowBytes; $b++) {
                $byteBits = substr($andRowBits, $b * 8, 8);
                $andData .= chr(bindec($byteBits));
            }
        }

        $header = pack(
            'VVVvvVVVVVV',
            40,                                  // biSize (BITMAPINFOHEADER)
            $width,                              // biWidth
            $height * 2,                         // biHeight (doubled for XOR + AND masks)
            1,                                   // biPlanes
            32,                                  // biBitCount
            0,                                   // biCompression (BI_RGB)
            strlen($xorData) + strlen($andData), // biSizeImage
            0, 0, 0, 0                           // biXPelsPerMeter, biYPelsPerMeter, biClrUsed, biClrImportant
        );

        return $header . $xorData . $andData;
    }

    /**
     * Assemble frames into Windows ICO binary format.
     */
    private static function packIcoBinaryWithFrames(array $frames): string
    {
        $count = count($frames);
        $header = pack('vvv', 0, 1, $count);
        $offset = 6 + ($count * 16);
        $entries = '';
        $payload = '';

        foreach ($frames as $size => $f) {
            $w = ($size >= 256) ? 0 : $size;
            $h = ($size >= 256) ? 0 : $size;
            $data = $f['data'];
            $len = strlen($data);
            $bpp = $f['bpp'] ?? 32;

            $entry = pack('CCCCvvVV', $w, $h, 0, 0, 1, $bpp, $len, $offset);
            $entries .= $entry;
            $payload .= $data;
            $offset += $len;
        }

        return $header . $entries . $payload;
    }

    /**
     * Synchronize Windows Desktop & Project .lnk shortcuts to use the active ICO.
     */
    public static function syncWindowsDesktopShortcut(?string $icoPath = null): void
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
            return;
        }

        $resolvedIco = $icoPath ?: self::resolveActiveIconPath();
        if (!file_exists($resolvedIco)) {
            return;
        }

        try {
            $normalizedIco = str_replace('/', '\\', realpath($resolvedIco) ?: $resolvedIco);

            $psScript = '
$ico = "' . $normalizedIco . '";
$wsh = New-Object -ComObject WScript.Shell;

# 1. Update Project Directory Shortcut
$projDir = Split-Path -Parent $ico;
$projLnk = Join-Path $projDir "START_KASIR_RONGSOK.lnk";
if (Test-Path $projLnk) {
    try {
        $s = $wsh.CreateShortcut($projLnk);
        $s.IconLocation = "$ico,0";
        $s.Save();
    } catch {}
}

# 2. Gather candidate Desktop paths
$candidates = @();

try {
    $regDesk = (Get-ItemProperty "HKCU:\Software\Microsoft\Windows\CurrentVersion\Explorer\User Shell Folders" -ErrorAction SilentlyContinue).Desktop;
    if ($regDesk) {
        $exp = [Environment]::ExpandEnvironmentStrings($regDesk);
        if (Test-Path $exp) { $candidates += $exp; }
    }
} catch {}

try {
    $sfDesk = $wsh.SpecialFolders("Desktop");
    if ($sfDesk -and (Test-Path $sfDesk)) { $candidates += $sfDesk; }
} catch {}

if ($env:USERPROFILE) {
    $uDesk = Join-Path $env:USERPROFILE "Desktop";
    if (Test-Path $uDesk) { $candidates += $uDesk; }
    $oneDesk = Join-Path $env:USERPROFILE "OneDrive\Desktop";
    if (Test-Path $oneDesk) { $candidates += $oneDesk; }
}

if ($env:PUBLIC) {
    $pubDesk = Join-Path $env:PUBLIC "Desktop";
    if (Test-Path $pubDesk) { $candidates += $pubDesk; }
}

$uniqueDesks = $candidates | Select-Object -Unique;

foreach ($desk in $uniqueDesks) {
    try {
        $deskLnk = Join-Path $desk "Kasir Rongsok.lnk";
        if (Test-Path $deskLnk) {
            $s = $wsh.CreateShortcut($deskLnk);
            $s.IconLocation = "$ico,0";
            $s.Save();
        }
    } catch {}
}

# 3. Notify Windows Explorer Shell to immediately flush and refresh icon cache
try {
    $code = @"
[System.Runtime.InteropServices.DllImport("Shell32.dll")]
public static extern void SHChangeNotify(int eventId, int flags, System.IntPtr item1, System.IntPtr item2);
"@
    Add-Type -MemberDefinition $code -Namespace Win32 -Name ShellApi -ErrorAction SilentlyContinue
    [Win32.ShellApi]::SHChangeNotify(0x08000000, 0, [System.IntPtr]::Zero, [System.IntPtr]::Zero)
} catch {}

Start-Process -FilePath "ie4uinit.exe" -ArgumentList "-show" -WindowStyle Hidden -ErrorAction SilentlyContinue;
';

            $encodedCmd = base64_encode(mb_convert_encoding($psScript, 'UTF-16LE', 'UTF-8'));
            $cmd = 'powershell.exe -NoProfile -ExecutionPolicy Bypass -EncodedCommand ' . $encodedCmd;
            @pclose(@popen("start /B " . $cmd, "r"));
        } catch (Throwable $e) {
            Log::warning('Gagal update shortcut Windows: ' . $e->getMessage());
        }
    }
}
