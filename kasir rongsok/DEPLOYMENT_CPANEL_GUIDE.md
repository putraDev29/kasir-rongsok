# PANDUAN LENGKAP HOSTING LARAVEL DI CPANEL
### Aplikasi Kasir Rongsok (Scrap Buying & Inventory Management System)

Panduan langkah demi langkah untuk mengunggah dan mengaktifkan aplikasi di cPanel hosting (Shared Hosting / Cloud Hosting cPanel).

---

## 1. Yang Harus Disiapkan di cPanel

1. **Akun cPanel Aktif:**
   - URL login cPanel (misal: `https://namadomain.com:2083` atau dari member area penyedia hosting).
   - Username & Password cPanel.
2. **Domain atau Subdomain:**
   - Bisa menggunakan domain utama (contoh: `rongsokmakmur.com`) atau subdomain (contoh: `kasir.rongsokmakmur.com`).
3. **Versi PHP Minimal PHP 8.2:**
   - Masuk ke menu **Select PHP Version** atau **MultiPHP Manager** di cPanel.
   - Pilih **PHP 8.2** atau **PHP 8.3**.
   - Pastikan ekstensi berikut aktif: `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `fileinfo`.
4. **SSL / HTTPS (Gratis dari cPanel):**
   - Pastikan fitur **AutoSSL / Let's Encrypt** di cPanel aktif agar domain berstatus `https://`.

---

## 2. Persiapan File Lokal Sebelum Upload

Saya telah menyiapkan dan menguji aplikasi ini secara lokal sehingga Anda tidak perlu menjalankan build atau kompilasi lagi:
- ✅ **Aset Frontend Siap:** `npm run build` sudah dijalankan dan file CSS/JS ada di dalam folder `public/build/`.
- ✅ **Database Backup Siap:** File `database_backup.sql` sudah digenerate di root proyek.
- ✅ **Folder Vendor Siap:** Dependensi backend sudah terpasang.

### Cara Mengompresi Proyek Menjadi ZIP:
Kompres seluruh folder proyek ini menjadi satu file ZIP (misal: `kasir_rongsok.zip`), **tetapi KECUALIKAN** folder berikut agar ukuran file tidak terlalu besar:
- ❌ **JANGAN SERTAKAN:** folder `node_modules/` (tidak dipakai di hosting karena asset sudah di-build).
- ❌ **JANGAN SERTAKAN:** folder `.git/` (jika ada).
- ✅ **WAJIB DISERTAKAN:** folder `app`, `bootstrap`, `config`, `database`, `public`, `resources`, `routes`, `storage`, `vendor`, `.env.example`, `artisan`, `composer.json`.

### Struktur Alternatif (Hostinger / Subfolder `public_html/kasir-rongsok/`):
Jika Anda menggunakan struktur di mana folder inti Laravel berada di dalam subfolder seperti:
```text
public_html/kasir-rongsok/              <-- Root web (DocumentRoot)
├── build/                              <-- Aset Vite JS & CSS
├── uploads/                            <-- Direktori upload logo & berkas
│   └── logo/
├── .htaccess
├── favicon.ico
├── index.php                           <-- Entry point web server
├── robots.txt
└── kasir rongsok/                      <-- Inti kode Laravel (base_path)
    ├── app/
    ├── bootstrap/                      <-- Telah otomatis mendeteksi public_path web root
    ├── config/
    ├── storage/
    ├── vendor/
    └── .env
```
Pada struktur ini:
- `bootstrap/app.php` sudah otomatis mendeteksi folder web root (`public_html/kasir-rongsok/`) via `$_SERVER['SCRIPT_FILENAME']` sehingga semua upload logo langsung masuk ke folder `public_html/kasir-rongsok/uploads/logo/`.
- Pastikan folder `uploads` dan `uploads/logo` memiliki hak akses tulis (Permission **755** atau **775**).
- Anda juga bisa menambahkan baris `PUBLIC_PATH=../` di file `.env` jika diperlukan.

---

## 3. Langkah Upload & Struktur Folder di cPanel (Metode Aman)

Untuk alasan keamanan, file inti Laravel **TIDAK BOLEH** ditaruh langsung di dalam `public_html` agar file sensitif seperti `.env` tidak bisa diakses orang lain lewat browser.

