# APLIKASI KASIR RONGSOK & WHATSAPP GATEWAY

Sistem Manajemen POS Kasir Timbangan Pengepul & Depo Rongsokan terintegrasi dengan WPPConnect WhatsApp Gateway Lokal Mandiri (seperti arsitektur Dental Care).

---

## 📂 Struktur Direktori

```
app rongsok/
├── kasir rongsok/           # Aplikasi Web Utama (Laravel 11, Tailwind, Alpine.js)
├── wa_gateway/              # Server WhatsApp Gateway Lokal (Node.js, WPPConnect, Port 21465)
├── START_KASIR_RONGSOK.bat  # Launcher satu-klik (Silent, auto start MySQL, App, & WA Gateway)
├── START_KASIR_RONGSOK.vbs  # Script VBS launcher & sinkronisasi shortcut Desktop
├── start_services.ps1       # Script PowerShell launcher dengan indikator warna
├── STOP_KASIR_RONGSOK.bat   # Script penghenti seluruh layanan latar belakang
├── stop_services.ps1        # Script PowerShell penghenti proses port 8000 & 21465
├── CEK_STATUS.bat           # Script pengecek status port MySQL (3306), Kasir (8000), & WA (21465)
└── setup_shortcuts.ps1      # Script pembuatan pintasan Desktop otomatis
```

---

## 🚀 Panduan Menjalankan Sistem

1. **Jalankan Aplikasi & WA Gateway (Satu Klik):**
   Klik ganda pada file `START_KASIR_RONGSOK.bat`.
   - Otomatis memastikan database MySQL aktif di port 3306.
   - Menjalankan backend Kasir Rongsok di port 8000 (`http://localhost:8000`).
   - Menjalankan WhatsApp Gateway di port 21465 (`http://127.0.0.1:21465`).
   - Membuka browser Google Chrome ke `http://localhost:8000`.

2. **Periksa Status Layanan:**
   Klik ganda pada file `CEK_STATUS.bat`.
   Konsol akan menampilkan status apakah MySQL, Kasir Rongsok, dan WA Gateway aktif [V] atau belum aktif [X].

3. **Matikan Semua Layanan:**
   Klik ganda pada file `STOP_KASIR_RONGSOK.bat`.

---

## 📱 Fitur WhatsApp Gateway

- **Header Status Indicator:**
  - 🟢 **WA Terhubung**: Akun resmi WhatsApp depo sedang aktif dan siap mengirim pesan.
  - 🟡 **Scan QR WA**: Gateway meminta pemindaian QR code melalui WhatsApp HP kasir/depo.
  - 🔴 **WA Gateway Offline**: Layanan di port 21465 belum berjalan.
- **Kirim Nota Timbang via WhatsApp:**
  - Pada halaman detail nota maupun tabel riwayat penerimaan, klik tombol **"Kirim Nota WA"**.
  - Rincian penimbangan berat kotor, tara/potongan, berat bersih, harga/kg, total nilai barang, potongan kasbon/hutang, dan tunai dibayar tersusun rapi dengan emoji dan teks disclaimer hukum.
  - Opsi kirim langsung via server Gateway atau buka langsung di WhatsApp Web / App (`wa.me`).
- **Kirim Faktur Penjualan Partai:**
  - Kirim rincian pengiriman armada truk rongsok ke pembeli/pabrik.
- **Kirim Bukti Pembayaran Kasbon:**
  - Kirim konfirmasi pembayaran pinjaman modal/kasbon ke nomor pemasok.
- **Panel Manajemen Gateway:**
  - Scan QR Code langsung di modal aplikasi.
  - Uji coba kirim pesan instan.
  - Reset antrian obrolan per nomor.
  - Logout dan Reconnect kapan saja.

