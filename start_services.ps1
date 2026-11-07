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

# 2. Jalankan Backend Kasir Rongsok (Port 8000 - Bind 0.0.0.0 agar bisa diakses Tablet/HP)
$bePort = Get-NetTCPConnection -State Listen -LocalPort 8000 -ErrorAction SilentlyContinue
if (-not $bePort) {
    Write-Host "[2/3] Menjalankan Aplikasi Kasir Rongsok di port 8000 (LAN/Tablet Ready)..." -ForegroundColor White
    Start-Process -FilePath "cmd.exe" -ArgumentList "/c php artisan serve --host=0.0.0.0 --port=8000" -WorkingDirectory $appDir -WindowStyle Minimized
} else {
    Write-Host "[2/3] Aplikasi Kasir Rongsok sudah berjalan di port 8000." -ForegroundColor Green
}

# 3. Jalankan WhatsApp Gateway (Port 21465)
$waPort = Get-NetTCPConnection -State Listen -LocalPort 21465 -ErrorAction SilentlyContinue
if (-not $waPort) {
    Write-Host "[3/4] Menjalankan WhatsApp Gateway di port 21465..." -ForegroundColor White
    Start-Process -FilePath "cmd.exe" -ArgumentList "/c node server.js" -WorkingDirectory $waDir -WindowStyle Minimized
} else {
    Write-Host "[3/4] WhatsApp Gateway sudah berjalan di port 21465." -ForegroundColor Green
}

# 4. Jalankan Landing Page Website (Port 3000)
$landingDir = Join-Path $baseDir "landing-page"
$landingPort = Get-NetTCPConnection -State Listen -LocalPort 3000 -ErrorAction SilentlyContinue
if (-not $landingPort -and (Test-Path $landingDir)) {
    Write-Host "[4/4] Menjalankan Landing Page Website di port 3000..." -ForegroundColor White
    Start-Process -FilePath "cmd.exe" -ArgumentList "/c npm start -- -p 3000" -WorkingDirectory $landingDir -WindowStyle Minimized
} else {
    Write-Host "[4/4] Landing Page Website sudah berjalan di port 3000." -ForegroundColor Green
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

# Ambil IP LAN PC untuk akses Tablet
$lanIp = (ipconfig | Select-String "IPv4" | ForEach-Object { ($_ -split ":")[1].Trim() } | Select-Object -First 1)

Write-Host ""
Write-Host "================================================================" -ForegroundColor Green
Write-Host "  SEMUA LAYANAN KASIR RONGSOK BERHASIL AKTIF!" -ForegroundColor Green
Write-Host "  - Akses PC Ini              : http://127.0.0.1:8000" -ForegroundColor Cyan
if ($lanIp) {
    Write-Host "  - Akses Tablet (Wajib Pakai): http://${lanIp}:8000" -ForegroundColor Yellow
    Write-Host "    (Gunakan Google Chrome di Tablet dengan Wi-Fi yang sama)" -ForegroundColor DarkGray
}
Write-Host "  - WA Gateway REST API       : http://127.0.0.1:21465" -ForegroundColor Cyan
Write-Host "  - Landing Page Website      : http://127.0.0.1:3000" -ForegroundColor Cyan
Write-Host "================================================================" -ForegroundColor Green
Write-Host ""
Write-Host "Membuka browser ke $targetUrl ..." -ForegroundColor Yellow
Start-Process $targetUrl

Start-Sleep -Seconds 3

