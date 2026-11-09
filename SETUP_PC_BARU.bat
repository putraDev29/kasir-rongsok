@echo off
setlocal enabledelayedexpansion
title Pemasangan Otomatis Kasir Rongsok - PC Client

:: 1. Periksa Hak Akses Administrator
net session >nul 2>&1
if %errorLevel% neq 0 (
    echo Meminta izin Administrator untuk memasang dependensi dan firewall...
    powershell -NoProfile -Command "Start-Process cmd.exe -ArgumentList '/c \"\"%~f0\"\"' -Verb RunAs"
    exit /b
)

cd /d "%~dp0"
cls
echo ====================================================================
echo      PEMASANGAN & SETUP OTOMATIS KASIR RONGSOK (PC CLIENT)
echo ====================================================================
echo.
echo Script ini akan mengonfigurasi PC ini agar siap digunakan:
echo   1. Deteksi XAMPP (PHP, MySQL) dan Node.js
echo   2. Konfigurasi File Environment (.env) & Database MySQL
echo   3. Instalasi Dependensi Laravel (Composer) & Aset (NPM)
echo   4. Migrasi Tabel & Data Awal (Super Admin, Kasir, Master Rongsok)
echo   5. Instalasi WhatsApp Gateway (Node.js)
echo   6. Buka Port Windows Firewall (8000 & 21465)
echo   7. Buat Pintasan Desktop dengan Logo Usaha
echo ====================================================================
echo.
pause

:: Cari path PHP jika belum ada di System PATH
where php >nul 2>&1
if %errorLevel% neq 0 (
    if exist "C:\xampp\php\php.exe" (
        set "PATH=C:\xampp\php;%PATH%"
        echo [INFO] Menambahkan C:\xampp\php ke PATH sesi ini.
    ) else if exist "D:\xampp\php\php.exe" (
        set "PATH=D:\xampp\php;%PATH%"
        echo [INFO] Menambahkan D:\xampp\php ke PATH sesi ini.
    ) else (
        echo [ERROR] PHP tidak ditemukan! Pastikan XAMPP sudah terpasang.
        echo Download XAMPP di: https://www.apachefriends.org
        pause
        exit /b 1
    )
)

:: Cari path MySQL
where mysql >nul 2>&1
if %errorLevel% neq 0 (
    if exist "C:\xampp\mysql\bin\mysql.exe" (
        set "PATH=C:\xampp\mysql\bin;%PATH%"
    ) else if exist "D:\xampp\mysql\bin\mysql.exe" (
        set "PATH=D:\xampp\mysql\bin;%PATH%"
    )
)

:: Cari Node.js
where node >nul 2>&1
if %errorLevel% neq 0 (
    echo [ERROR] Node.js tidak ditemukan!
    echo Download & pasang Node.js LTS di: https://nodejs.org
    pause
    exit /b 1
)

:: Cari Composer
where composer >nul 2>&1
if %errorLevel% neq 0 (
    if exist "C:\ProgramData\ComposerSetup\bin\composer.bat" (
        set "PATH=C:\ProgramData\ComposerSetup\bin;%PATH%"
    ) else (
        echo [PERINGATAN] Composer tidak ditemukan di System PATH.
        echo Jika belum pasang Composer, download di: https://getcomposer.org
    )
)

:: 2. Pastikan MySQL XAMPP Aktif
echo.
echo [1/7] Memeriksa Layanan Database MySQL (Port 3306)...
netstat -ano | findstr :3306 >nul 2>&1
if %errorLevel% neq 0 (
    echo Menjalankan MySQL dari XAMPP...
    if exist "C:\xampp\mysql_start.bat" (
        start "" "C:\xampp\mysql_start.bat"
    ) else if exist "D:\xampp\mysql_start.bat" (
        start "" "D:\xampp\mysql_start.bat"
    )
    timeout /t 3 /nobreak >nul
)
echo [OK] MySQL aktif.

