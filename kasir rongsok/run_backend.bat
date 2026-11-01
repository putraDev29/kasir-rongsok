@echo off
title Kasir Rongsok Backend (Port 8000 - LAN/Tablet Ready)
cd /d "%~dp0"
php artisan serve --host=0.0.0.0 --port=8000
pause

