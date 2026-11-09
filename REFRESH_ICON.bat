@echo off
title Memperbarui Icon Kasir Rongsok
cd /d "%~dp0"

echo ====================================================================
echo   MEMPERBARUI ICON KASIR RONGSOK ^& MEMBERSIHKAN CACHE WINDOWS
echo ====================================================================
echo.

:: 1. Cari PHP di XAMPP atau PATH
set "PHP_CMD=php"
where php >nul 2>&1
if %errorLevel% neq 0 (
    if exist "C:\xampp\php\php.exe" (
        set "PHP_CMD=C:\xampp\php\php.exe"
    ) else if exist "D:\xampp\php\php.exe" (
        set "PHP_CMD=D:\xampp\php\php.exe"
    ) else if exist "E:\xampp\php\php.exe" (
        set "PHP_CMD=E:\xampp\php\php.exe"
    ) else if exist "F:\xampp\php\php.exe" (
        set "PHP_CMD=F:\xampp\php\php.exe"
    ) else if exist "C:\php\php.exe" (
        set "PHP_CMD=C:\php\php.exe"
    ) else if exist "D:\php\php.exe" (
        set "PHP_CMD=D:\php\php.exe"
    )
)

:: 2. Generate Icon ICO Baru (Format DIB 32-bit Anti-Blank)
echo [1/3] Menghasilkan file icon Windows resolusi tinggi (Anti-Blank)...
cd "kasir rongsok"
call "%PHP_CMD%" artisan app:generate-icon
cd ..

:: 3. Perbarui Pintasan Desktop & Folder Proyek
echo.
echo [2/3] Memperbarui pintasan desktop Kasir Rongsok...
powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0setup_shortcuts.ps1"

:: 4. Bersihkan Cache Icon Windows Explorer agar icon langsung berubah
echo.
echo [3/3] Membersihkan Cache Icon Windows Explorer...
taskkill /f /im explorer.exe >nul 2>&1
del /f /q "%LOCALAPPDATA%\IconCache.db" >nul 2>&1
del /f /q "%LOCALAPPDATA%\Microsoft\Windows\Explorer\iconcache_*.db" >nul 2>&1
start explorer.exe

echo.
echo ====================================================================
echo   SUKSES! Icon Kasir Rongsok berhasil diperbarui dan segar kembali!
echo ====================================================================
echo.
pause
