@echo off
title Kasir Rongsok WhatsApp Gateway
cd /d "%~dp0"

where node >nul 2>&1
if %errorLevel% neq 0 (
    if exist "C:\Program Files\nodejs\node.exe" (
        set "PATH=C:\Program Files\nodejs;%PATH%"
    )
)

set PUPPETEER_SKIP_DOWNLOAD=true

if not exist "node_modules" (
    echo Menginstal dependensi WhatsApp Gateway pertama kali...
    call npm install
)

echo Menjalankan WhatsApp Gateway Kasir Rongsok di port 21465...
node server.js
pause
