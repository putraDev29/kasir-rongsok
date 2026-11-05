<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\BusinessSetting;
use App\Services\IconGeneratorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Throwable;

class SettingController extends Controller
{
    public function index(): View
    {
        $settings = BusinessSetting::pluck('value', 'key')->all();
        return view('settings.index', compact('settings'));
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'app_name' => 'required|string|max:100',
            'app_subtitle' => 'nullable|string|max:150',
            'company_name' => 'required|string|max:150',
            'company_tagline' => 'nullable|string|max:255',
            'company_phone' => 'nullable|string|max:50',
            'company_address' => 'nullable|string|max:300',
            'receipt_header' => 'nullable|string|max:300',
            'receipt_footer' => 'nullable|string|max:500',
            'receipt_disclaimer' => 'nullable|string|max:300',
            'receipt_terms' => 'nullable|string|max:1000',
            'pickup_service_text' => 'nullable|string|max:500',
            'delivery_order_notes' => 'nullable|string|max:500',
            'debt_agreement_terms' => 'nullable|string|max:1000',
            'salary_slip_notes' => 'nullable|string|max:500',
            'default_printer_type' => 'required|in:thermal_58,thermal_80,a4',
            'app_logo' => 'nullable|image|mimes:jpeg,png,jpg,webp,svg|max:2048',
            'remove_logo' => 'nullable|boolean',
            'owner_signature' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'remove_signature' => 'nullable|boolean',
        ]);

        // 1. Handle Logo Upload or Removal
        try {
            if ($request->hasFile('app_logo')) {
                $file = $request->file('app_logo');
                $extension = $file->getClientOriginalExtension() ?: 'png';
                $filename = 'logo_' . time() . '.' . $extension;

                // Determine target directory via public_path()
                $destinationPath = public_path('uploads/logo');
                if (!file_exists($destinationPath)) {
                    @mkdir($destinationPath, 0775, true);
                }

                // Detect if parent uploads directory exists (e.g. shared hosting Hostinger / cPanel layout)
                $parentUploadPath = null;
                if (file_exists(base_path('../uploads')) && is_dir(base_path('../uploads'))) {
                    $parentUploadPath = realpath(base_path('../uploads')) . DIRECTORY_SEPARATOR . 'logo';
                    if (!file_exists($parentUploadPath)) {
                        @mkdir($parentUploadPath, 0775, true);
                    }
                }

                // Remove previous logo if exists (clean up from all candidate directories)
                $oldLogo = BusinessSetting::get('app_logo');
                if ($oldLogo) {
                    if (file_exists(public_path($oldLogo))) {
                        @unlink(public_path($oldLogo));
                    }
                    if (file_exists(base_path('../' . $oldLogo))) {
                        @unlink(base_path('../' . $oldLogo));
                    }
                    if (file_exists(base_path('public/' . $oldLogo))) {
                        @unlink(base_path('public/' . $oldLogo));
                    }
                }

                // Move uploaded file to active public path
                $file->move($destinationPath, $filename);

                // If parent uploads exists and is distinct from destinationPath, mirror the file there too
                if ($parentUploadPath && realpath($destinationPath) !== realpath($parentUploadPath)) {
                    @copy($destinationPath . DIRECTORY_SEPARATOR . $filename, $parentUploadPath . DIRECTORY_SEPARATOR . $filename);
                }

                // Also mirror to base_path('public/uploads/logo') if distinct
                $localPublicUploadPath = base_path('public/uploads/logo');
                if (file_exists(base_path('public/uploads')) && realpath($destinationPath) !== realpath($localPublicUploadPath)) {
                    if (!file_exists($localPublicUploadPath)) {
                        @mkdir($localPublicUploadPath, 0775, true);
                    }
                    @copy($destinationPath . DIRECTORY_SEPARATOR . $filename, $localPublicUploadPath . DIRECTORY_SEPARATOR . $filename);
                }

                BusinessSetting::set('app_logo', 'uploads/logo/' . $filename, 'branding');

                // Generate Windows ICO & update Desktop/Project shortcuts
                $uploadedFullPath = $destinationPath . DIRECTORY_SEPARATOR . $filename;
                $projectRoot = IconGeneratorService::resolveProjectRoot();
                $activeLogoIcoPath = $projectRoot . DIRECTORY_SEPARATOR . 'app_logo_active.ico';
                $legacyIcoPath = $projectRoot . DIRECTORY_SEPARATOR . 'app_icon.ico';
                $publicIcoPath = public_path('favicon.ico');

                if (IconGeneratorService::generateIcoFromImage($uploadedFullPath, $activeLogoIcoPath)) {
                    @copy($activeLogoIcoPath, $legacyIcoPath);
                    @copy($activeLogoIcoPath, $publicIcoPath);
                    IconGeneratorService::syncWindowsDesktopShortcut($activeLogoIcoPath);
                } elseif (IconGeneratorService::generateIcoFromImage($uploadedFullPath, $publicIcoPath)) {
                    @copy($publicIcoPath, $legacyIcoPath);
                }
            } elseif ($request->boolean('remove_logo')) {
                $oldLogo = BusinessSetting::get('app_logo');
                if ($oldLogo) {
                    if (file_exists(public_path($oldLogo))) {
                        @unlink(public_path($oldLogo));
                    }
                    if (file_exists(base_path('../' . $oldLogo))) {
                        @unlink(base_path('../' . $oldLogo));
                    }
                    if (file_exists(base_path('public/' . $oldLogo))) {
                        @unlink(base_path('public/' . $oldLogo));
                    }
                }
                BusinessSetting::set('app_logo', null, 'branding');

                // Reset Windows ICO to default icon & update shortcuts
                $projectRoot = IconGeneratorService::resolveProjectRoot();
                $activeLogoIcoPath = $projectRoot . DIRECTORY_SEPARATOR . 'app_logo_active.ico';
                $legacyIcoPath = $projectRoot . DIRECTORY_SEPARATOR . 'app_icon.ico';
                $publicIcoPath = public_path('favicon.ico');

                if (file_exists($activeLogoIcoPath)) {
                    @unlink($activeLogoIcoPath);
                }

                if (IconGeneratorService::generateDefaultIco($legacyIcoPath)) {
                    @copy($legacyIcoPath, $publicIcoPath);
                    IconGeneratorService::syncWindowsDesktopShortcut($legacyIcoPath);
                } else {
                    IconGeneratorService::generateDefaultIco($publicIcoPath);
                }
            }
        } catch (Throwable $e) {
            Log::error('Gagal mengunggah logo usaha: ' . $e->getMessage(), [
                'exception' => $e,
                'public_path' => public_path(),
                'base_path' => base_path(),
            ]);

            return back()->withInput()->with('error', 'Gagal menyimpan file logo: ' . $e->getMessage() . '. Pastikan folder uploads/logo memiliki izin tulis (chmod 755 / 775).');
        }

        // 2. Handle Owner Signature Upload or Removal
        try {
            if ($request->hasFile('owner_signature')) {
                $file = $request->file('owner_signature');
                $extension = $file->getClientOriginalExtension() ?: 'png';
                $filename = 'signature_' . time() . '.' . $extension;

                $destinationPath = public_path('uploads/signature');
                if (!file_exists($destinationPath)) {
                    @mkdir($destinationPath, 0775, true);
                }

                // Remove previous signature if exists
                $oldSig = BusinessSetting::get('owner_signature');
                if ($oldSig) {
                    if (file_exists(public_path($oldSig))) {
                        @unlink(public_path($oldSig));
                    }
                    if (file_exists(base_path('../' . $oldSig))) {
                        @unlink(base_path('../' . $oldSig));
                    }
                    if (file_exists(base_path('public/' . $oldSig))) {
                        @unlink(base_path('public/' . $oldSig));
                    }
                }

                $file->move($destinationPath, $filename);

                // Normalisasi dimensi gambar tanda tangan agar pas dengan area tanda tangan dokumen (maks 600x300)
                $this->normalizeSignatureImage($destinationPath . DIRECTORY_SEPARATOR . $filename);

                // Mirror if parent uploads directory exists
                if (file_exists(base_path('../uploads')) && is_dir(base_path('../uploads'))) {
                    $parentUploadPath = realpath(base_path('../uploads')) . DIRECTORY_SEPARATOR . 'signature';
                    if (!file_exists($parentUploadPath)) {
                        @mkdir($parentUploadPath, 0775, true);
                    }
                    @copy($destinationPath . DIRECTORY_SEPARATOR . $filename, $parentUploadPath . DIRECTORY_SEPARATOR . $filename);
                }

                BusinessSetting::set('owner_signature', 'uploads/signature/' . $filename, 'branding');
            } elseif ($request->boolean('remove_signature')) {
                $oldSig = BusinessSetting::get('owner_signature');
                if ($oldSig) {
                    if (file_exists(public_path($oldSig))) {
                        @unlink(public_path($oldSig));
                    }
                    if (file_exists(base_path('../' . $oldSig))) {
                        @unlink(base_path('../' . $oldSig));
                    }
                    if (file_exists(base_path('public/' . $oldSig))) {
                        @unlink(base_path('public/' . $oldSig));
                    }
                }
                BusinessSetting::set('owner_signature', null, 'branding');
            }
        } catch (Throwable $e) {
            Log::error('Gagal mengunggah tanda tangan owner: ' . $e->getMessage(), [
                'exception' => $e,
                'public_path' => public_path(),
            ]);

            return back()->withInput()->with('error', 'Gagal menyimpan file tanda tangan: ' . $e->getMessage() . '. Pastikan folder uploads/signature memiliki izin tulis.');
        }

        // 3. Save text settings
        $fieldsToSave = [
            'app_name',
            'app_subtitle',
            'company_name',
            'company_tagline',
            'company_phone',
            'company_address',
            'receipt_header',
            'receipt_footer',
            'receipt_disclaimer',
            'receipt_terms',
            'pickup_service_text',
            'delivery_order_notes',
            'debt_agreement_terms',
            'salary_slip_notes',
            'default_printer_type',
        ];

        foreach ($fieldsToSave as $field) {
            if ($request->has($field)) {
                BusinessSetting::set($field, $request->input($field));
            }
        }

        ActivityLog::record(
            action: 'settings_updated',
            description: 'Memperbarui profil usaha, identitas sidebar, logo, dan teks nota.'
        );

        return back()->with('success', 'Pengaturan usaha, identitas header/sidebar, dan logo berhasil diperbarui.');
    }

    /**
     * Normalisasi ukuran tanda tangan agar proporsional dan tidak meledak di dokumen cetak/PDF.
     * Maksimal batas bounding box: 600px x 300px dengan mempertahankan transparansi PNG/WebP.
     */
    protected function normalizeSignatureImage(string $filePath): void
    {
        if (!extension_loaded('gd') || !file_exists($filePath)) {
            return;
        }

        $imageInfo = @getimagesize($filePath);
        if (!$imageInfo) {
            return;
        }

        [$origW, $origH, $type] = $imageInfo;
        $maxW = 600;
        $maxH = 300;

        if ($origW <= $maxW && $origH <= $maxH) {
            return;
        }

        $ratio = min($maxW / $origW, $maxH / $origH);
        $newW = (int) round($origW * $ratio);
        $newH = (int) round($origH * $ratio);

        $srcImg = null;
        switch ($type) {
            case IMAGETYPE_PNG:
                $srcImg = @imagecreatefrompng($filePath);
                break;
            case IMAGETYPE_JPEG:
                $srcImg = @imagecreatefromjpeg($filePath);
                break;
            case IMAGETYPE_WEBP:
                if (function_exists('imagecreatefromwebp')) {
                    $srcImg = @imagecreatefromwebp($filePath);
                }
                break;
        }

        if (!$srcImg) {
            return;
        }

        $dstImg = imagecreatetruecolor($newW, $newH);
        imagealphablending($dstImg, false);
        imagesavealpha($dstImg, true);
        $transparent = imagecolorallocatealpha($dstImg, 255, 255, 255, 127);
        imagefilledrectangle($dstImg, 0, 0, $newW, $newH, $transparent);

        imagecopyresampled($dstImg, $srcImg, 0, 0, 0, 0, $newW, $newH, $origW, $origH);

        switch ($type) {
            case IMAGETYPE_PNG:
                imagepng($dstImg, $filePath, 8);
                break;
            case IMAGETYPE_JPEG:
                imagejpeg($dstImg, $filePath, 90);
                break;
            case IMAGETYPE_WEBP:
                if (function_exists('imagewebp')) {
                    imagewebp($dstImg, $filePath, 90);
                }
                break;
        }

        imagedestroy($srcImg);
        imagedestroy($dstImg);
    }
}
