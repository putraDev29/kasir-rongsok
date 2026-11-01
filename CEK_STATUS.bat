@echo off
title Status Kasir Rongsok System

echo ================================================================
echo           STATUS LAYANAN KASIR RONGSOK SYSTEM
echo ================================================================
echo.

powershell -NoProfile -ExecutionPolicy Bypass -Command "$app = Get-NetTCPConnection -State Listen -LocalPort 8000 -ErrorAction SilentlyContinue; if ($app) { Write-Host '[V] Aplikasi Kasir Rongsok (Port 8000) : AKTIF' -ForegroundColor Green } else { Write-Host '[X] Aplikasi Kasir Rongsok (Port 8000) : MATI / BELUM JALAN' -ForegroundColor Red }; $wa = Get-NetTCPConnection -State Listen -LocalPort 21465 -ErrorAction SilentlyContinue; if ($wa) { Write-Host '[V] WhatsApp Gateway (Port 21465)      : AKTIF' -ForegroundColor Green } else { Write-Host '[X] WhatsApp Gateway (Port 21465)      : MATI / BELUM JALAN' -ForegroundColor Red }; $db = Get-NetTCPConnection -State Listen -LocalPort 3306 -ErrorAction SilentlyContinue; if ($db) { Write-Host '[V] MySQL Database (Port 3306)         : AKTIF' -ForegroundColor Green } else { Write-Host '[X] MySQL Database (Port 3306)         : MATI (Nyalakan MySQL di XAMPP)' -ForegroundColor Yellow }; $lanIp = (ipconfig | Select-String 'IPv4' | ForEach-Object { ($_ -split ':')[1].Trim() } | Select-Object -First 1); $pc = $env:COMPUTERNAME; Write-Host ''; Write-Host '--- ALAMAT AKSES DARI TABLET / HP ---' -ForegroundColor Cyan; if ($lanIp) { Write-Host ('  * Via IP Lokal : http://' + $lanIp + ':8000') -ForegroundColor Yellow }; if ($pc) { Write-Host ('  * Via Hostname : http://' + $pc.ToLower() + '.local:8000') -ForegroundColor Yellow };"

echo.
echo ================================================================
pause

