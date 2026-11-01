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
            'default_printer_type' => 'required|in:thermal_58,thermal_80,a4',
            'app_logo' => 'nullable|image|mimes:jpeg,png,jpg,webp,svg|max:2048',
            'remove_logo' => 'nullable|boolean',
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
                $rootIcoPath = realpath(base_path('..')) ? realpath(base_path('..')) . DIRECTORY_SEPARATOR . 'app_icon.ico' : base_path('../app_icon.ico');
                $publicIcoPath = public_path('favicon.ico');

                if (IconGeneratorService::generateIcoFromImage($uploadedFullPath, $rootIcoPath)) {
                    @copy($rootIcoPath, $publicIcoPath);
                    IconGeneratorService::syncWindowsDesktopShortcut($rootIcoPath);
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
                $rootIcoPath = realpath(base_path('..')) ? realpath(base_path('..')) . DIRECTORY_SEPARATOR . 'app_icon.ico' : base_path('../app_icon.ico');
                $publicIcoPath = public_path('favicon.ico');
                if (IconGeneratorService::generateDefaultIco($rootIcoPath)) {
                    @copy($rootIcoPath, $publicIcoPath);
                    IconGeneratorService::syncWindowsDesktopShortcut($rootIcoPath);
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

        // 2. Save text settings
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
}
