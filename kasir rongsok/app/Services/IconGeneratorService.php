<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Throwable;

class IconGeneratorService
{
    /**
     * Convert an image file (PNG, JPG, WEBP) into a Windows ICO file.
     */
    public static function generateIcoFromImage(string $sourceImagePath, string $targetIcoPath): bool
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

            // Target dimensions for Windows desktop icon (256x256)
            $size = 256;
            $canvas = imagecreatetruecolor($size, $size);

            // Enable full transparency
            imagealphablending($canvas, false);
            imagesavealpha($canvas, true);
            $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
            imagefilledrectangle($canvas, 0, 0, $size, $size, $transparent);
            imagealphablending($canvas, true);

            // Preserve aspect ratio
            $aspect = $srcW / max(1, $srcH);
            if ($aspect >= 1) {
                $dstW = $size - 16;
                $dstH = (int) round($dstW / $aspect);
                $dstX = 8;
                $dstY = (int) round(($size - $dstH) / 2);
            } else {
                $dstH = $size - 16;
                $dstW = (int) round($dstH * $aspect);
                $dstY = 8;
                $dstX = (int) round(($size - $dstW) / 2);
            }

            imagecopyresampled($canvas, $sourceImg, $dstX, $dstY, 0, 0, $dstW, $dstH, $srcW, $srcH);
            imagedestroy($sourceImg);

            // Convert canvas to PNG bytes
            imagealphablending($canvas, false);
            imagesavealpha($canvas, true);
            ob_start();
            imagepng($canvas, null, 9);
            $pngBytes = ob_get_clean();
            imagedestroy($canvas);

            if (!$pngBytes) {
                return false;
            }

            // Windows ICO Header (6 bytes) + Directory Entry (16 bytes) + PNG Data
            $icoHeader = pack('vvv', 0, 1, 1);
            $icoEntry = pack('CCCCvvVV', 0, 0, 0, 0, 1, 32, strlen($pngBytes), 22);
            $icoData = $icoHeader . $icoEntry . $pngBytes;

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
    public static function generateDefaultIco(string $targetIcoPath): bool
    {
        try {
            $size = 256;
            $canvas = imagecreatetruecolor($size, $size);
            imagesavealpha($canvas, true);
            $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
            imagefill($canvas, 0, 0, $transparent);

            // Rounded circle background
            $emerald = imagecolorallocate($canvas, 5, 150, 105);
            imagefilledellipse($canvas, 128, 128, 236, 236, $emerald);

            // Inner circle
            $darkEmerald = imagecolorallocate($canvas, 4, 120, 87);
            imagefilledellipse($canvas, 128, 128, 190, 190, $darkEmerald);

            // White text label
            $white = imagecolorallocate($canvas, 255, 255, 255);
            imagestring($canvas, 5, 85, 105, "KASIR", $white);
            imagestring($canvas, 5, 75, 125, "RONGSOK", $white);

            ob_start();
            imagepng($canvas, null, 9);
            $pngBytes = ob_get_clean();
            imagedestroy($canvas);

            $icoHeader = pack('vvv', 0, 1, 1);
            $icoEntry = pack('CCCCvvVV', 0, 0, 0, 0, 1, 32, strlen($pngBytes), 22);
            $icoData = $icoHeader . $icoEntry . $pngBytes;

            @file_put_contents($targetIcoPath, $icoData);
            return true;
        } catch (Throwable $e) {
            Log::warning('Gagal membuat default ICO: ' . $e->getMessage());
            return false;
        }
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
            $escapedIco = addslashes($icoPath);
            $psScript = "\$wsh = New-Object -ComObject WScript.Shell; "
                . "\$desk = [Environment]::GetFolderPath('Desktop'); "
                . "\$deskLnk = Join-Path \$desk 'Kasir Rongsok.lnk'; "
                . "if (Test-Path \$deskLnk) { \$s = \$wsh.CreateShortcut(\$deskLnk); \$s.IconLocation = '{$escapedIco},0'; \$s.Save(); }; "
                . "\$projDir = Split-Path -Parent '{$escapedIco}'; "
                . "\$projLnk = Join-Path \$projDir 'START_KASIR_RONGSOK.lnk'; "
                . "if (Test-Path \$projLnk) { \$s = \$wsh.CreateShortcut(\$projLnk); \$s.IconLocation = '{$escapedIco},0'; \$s.Save(); }; "
                . "Start-Process -FilePath 'ie4uinit.exe' -ArgumentList '-show' -WindowStyle Hidden -ErrorAction SilentlyContinue;";

            $cmd = 'powershell.exe -NoProfile -ExecutionPolicy Bypass -Command "' . $psScript . '"';
            @pclose(@popen("start /B " . $cmd, "r"));
        } catch (Throwable $e) {
            Log::warning('Gagal update shortcut Windows: ' . $e->getMessage());
        }
    }
}
