# Script Peluncur Kasir Rongsok System (Sistem Depo & POS)
$Host.UI.RawUI.WindowTitle = "Kasir Rongsok Launcher"
Write-Host "================================================================" -ForegroundColor Cyan
Write-Host "             KASIR RONGSOK MANAGEMENT SYSTEM" -ForegroundColor Cyan
Write-Host "================================================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Sedang memulai semua layanan..." -ForegroundColor Yellow

$baseDir = $PSScriptRoot
$appDir = Join-Path $baseDir "kasir rongsok"
$waDir = Join-Path $baseDir "wa_gateway"

# 1. Cek MySQL (Port 3306)
$mysqlPort = Get-NetTCPConnection -State Listen -LocalPort 3306 -ErrorAction SilentlyContinue
if (-not $mysqlPort) {
    $xamppBat = @("C:\xampp\mysql_start.bat", "D:\xampp\mysql_start.bat", "E:\xampp\mysql_start.bat") | Where-Object { Test-Path $_ } | Select-Object -First 1
    if ($xamppBat) {
        Write-Host "[1/3] Menjalankan MySQL Database di port 3306..." -ForegroundColor White
        Start-Process $xamppBat -WindowStyle Minimized
        Start-Sleep -Seconds 3
    } else {
        Write-Host "[!] Peringatan: MySQL XAMPP tidak ditemukan otomatis. Pastikan MySQL aktif." -ForegroundColor Yellow
    }
} else {
    Write-Host "[OK] Database MySQL aktif di port 3306." -ForegroundColor Green
}

# 2. Jalankan Backend Kasir Rongsok (Port 8000)
$bePort = Get-NetTCPConnection -State Listen -LocalPort 8000 -ErrorAction SilentlyContinue
if (-not $bePort) {
    Write-Host "[2/3] Menjalankan Aplikasi Kasir Rongsok di port 8000..." -ForegroundColor White
    Start-Process -FilePath "cmd.exe" -ArgumentList "/c php artisan serve --host=127.0.0.1 --port=8000" -WorkingDirectory $appDir -WindowStyle Minimized
} else {
    Write-Host "[2/3] Aplikasi Kasir Rongsok sudah berjalan di port 8000." -ForegroundColor Green
}

# 3. Jalankan WhatsApp Gateway (Port 21465)
$waPort = Get-NetTCPConnection -State Listen -LocalPort 21465 -ErrorAction SilentlyContinue
if (-not $waPort) {
    Write-Host "[3/3] Menjalankan WhatsApp Gateway di port 21465..." -ForegroundColor White
    Start-Process -FilePath "cmd.exe" -ArgumentList "/c node server.js" -WorkingDirectory $waDir -WindowStyle Minimized
} else {
    Write-Host "[3/3] WhatsApp Gateway sudah berjalan di port 21465." -ForegroundColor Green
}

# 4. Tunggu sampai server merespon (maks 15 detik)
Write-Host ""
Write-Host "Menunggu aplikasi siap melayani..." -ForegroundColor Yellow
$targetUrl = "http://127.0.0.1:8000"
$ready = $false
for ($i = 0; $i -lt 15; $i++) {
    Start-Sleep -Seconds 1
    if (Get-NetTCPConnection -State Listen -LocalPort 8000 -ErrorAction SilentlyContinue) {
        $ready = $true
        break
    }
}

Write-Host ""
Write-Host "================================================================" -ForegroundColor Green
Write-Host "  SEMUA LAYANAN BERHASIL AKTIF!" -ForegroundColor Green
Write-Host "  - Aplikasi Kasir Rongsok : $targetUrl" -ForegroundColor Cyan
Write-Host "  - WA Gateway REST API    : http://127.0.0.1:21465" -ForegroundColor Cyan
Write-Host "================================================================" -ForegroundColor Green
Write-Host ""
Write-Host "Membuka browser ke $targetUrl ..." -ForegroundColor Yellow
Start-Process $targetUrl

Start-Sleep -Seconds 3

