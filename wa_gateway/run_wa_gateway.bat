@echo off
title Kasir Rongsok WhatsApp Gateway (Port 21465)
color 0a
cd /d "%~dp0"

echo ================================================================
echo           KASIR RONGSOK - WHATSAPP GATEWAY SERVICE
echo ================================================================
echo   Layanan Pengiriman Nota Otomatis ^& Notifikasi WhatsApp
echo   Port: 21465 (Local Service)
echo ================================================================
echo.

where node >nul 2>&1
if %errorLevel% neq 0 (
    if exist "C:\Program Files\nodejs\node.exe" (
        set "PATH=C:\Program Files\nodejs;%PATH%"
    ) else if exist "C:\Program Files (x86)\nodejs\node.exe" (
        set "PATH=C:\Program Files (x86)\nodejs;%PATH%"
    ) else if exist "%LOCALAPPDATA%\Programs\node\node.exe" (
        set "PATH=%LOCALAPPDATA%\Programs\node;%PATH%"
    ) else if exist "%APPDATA%\npm\node.exe" (
        set "PATH=%APPDATA%\npm;%PATH%"
    ) else if exist "D:\Program Files\nodejs\node.exe" (
        set "PATH=D:\Program Files\nodejs;%PATH%"
    )
)

where node >nul 2>&1
if %errorLevel% neq 0 (
    echo [ERROR] Node.js tidak ditemukan di sistem!
    echo Silakan install Node.js dari https://nodejs.org/ untuk mengaktifkan WhatsApp Gateway.
    echo.
    pause
    exit /b 1
)

set PUPPETEER_SKIP_DOWNLOAD=true

if not exist "node_modules" (
    echo [SETUP] Menginstal dependensi WhatsApp Gateway pertama kali...
    call npm install
)

:: Bersihkan proses lama di port 21465 jika ada (mencegah bentrok EADDRINUSE)
for /f "tokens=5" %%a in ('netstat -aon ^| findstr :21465 ^| findstr LISTENING') do (
    taskkill /F /PID %%a >nul 2>&1
)

echo [*] Menjalankan WhatsApp Gateway di http://localhost:21465 ...
echo [*] Jangan tutup jendela ini agar pengiriman WhatsApp tetap berfungsi.
echo ================================================================
echo.
node server.js
if %errorLevel% neq 0 (
    echo.
    echo [PERINGATAN] Layanan WhatsApp Gateway terhenti.
    pause
)
