# APLIKASI KASIR RONGSOK & WHATSAPP GATEWAY

Sistem Manajemen POS Kasir Timbangan Pengepul & Depo Rongsokan terintegrasi dengan WPPConnect WhatsApp Gateway Lokal Mandiri (seperti arsitektur Dental Care).

---

## 📂 Struktur Direktori

```
app rongsok/
├── kasir rongsok/           # Aplikasi Web Kasir & POS Backend (Laravel 11, MySQL, API)
├── landing-page/            # Modern Responsive Landing Page (Next.js 16, Tailwind CSS, Framer Motion)
├── wa_gateway/              # Server WhatsApp Gateway Lokal (Node.js, WPPConnect, Port 21465)
├── SETUP_PC_BARU.bat        # Installer 1-Klik Otomatis untuk PC Baru / Client
├── START_KASIR_RONGSOK.bat  # Launcher satu-klik (Silent, auto start MySQL, App, & WA Gateway)
├── START_KASIR_RONGSOK.vbs  # Script VBS launcher & sinkronisasi shortcut Desktop
├── start_services.ps1       # Script PowerShell launcher dengan indikator warna
├── STOP_KASIR_RONGSOK.bat   # Script penghenti seluruh layanan latar belakang
├── stop_services.ps1        # Script PowerShell penghenti proses port 8000 & 21465
├── CEK_STATUS.bat           # Script pengecek status port MySQL (3306), Kasir (8000), & WA (21465)
└── setup_shortcuts.ps1      # Script pembuatan pintasan Desktop otomatis
```

---

## 💻 Panduan Instalasi di PC Client / PC Baru (Dari GitHub)

### A. Prasyarat Software di PC Client (Instal 1 Kali Saja)
Sebelum menjalankan proyek, pastikan software berikut sudah terinstal di PC Client:
1. **XAMPP** (Versi dengan PHP 8.2 atau 8.3): [Download XAMPP](https://www.apachefriends.org)
2. **Node.js** (Versi LTS 18, 20, atau 22): [Download Node.js](https://nodejs.org)
3. **Composer**: [Download Composer](https://getcomposer.org)
4. **Git**: [Download Git](https://git-scm.com) (atau bisa langsung Download ZIP dari GitHub)
5. **Google Chrome** (sebagai browser utama kasir)

---

### B. Langkah Instalasi Otomatis (Cukup 1-Klik via `SETUP_PC_BARU.bat`)
1. Di PC Client, **Clone** repository atau **Ekstrak ZIP** hasil unduhan dari GitHub ke direktori lokal (misal: `C:\project\app rongsok` atau `D:\kasir-rongsok`).
2. Buka **XAMPP Control Panel**, klik tombol **Start** pada **Apache** dan **MySQL**.
3. Klik kanan file:
   👉 **`SETUP_PC_BARU.bat`** $\rightarrow$ Pilih **"Run as administrator"**.
4. Script akan secara otomatis:
   - Membuat database MySQL `kasir_rongsok`.
   - Mengonfigurasi file `.env` Laravel.
   - Mengunduh paket Composer (`composer install`).
   - Membuat APP_KEY Laravel.
   - Menjalankan migrasi tabel dan mengisi data master awal (`migrate --seed`).
   - Melakukan kompilasi aset frontend Tailwind (`npm run build`).
   - Memasang modul WhatsApp Gateway (`npm install`).
   - Mengonfigurasi Windows Firewall untuk Port 8000 dan 21465.
   - Membuat pintasan Desktop **Kasir Rongsok** lengkap dengan ikon logo.
5. Selesai! Aplikasi langsung siap digunakan.

---

### C. Akun Login Default
Setelah database berhasil di-seed, gunakan salah satu akun berikut:
- **Owner / Super Admin** *(Akses Semua Modul)*:
  - Email: `owner@kasirrongsok.com`
  - Password: `password123`
- **Admin Kantor** *(Akses Kasir Timbang, Riwayat, Penerimaan, Dashboard)*:
  - Email: `admin@kasirrongsok.com`
  - Password: `password123`
- **Kasir Timbangan**:
  - Email: `kasir1@kasirrongsok.com`
  - Password: `password123`

---

## 🚀 Penggunaan Sehari-hari

1. **Menjalankan Sistem:**
   Cukup klik ganda pada ikon pintasan Desktop **Kasir Rongsok** atau file `START_KASIR_RONGSOK.bat`.
   - Otomatis memastikan database MySQL aktif di port 3306.
   - Menjalankan backend Kasir Rongsok di port 8000 (`0.0.0.0:8000` - siap LAN/Tablet).
   - Menjalankan WhatsApp Gateway di port 21465.
   - Membuka browser Google Chrome ke `http://localhost:8000`.

2. **Akses dari Tablet / HP:**
   - Sambungkan Tablet/HP ke Wi-Fi yang sama dengan PC.
   - Buka Google Chrome di Tablet/HP, buka alamat IP PC:
     `http://[IP-PC-ANDA]:8000` (contoh: `http://192.168.100.62:8000`).
   - Klik titik tiga (⋮) $\rightarrow$ **"Buat pintasan"** untuk menaruh ikon di layar depan HP/Tablet.

3. **Mencetak Nota Timbangan ke Printer Thermal (58mm / 80mm):**
   - **PC**: Colok kabel USB printer, pasang driver POS-58 / POS-80.
   - **Tablet/HP**: Sambungkan Bluetooth printer, instal aplikasi gratis **RawBT Print Service** di Android Play Store.
   - Pada halaman cetak, format **Thermal 58mm** sudah diatur pas 48mm area cetak (tanpa teks terpotong).

4. **Mematikan Semua Layanan:**
   Klik ganda pada file `STOP_KASIR_RONGSOK.bat`.
