@echo off
title Kasir Rongsok Backend (Port 8000 - LAN/Tablet Ready)
cd /d "%~dp0"

:: 1. Deteksi path PHP dari PATH atau instalasi XAMPP
where php >nul 2>&1
if %errorLevel% neq 0 (
    if exist "C:\xampp\php\php.exe" (
        set "PATH=C:\xampp\php;%PATH%"
    ) else if exist "D:\xampp\php\php.exe" (
        set "PATH=D:\xampp\php;%PATH%"
    ) else if exist "E:\xampp\php\php.exe" (
        set "PATH=E:\xampp\php;%PATH%"
    ) else if exist "F:\xampp\php\php.exe" (
        set "PATH=F:\xampp\php;%PATH%"
    ) else if exist "C:\php\php.exe" (
        set "PATH=C:\php;%PATH%"
    ) else if exist "D:\php\php.exe" (
        set "PATH=D:\php;%PATH%"
    )
)

:: 2. Pastikan file konfigurasi .env ada
if not exist ".env" (
    if exist ".env.example" (
        copy ".env.example" ".env" >nul
        call php artisan key:generate --force >nul 2>&1
    )
)

:: 3. Pastikan database MySQL, migrasi, data default, dan icon logo siap otomatis
call php artisan app:ensure-system-ready

:: 4. Jalankan Server Laravel Backend
php artisan serve --host=0.0.0.0 --port=8000
pause