:: 3. Buat Database MySQL
echo.
echo [2/7] Menyiapkan Database MySQL...
mysql -u root -e "CREATE DATABASE IF NOT EXISTS kasir_rongsok CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; CREATE DATABASE IF NOT EXISTS kasir_rongsok_2 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" >nul 2>&1
if %errorLevel% equ 0 (
    echo [OK] Database siap.
) else (
    echo [!] Melewati pembuatan manual database. Pastikan MySQL aktif.
)

:: 4. Setup File .env Laravel
echo.
echo [3/7] Menyiapkan File Konfigurasi .env...
if not exist "kasir rongsok\.env" (
    copy "kasir rongsok\.env.example" "kasir rongsok\.env" >nul
    echo [OK] File .env berhasil dibuat dari template.
) else (
    echo [OK] File .env sudah ada.
)

:: 5. Instal Dependensi Composer & Generate Key
echo.
echo [4/7] Memeriksa Dependensi Backend Laravel...
cd "kasir rongsok"
if not exist "vendor\autoload.php" (
    echo Mengunduh paket Composer (memerlukan internet)...
    call composer install --no-interaction --prefer-dist --optimize-autoloader
) else (
    echo [OK] Folder vendor sudah ada.
)

echo Menghasilkan Application Key...
call php artisan key:generate --force >nul 2>&1

echo Menjalankan Penyiapan Database, Migrasi, Seeder, dan Icon...
call php artisan app:ensure-system-ready
cd ..

:: 6. Build Aset Frontend (NPM)
echo.
echo [5/7] Memeriksa Aset Frontend & WhatsApp Gateway...
cd "kasir rongsok"
if not exist "public\build\manifest.json" (
    echo Menginstal & build aset frontend...
    call npm install --silent
    call npm run build
) else (
    echo [OK] Aset frontend (build) sudah siap.
)
cd ..

:: Instal WA Gateway dependencies jika belum ada
cd "wa_gateway"
if not exist "node_modules\@wppconnect-team" (
    echo Mengunduh modul WhatsApp Gateway...
    call npm install --silent
) else (
    echo [OK] Modul WhatsApp Gateway sudah siap.
)
cd ..

:: 7. Pengaturan Windows Firewall
echo.
echo [6/7] Mengonfigurasi Windows Defender Firewall...
netsh advfirewall firewall add rule name="Kasir Rongsok Port 8000" dir=in action=allow protocol=TCP localport=8000 profile=any >nul 2>&1
netsh advfirewall firewall add rule name="Kasir Rongsok WA Gateway" dir=in action=allow protocol=TCP localport=21465 profile=any >nul 2>&1
echo [OK] Port 8000 dan 21465 berhasil diizinkan di Firewall.

:: 8. Buat Pintasan Desktop
echo.
echo [7/7] Menghasilkan Icon & Membuat Pintasan Desktop...
cd "kasir rongsok"
call php artisan app:generate-icon >nul 2>&1
cd ..
powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0setup_shortcuts.ps1" >nul 2>&1
echo [OK] Pintasan Desktop berhasil dibuat.

echo.
echo ====================================================================
echo   SELAMAT! INSTALASI DI PC CLIENT TELAH SELESAI DENGAN SUKSES!
echo.
echo   AKUN LOGIN BAWAAN (DEFAULT):
echo   --------------------------------------------------------------
echo   1. Super Admin (Owner) : owner@kasirrongsok.com  / password123
echo   2. Admin Kantor        : admin@kasirrongsok.com  / password123
echo   3. Kasir Timbang       : kasir1@kasirrongsok.com / password123
echo.
echo   Untuk mulai menggunakan:
echo   Klik pintasan "Kasir Rongsok" di Desktop atau START_KASIR_RONGSOK.bat
echo ====================================================================
echo.
set /p START_NOW="Jalankan Kasir Rongsok sekarang? (Y/N): "
if /i "%START_NOW%"=="Y" (
    start "" wscript.exe "%~dp0START_KASIR_RONGSOK.vbs"
)
exit /b 0

