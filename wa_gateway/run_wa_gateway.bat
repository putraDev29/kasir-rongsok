@echo off
title Kasir Rongsok WhatsApp Gateway
cd /d "%~dp0"

:: Cari node di System PATH atau folder umum Windows
where node >nul 2>&1
if %errorLevel% neq 0 (
    if exist "C:\Program Files\nodejs\node.exe" (
        set "PATH=C:\Program Files\nodejs;%PATH%"
    ) else if exist "C:\Program Files (x86)\nodejs\node.exe" (
        set "PATH=C:\Program Files (x86)\nodejs;%PATH%"
    ) else if exist "%LOCALAPPDATA%\Programs\node\node.exe" (
        set "PATH=%LOCALAPPDATA%\Programs\node;%PATH%"
    ) else if exist "%APPDATA%\nvm\current\node.exe" (
        set "PATH=%APPDATA%\nvm\current;%PATH%"
    ) else if exist "C:\nodejs\node.exe" (
        set "PATH=C:\nodejs;%PATH%"
    ) else if exist "D:\nodejs\node.exe" (
        set "PATH=D:\nodejs;%PATH%"
    )
)

set PUPPETEER_SKIP_DOWNLOAD=true

:: Pastikan modul utama wppconnect terinstal jika node_modules belum ada atau belum lengkap
if not exist "node_modules\@wppconnect-team" (
    echo Menginstal dependensi WhatsApp Gateway pertama kali...
    call npm install
)

echo Menjalankan WhatsApp Gateway Kasir Rongsok di port 21465...
node server.js
pause