### Struktur yang Direkomendasikan:
```text
/home/username/
├── kasir_core/             <-- Letakkan seluruh file Laravel di sini (di luar public_html)
│   ├── app/
│   ├── bootstrap/
│   ├── config/
│   ├── storage/
│   ├── vendor/
│   ├── .env
│   └── ...
│
└── public_html/            <-- Isi dari folder "public" Laravel ditaruh di sini
    ├── build/
    ├── index.php
    ├── .htaccess
    └── robots.txt
```

### Langkah Detailnya:
1. Buka **File Manager** di cPanel.
2. Buat folder baru di direktori utama (`/home/username/`) bernama: `kasir_core`.
3. Masuk ke folder `kasir_core`, lalu **Upload** file `kasir_rongsok.zip`.
4. Klik kanan file ZIP tersebut -> pilih **Extract**.
5. Buka folder `kasir_core/public/`:
   - Pilih semua file & folder di dalamnya (`build`, `index.php`, `.htaccess`, `robots.txt`, favicon, dll).
   - Klik tombol **Move (Pindahkan)** ke folder: `public_html/` (atau `public_html/kasir/` jika menggunakan subdomain).
6. Edit file `public_html/index.php`:
   - Buka `public_html/index.php` -> klik **Edit**.
   - Sesuaikan 2 baris path ini:
     ```php
     // SEBELUMNYA:
     require __DIR__.'/../vendor/autoload.php';
     $app = require_once __DIR__.'/../bootstrap/app.php';

     // UBAH MENJADI:
     require __DIR__.'/../kasir_core/vendor/autoload.php';
     $app = require_once __DIR__.'/../kasir_core/bootstrap/app.php';
     ```
   - Klik **Save Changes**.

---

## 4. Setup Database MySQL di cPanel

1. Di cPanel, buka menu **MySQL Databases**:
   - Buat database baru, contoh: `username_kasir`.
   - Buat user database baru, contoh: `username_admin` dengan password yang kuat.
   - Di bagian **Add User to Database**, hubungkan user tadi ke database dan centang **ALL PRIVILEGES**.
2. Masuk ke menu **phpMyAdmin** di cPanel:
   - Klik nama database `username_kasir` di panel kiri.
   - Klik tab **Import** di bagian atas.
   - Klik **Choose File**, pilih file `database_backup.sql` dari komputer Anda.
   - Gulir ke bawah dan klik **Import / Go**.
   - Database lengkap beserta data awal (16 master barang, data akun, pemasok, transaksi contoh) langsung terisi.

---

## 5. Konfigurasi File `.env` di cPanel

1. Di File Manager cPanel, masuk ke folder `kasir_core`.
2. Jika file `.env` tidak terlihat, klik tombol **Settings** di pojok kanan atas File Manager -> centang **Show Hidden Files (dotfiles)** -> Save.
3. Edit file `.env` dan sesuaikan nilainya:
   ```env
   APP_NAME="Kasir Rongsok"
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://namadomainanda.com

   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=username_kasir
   DB_USERNAME=username_admin
   DB_PASSWORD=password_database_anda
   DB_COLLATION=utf8mb4_unicode_ci
   ```
4. Simpan file `.env`.

---

## 6. Hak Akses Folder (Permissions)

Pastikan web server dapat menulis ke folder storage dan cache:
- Masuk ke `kasir_core/`
- Pastikan permission folder `storage` dan subfoldernya adalah **775** atau **755**.
- Pastikan permission folder `bootstrap/cache` adalah **775** atau **755**.

---

## 7. Login Pertama Kali

Buka domain Anda di browser: `https://namadomainanda.com/login`

Gunakan akun awal berikut:
- **Super Admin (Owner):**
  - Email: `owner@kasirrongsok.com`
  - Password: `password123`
- **Admin:**
  - Email: `admin@kasirrongsok.com`
  - Password: `password123`
- **Kasir:**
  - Email: `kasir1@kasirrongsok.com`
  - Password: `password123`

*(Setelah berhasil login, Anda dapat langsung mengubah password akun di menu **Kelola Pengguna** atau memperbarui profil usaha di menu **Pengaturan Usaha**).*

