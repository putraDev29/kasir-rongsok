@echo off
title Kasir Rongsok Backend (Port 8000 - LAN/Tablet Ready)
cd /d "%~dp0"

where php >nul 2>&1
if %errorLevel% neq 0 (
    if exist "C:\xampp\php\php.exe" (
        set "PATH=C:\xampp\php;%PATH%"
    ) else if exist "D:\xampp\php\php.exe" (
        set "PATH=D:\xampp\php;%PATH%"
    ) else if exist "E:\xampp\php\php.exe" (
        set "PATH=E:\xampp\php;%PATH%"
    )
)

php artisan serve --host=0.0.0.0 --port=8000
pause

