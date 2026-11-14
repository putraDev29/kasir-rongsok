@echo off
title Memulai Kasir Rongsok System...
cd /d "%~dp0"

:: 0. Deteksi apakah laptop baru (belum setup .env atau vendor)
if not exist "kasir rongsok\.env" (
    echo ====================================================================
    echo   [SETUP PERDANA] Menyiapkan Kasir Rongsok pertama kali di laptop ini...
    echo ====================================================================
    call "%~dp0SETUP_PC_BARU.bat"
    exit /b
)
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
