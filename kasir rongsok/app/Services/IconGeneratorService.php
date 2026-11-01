<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Throwable;

class IconGeneratorService
{
    /**
     * Standard icon resolutions for Windows Desktop, Explorer, Taskbar, and DPI scaling.
     */
    private const ICON_SIZES = [256, 128, 64, 48, 32, 16];

    /**
     * Convert an image file (PNG, JPG, WEBP) into a multi-resolution Windows ICO file.
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

            $images = [];

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

                imagealphablending($canvas, false);
                imagesavealpha($canvas, true);
                ob_start();
                imagepng($canvas, null, 9);
                $pngData = ob_get_clean();
                imagedestroy($canvas);

                if ($pngData) {
                    $images[$size] = $pngData;
                }
            }

            imagedestroy($sourceImg);

            if (empty($images)) {
                return false;
            }

            $icoData = self::packIcoBinary($images);
            @file_put_contents($targetIcoPath, $icoData);

            return true;
        } catch (Throwable $e) {
            Log::warning('Gagal menghasilkan icon ICO: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Generate default Kasir Rongsok emerald recycle ICO when no logo is uploaded.
     */
    public static function generateDefaultIco(string $targetIcoPath, array $sizes = self::ICON_SIZES): bool
    {
        try {
            $images = [];

            foreach ($sizes as $size) {
                $canvas = imagecreatetruecolor($size, $size);
                imagesavealpha($canvas, true);
                $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
                imagefill($canvas, 0, 0, $transparent);

                // Rounded circle background
                $emerald = imagecolorallocate($canvas, 5, 150, 105);
                $outerRadius = (int) round($size * 0.92);
                $center = (int) round($size / 2);
                imagefilledellipse($canvas, $center, $center, $outerRadius, $outerRadius, $emerald);

                // Inner circle
                $darkEmerald = imagecolorallocate($canvas, 4, 120, 87);
                $innerRadius = (int) round($size * 0.74);
                imagefilledellipse($canvas, $center, $center, $innerRadius, $innerRadius, $darkEmerald);

                // White text label if size is large enough (>= 48px)
                if ($size >= 48) {
                    $white = imagecolorallocate($canvas, 255, 255, 255);
                    $font = ($size >= 128) ? 5 : 2;
                    $text1 = "KASIR";
                    $text2 = "RONGSOK";
                    $fw = imagefontwidth($font);
                    $fh = imagefontheight($font);
                    imagestring($canvas, $font, (int) round($center - (strlen($text1) * $fw / 2)), (int) round($center - $fh - 2), $text1, $white);
                    imagestring($canvas, $font, (int) round($center - (strlen($text2) * $fw / 2)), (int) round($center + 2), $text2, $white);
                }

                ob_start();
                imagepng($canvas, null, 9);
                $pngData = ob_get_clean();
                imagedestroy($canvas);

                if ($pngData) {
                    $images[$size] = $pngData;
                }
            }

            if (empty($images)) {
                return false;
            }

            $icoData = self::packIcoBinary($images);
            @file_put_contents($targetIcoPath, $icoData);

            return true;
        } catch (Throwable $e) {
            Log::warning('Gagal membuat default ICO: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Assemble PNG image streams into standard Windows ICO binary format.
     */
    private static function packIcoBinary(array $images): string
    {
        $count = count($images);
        $header = pack('vvv', 0, 1, $count);
        $offset = 6 + ($count * 16);
        $entries = '';
        $payload = '';

        foreach ($images as $size => $data) {
            $w = ($size >= 256) ? 0 : $size;
            $h = ($size >= 256) ? 0 : $size;
            $len = strlen($data);

            $entry = pack('CCCCvvVV', $w, $h, 0, 0, 1, 32, $len, $offset);
            $entries .= $entry;
            $payload .= $data;
            $offset += $len;
        }

        return $header . $entries . $payload;
    }

    /**
     * Synchronize Windows Desktop & Project .lnk shortcuts to use the active ICO.
     */
    public static function syncWindowsDesktopShortcut(string $icoPath): void
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
            return;
        }

        if (!file_exists($icoPath)) {
            return;
        }

        try {
            $normalizedIco = str_replace('/', '\\', realpath($icoPath) ?: $icoPath);

            $psScript = '
$ico = "' . $normalizedIco . '";
$wsh = New-Object -ComObject WScript.Shell;

# 1. Update Project Directory Shortcut
$projDir = Split-Path -Parent $ico;
$projLnk = Join-Path $projDir "START_KASIR_RONGSOK.lnk";
if (Test-Path $projLnk) {
    $s = $wsh.CreateShortcut($projLnk);
    $s.IconLocation = "$ico,0";
    $s.Save();
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
