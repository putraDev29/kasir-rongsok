@echo off
title Kasir Rongsok Backend (Port 8000)
cd /d "%~dp0"
php artisan serve --host=127.0.0.1 --port=8000
pause

