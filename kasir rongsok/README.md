# APLIKASI KASIR RONGSOK
### *Scrap Buying & Inventory Management System*

Aplikasi web kasir profesional, modern, dan tangguh khusus untuk operasional usaha depo/pengepul barang rongsokan dan daur ulang logam, kertas, plastik, dan aki bekas.

---

## 🚀 Fitur Utama

1. **Dashboard Bisnis Real-Time:**
   - Ringkasan omzet pembelian (kas keluar), timbangan bersih (kg & ton), estimasi nilai persediaan gudang, dan laba kotor.
   - Grafik tren pembelian harian (7 hari terakhir).
   - Indikator barang dengan stok menipis (*low stock alert*).
   - Filter periode: Hari Ini, Kemarin, Minggu Ini, Bulan Ini, Tahun Ini.

2. **Kasir Timbangan (Core Cashier / POS):**
   - Penimbangan multi-item cepat dan ergonomis dengan Alpine.js.
   - Input **Berat Kotor (kg)** dan **Potongan / Tara (kg)** (wadah karung, air, drum).
   - Kalkulasi otomatis: $\text{Berat Bersih} = \text{Berat Kotor} - \text{Potongan}$.
   - Snapshot harga beli master per item dan dukungan negosiasi harga.
   - Tombol shortcut keyboard: `[F2]` Tambah baris, `[Ctrl+Enter]` Selesaikan nota.
   - Filter cepat kategori barang & pencarian autocomplete.
   - Pilihan pemasok terdaftar atau pemasok umum tunai langsung.

3. **Cetak Nota Pembelian Multi-Format:**
   - **Thermal Printer 58mm** (mini POS receipt).
   - **Thermal Printer 80mm** (standard POS receipt).
   - **Kertas HVS A4** (surat penerimaan lengkap dengan tanda tangan).
   - CSS print khusus: menyembunyikan navigasi, sidebar, dan tombol saat dicetak.
   - Cetak ulang nota kapan saja dari riwayat transaksi.

4. **Manajemen Stok & Buku Mutasi (Stock Movement):**
   - Pemotongan & penambahan stok otomatis menggunakan database transaction.
   - Pencatatan kartu stok lengkap (*Stock Movement Ledger*): `purchase`, `sale`, `adjustment`, `reversal`.
   - Fitur Stock Opname / Penyesuaian fisik akhir bulan dengan alasan audit.

5. **Penjualan Partai Besar ke Pabrik (Wholesale Sales):**
   - Penjualan rongsok ke pabrik peleburan / pengepul besar.
   - Pencegahan stok minus secara ketat di backend.
   - Kalkulasi otomatis HPP berbasis *moving average buy price* dan estimasi laba kotor.
   - Cetak Surat Jalan / Invoice pengiriman armada truk.

6. **Biaya Operasional:**
   - Pencatatan BBM armada, upah kuli bongkar muat, konsumsi kasir, dan perawatan timbangan.

7. **Laporan & Export:**
   - Laporan Pembelian Barang.
   - Laporan Penjualan Barang.
   - Laporan Posisi Stok & Valuasi Persediaan.
   - Laporan Beban Operasional.
   - Laporan Laba / Rugi Usaha (P&L: Omzet - HPP - Biaya Operasional = Laba Bersih).
   - Laporan Kinerja Transaksi per Kasir.
   - Export CSV / Excel (kompatibel Excel dengan UTF-8 BOM) & Cetak Print/PDF.

8. **Role-Based Access Control (RBAC):**
   - **Super Admin (Owner):** Akses penuh ke seluruh menu, kelola user, pengaturan usaha.
   - **Admin:** Mengelola transaksi pembelian, penjualan, master barang & harga, biaya operasional, stok opname, dan laporan.
   - **Kasir:** Fokus pada penerimaan barang dan cetak nota. Dibatasi dari perubahan master harga dan laporan keuangan sensitif.

9. **Integrasi WhatsApp Gateway Otomatis (WPPConnect):**
   - Panel WhatsApp Gateway terintegrasi langsung di navbar atas dengan indikator status real-time (🟢 Terhubung, 🟡 Menunggu Scan QR, 🔴 Offline).
   - Scan QR code langsung dari browser untuk menautkan nomor WhatsApp resmi depo.
   - Fitur **Kirim Nota WA** di riwayat & detail nota pembelian dengan rincian timbangan otomatis (Bruto, Potongan/Tara, Netto kg, Harga/kg, Potongan Kasbon, dan Tunai).
   - Pengiriman faktur penjualan partai besar (wholesale) dan bukti bayar kasbon/modal.
   - Opsi pengiriman fleksibel: Otomatis via server WPPConnect background atau langsung via tautan WhatsApp (wa.me) sebagai fallback.
   - Fitur uji coba kirim pesan dan reset antrian obrolan langsung dari UI.

---

## 🔑 Akun Demo Pengguna

| Peran (Role) | Email | Kata Sandi | Halaman Utama |
|---|---|---|---|
| **Super Admin (Owner)** | `owner@kasirrongsok.com` | `password123` | Dashboard & Pengaturan Usaha |
| **Admin** | `admin@kasirrongsok.com` | `password123` | Dashboard & Seluruh Transaksi |
| **Kasir 1 (Timbang)** | `kasir1@kasirrongsok.com` | `password123` | POS Kasir Penerimaan |
| **Kasir 2 (Timbang)** | `kasir2@kasirrongsok.com` | `password123` | POS Kasir Penerimaan |

---

## 💻 Menjalankan Aplikasi

Tersedia launcher otomatis (seperti pada sistem Dental Care):
1. **Cara Cepat (Satu Klik):**
   - Jalankan `START_KASIR_RONGSOK.bat` (atau pintasan di Desktop) pada folder utama `app rongsok`.
   - Script akan otomatis mengecek MySQL, menyalakan WhatsApp Gateway di port 21465, menyalakan aplikasi di port 8000, dan membuka browser.
2. **Cek Status Layanan:**
   - Jalankan `CEK_STATUS.bat` untuk memverifikasi apakah MySQL (3306), Kasir Rongsok (8000), dan WA Gateway (21465) sudah aktif.
3. **Menghentikan Layanan:**
   - Jalankan `STOP_KASIR_RONGSOK.bat` untuk mematikan semua layanan latar belakang sekaligus.
4. **Manual:**
   - Jalankan WhatsApp Gateway:
     ```bash
     cd wa_gateway && node server.js
     ```
   - Jalankan Laravel:
     ```bash
     cd "kasir rongsok" && php artisan serve
     ```
   - Buka browser: `http://127.0.0.1:8000`
4. Masuk menggunakan salah satu akun demo di atas (atau klik tombol demo cepat pada halaman login).

---

## 🧪 Menjalankan Pengujian (Test Suite)

Untuk menjalankan test suite otomatis:
```bash
php artisan test
```
Semua feature test (kalkulasi timbangan, penambahan stok, penjualan, pencegahan stok minus, reversal pembatalan transaksi, dan hak akses peran) telah teruji.
