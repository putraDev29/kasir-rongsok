@echo off
setlocal enabledelayedexpansion
title Setup Akses Tablet - Kasir Rongsok

:: Memeriksa hak Administrator
net session >nul 2>&1
if %errorLevel% neq 0 (
    echo Meminta izin Administrator untuk mengatur Windows Firewall...
    powershell -NoProfile -Command "Start-Process cmd.exe -ArgumentList '/c \"\"%~f0\"\"' -Verb RunAs"
    exit /b
)

cls
echo ================================================================
echo          PENGATURAN AKSES TABLET - KASIR RONGSOK
echo ================================================================
echo.
echo [1/2] Membuka Port 8000 (Aplikasi Web Kasir Rongsok)...
netsh advfirewall firewall delete rule name="Kasir Rongsok Port 8000" >nul 2>&1
netsh advfirewall firewall add rule name="Kasir Rongsok Port 8000" dir=in action=allow protocol=TCP localport=8000 profile=any >nul
if %errorLevel% equ 0 (
    echo       [OK] Port 8000 berhasil diizinkan di Windows Firewall.
) else (
    echo       [!] Gagal menambahkan rule Port 8000.
)

echo.
echo [2/2] Membuka Port 21465 (WhatsApp Gateway)...
netsh advfirewall firewall delete rule name="Kasir Rongsok WA Gateway" >nul 2>&1
netsh advfirewall firewall add rule name="Kasir Rongsok WA Gateway" dir=in action=allow protocol=TCP localport=21465 profile=any >nul
if %errorLevel% equ 0 (
    echo       [OK] Port 21465 berhasil diizinkan di Windows Firewall.
) else (
    echo       [!] Gagal menambahkan rule Port 21465.
)

:: Deteksi IP aktif Wi-Fi / LAN
for /f "tokens=2 delims=:" %%a in ('ipconfig ^| findstr /c:"IPv4 Address" /c:"Alamat IPv4"') do (
    set "RAW_IP=%%a"
    set "LAN_IP=!RAW_IP: =!"
    goto :ip_found
)
:ip_found

echo.
echo ================================================================
echo  SUKSES! Windows Firewall sudah terbuka untuk Tablet.
echo.
echo  PETUNJUK AKSES DI TABLET:
echo  1. Pastikan Tablet dan PC terhubung ke Wi-Fi yang SAMA.
echo  2. Buka Google Chrome di Tablet, lalu ketik alamat ini:
if defined LAN_IP (
    echo        http://%LAN_IP%:8000
) else (
    echo        http://192.168.100.62:8000
)
echo.
echo  Catatan: Jangan gunakan "dti-sewa5-s-nb.local" karena browser
echo  Android secara default tidak mendukung domain .local (mDNS).
echo.
echo  3. Untuk Pasang sebagai Aplikasi (PWA) di Tablet:
echo     - Buka alamat di atas di Chrome Tablet
echo     - Klik menu titik tiga (:) di pojok kanan atas Chrome
echo     - Pilih "Instal aplikasi" (atau "Tambahkan ke Layar Utama")
echo ================================================================
echo.
pause

