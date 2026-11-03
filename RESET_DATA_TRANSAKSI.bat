@echo off
title Reset Data Transaksi Kasir Rongsok
cd /d "%~dp0"

echo ================================================================
echo           RESET & PEMBERSIHAN DATA TRANSAKSI KASIR
echo ================================================================
echo.
echo PERINGATAN:
echo Tindakan ini akan MENGHAPUS SEMUA DATA TRANSAKSI di database lokal:
echo   - Riwayat Pembelian / Kasir Timbang
echo   - Riwayat Penjualan Partai / Grosir
echo   - Surat Jalan Pengiriman
echo   - Hutang Piutang Modal
echo   - Pengeluaran Operasional & Slip Gaji
echo   - Kartu Stok & Mutasi Transaksi (Stok kembali ke 0)
echo.
echo DATA YANG TETAP TERSIMPAN:
echo   [V] Data User / Akun Login (Owner, Admin, Kasir)
echo   [V] Master Jenis Barang Rongsok & Harga Beli/Jual
echo   [V] Pengaturan Profil Usaha & Logo
echo   [V] Metode Pembayaran & Kategori
echo ================================================================
echo.

set /p CONFIRM="Ketik Y lalu tekan Enter untuk melanjutkan reset: "
if /i "%CONFIRM%" neq "Y" (
    echo.
    echo Reset dibatalkan. Tidak ada data yang diubah.
    pause
    exit /b 0
)

echo.
echo Sedang membersihkan data transaksi...
cd "kasir rongsok"

where php >nul 2>&1
if %errorLevel% neq 0 (
    if exist "C:\xampp\php\php.exe" (
        set "PATH=C:\xampp\php;%PATH%"
    ) else if exist "D:\xampp\php\php.exe" (
        set "PATH=D:\xampp\php;%PATH%"
    )
)

call php artisan app:reset-transactions --force

cd ..
echo.
pause
exit /b 0

