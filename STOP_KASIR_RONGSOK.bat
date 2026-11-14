@echo off
title Hentikan Kasir Rongsok System
color 0c

echo ================================================================
echo           MENGHENTIKAN KASIR RONGSOK SYSTEM
echo ================================================================
echo.

echo [1/3] Menghentikan Aplikasi Kasir Rongsok (Port 8000)...
for /f "tokens=5" %%a in ('netstat -aon ^| findstr :8000 ^| findstr LISTENING') do taskkill /F /PID %%a >nul 2>&1

echo [2/3] Menghentikan WhatsApp Gateway (Port 21465)...
for /f "tokens=5" %%a in ('netstat -aon ^| findstr :21465 ^| findstr LISTENING') do taskkill /F /PID %%a >nul 2>&1

echo [3/3] Membersihkan proses browser WhatsApp di latar belakang...
powershell -NoProfile -ExecutionPolicy Bypass -Command "Get-CimInstance Win32_Process -Filter \"Name = 'chrome.exe' or Name = 'msedge.exe'\" -ErrorAction SilentlyContinue | Where-Object { $_.CommandLine -like '*kasir-rongsok*' } | ForEach-Object { Stop-Process -Id $_.ProcessId -Force -ErrorAction SilentlyContinue }" >nul 2>&1

:: Tutup window CMD terkait Kasir Rongsok jika ada yang tertinggal
taskkill /F /FI "WINDOWTITLE eq Kasir Rongsok*" >nul 2>&1

echo.
echo ================================================================
echo   Semua layanan Kasir Rongsok berhasil dihentikan!
echo ================================================================
ping 127.0.0.1 -n 2 >nul
exit
