@echo off
title Memulai Kasir Rongsok System...
cd /d "%~dp0"

:: 0. Pastikan file konfigurasi .env ada jika folder di-copy tanpa .env
if not exist "kasir rongsok\.env" (
    if exist "kasir rongsok\.env.example" (
        copy "kasir rongsok\.env.example" "kasir rongsok\.env" >nul
    )
)

:: Jika folder vendor belum ada sama sekali, jalankan setup perdana
if not exist "kasir rongsok\vendor\autoload.php" (
    echo ====================================================================
    echo   [SETUP PERDANA] Menyiapkan dependensi Kasir Rongsok...
    echo ====================================================================
    call "%~dp0SETUP_PC_BARU.bat"
    exit /b
)

:: 1. Otomatis Buka Port Firewall untuk Tablet & HP (Port 8000 & 21465)
netsh advfirewall firewall add rule name="Kasir Rongsok Port 8000" dir=in action=allow protocol=TCP localport=8000 profile=any >nul 2>&1
netsh advfirewall firewall add rule name="Kasir Rongsok WA Gateway" dir=in action=allow protocol=TCP localport=21465 profile=any >nul 2>&1

:: 2. Jalankan Silent Launcher VBScript
start "" wscript.exe "%~dp0START_KASIR_RONGSOK.vbs"
exit
