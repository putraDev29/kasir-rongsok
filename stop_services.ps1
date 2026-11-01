# Script Penghenti Layanan Kasir Rongsok System
$Host.UI.RawUI.WindowTitle = "Hentikan Kasir Rongsok System"
Write-Host "================================================================" -ForegroundColor Red
Write-Host "           MENGHENTIKAN KASIR RONGSOK SYSTEM" -ForegroundColor Red
Write-Host "================================================================" -ForegroundColor Red
Write-Host ""

function Kill-PortProcess($port, $name) {
    $conns = Get-NetTCPConnection -State Listen -LocalPort $port -ErrorAction SilentlyContinue
    if ($conns) {
        foreach ($c in $conns) {
            $pId = $c.OwningProcess
            if ($pId -gt 0) {
                Write-Host "Menghentikan $name (Port $port, PID $pId)..." -ForegroundColor Yellow
                Stop-Process -Id $pId -Force -ErrorAction SilentlyContinue
            }
        }
    } else {
        Write-Host "$name (Port $port) sudah tidak aktif." -ForegroundColor Gray
    }
}

Kill-PortProcess 8000 "Aplikasi Kasir Rongsok"
Kill-PortProcess 21465 "WhatsApp Gateway"

# Tutup window cmd terkait jika ada
Get-Process cmd -ErrorAction SilentlyContinue | Where-Object {
    $_.MainWindowTitle -like "*Kasir Rongsok*"
} | Stop-Process -Force -ErrorAction SilentlyContinue

Write-Host ""
Write-Host "================================================================" -ForegroundColor Green
Write-Host "  Semua layanan Kasir Rongsok telah dihentikan!" -ForegroundColor Green
Write-Host "================================================================" -ForegroundColor Green
Start-Sleep -Seconds 3

