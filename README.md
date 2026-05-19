# 🎓 PMB Online — Sistem Penerimaan Mahasiswa Baru

![PHP](https://img.shields.io/badge/PHP-8.3-777BB4?style=flat-square&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8.4-4479A1?style=flat-square&logo=mysql&logoColor=white)
![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952B3?style=flat-square&logo=bootstrap&logoColor=white)
![License](https://img.shields.io/badge/License-MIT-green?style=flat-square)

Aplikasi web **Penerimaan Mahasiswa Baru (PMB) Online** berbasis PHP Native dan MySQL. Sistem ini memungkinkan calon mahasiswa mendaftar, mengupload berkas, dan melihat hasil seleksi secara online, serta memudahkan admin dalam mengelola dan memverifikasi data pendaftar.

---

## 📸 Tampilan Aplikasi

| Halaman | Deskripsi |
|--------|-----------|
| **Login** | Split-screen dengan ilustrasi kampus, form login modern |
| **Register** | Form registrasi dengan password strength meter & alur pendaftaran |
| **Dashboard Mahasiswa** | Ringkasan status pendaftaran & progress |
| **Dashboard Admin** | Statistik pendaftar, verifikasi berkas, pengumuman hasil |

---

## ✨ Fitur Utama

### 👨‍🎓 Mahasiswa
- Registrasi & login akun
- Mengisi biodata lengkap (NIK, NISN, asal sekolah, jurusan pilihan, dll.)
- Upload berkas pendukung (foto, ijazah, rapor, KTP)
- Melihat status verifikasi berkas secara real-time
- Melihat pengumuman hasil seleksi
- Formulir daftar ulang (khusus yang diterima) — pilihan kelas, data orang tua, fasilitas asrama

### 🛠️ Admin
- Dashboard statistik (total pendaftar, gender, status verifikasi, hasil seleksi)
- Data mahasiswa lengkap dengan filter, pencarian, dan export CSV
- Verifikasi berkas mahasiswa (terima / tolak + catatan)
- Set hasil seleksi per mahasiswa (Diterima / Tidak Diterima)
- Publikasi pengumuman hasil seleksi
- Verifikasi pembayaran daftar ulang

### 🎨 Tampilan
- Responsive design — mendukung mobile & desktop
- Hamburger menu di perangkat mobile
- Sidebar navigasi dengan highlight halaman aktif

---

## 🗂️ Struktur Proyek

```
pmb-online/
├── app/
│   ├── config/
│   │   └── database.php          # Konfigurasi koneksi database
│   ├── helpers/
│   │   └── auth.php              # Helper autentikasi & role guard
│   └── views/
│       └── layouts/
│           ├── header.php        # Layout header + sidebar (responsive)
│           └── footer.php        # Layout footer + Bootstrap JS
│
├── public/
│   ├── index.php                 # Landing page
│   ├── login.php                 # Halaman login
│   ├── register.php              # Halaman registrasi
│   ├── logout.php                # Proses logout
│   │
│   ├── mahasiswa/
│   │   ├── dashboard.php         # Dashboard mahasiswa
│   │   ├── biodata.php           # Form biodata
│   │   ├── upload.php            # Upload berkas
│   │   ├── pengumuman.php        # Lihat hasil seleksi
│   │   └── daftar_ulang.php      # Form daftar ulang
│   │
│   ├── admin/
│   │   ├── dashboard.php         # Dashboard admin
│   │   ├── mahasiswa.php         # Data semua mahasiswa
│   │   ├── verifikasi.php        # List verifikasi berkas
│   │   ├── verifikasi_detail.php # Detail verifikasi per mahasiswa
│   │   ├── verifikasi_pembayaran.php # Verifikasi pembayaran
│   │   └── pengumuman.php        # Kelola hasil seleksi
│   │
│   ├── assets/
│   │   └── css/style.css
│   └── uploads/                  # Folder file yang diupload
│       ├── foto/
│       ├── ijazah/
│       ├── rapor/
│       ├── ktp/
│       └── pembayaran/
```

---

## 🗄️ Struktur Database

Database: `pmb_online`

| Tabel | Deskripsi |
|-------|-----------|
| `users` | Akun pengguna (admin & mahasiswa) |
| `biodata_mahasiswa` | Data pribadi & status pendaftaran mahasiswa |
| `documents` | Berkas upload & status verifikasi |
| `daftar_ulang` | Data daftar ulang mahasiswa yang diterima |
| `biaya_kuliah` | Referensi biaya per jurusan & kelas |

---

## ⚙️ Cara Instalasi

### Prasyarat
- PHP >= 8.0
- MySQL >= 8.0
- Web server: **Apache** (XAMPP / Laragon / WAMP) atau **Nginx**

### Langkah-langkah

**1. Clone repository**
```bash
git clone https://github.com/username/pmb-online.git
cd pmb-online
```

**2. Pindahkan ke folder htdocs / www**
```bash
# XAMPP (Windows)
xcopy /E /I pmb-online C:\xampp\htdocs\pmb-online

# XAMPP (Linux/Mac)
cp -r pmb-online /opt/lampp/htdocs/
```

**3. Import database**

- Buka **phpMyAdmin** → buat database baru bernama `pmb_online`
- Klik **Import** → pilih file `pmb_online.sql`
- Klik **Go**

**4. Konfigurasi koneksi database**

Edit file `app/config/database.php`:
```php
$host = "localhost";
$user = "root";       // sesuaikan username MySQL Anda
$pass = "";           // sesuaikan password MySQL Anda
$db   = "pmb_online";
```

**5. Buat folder uploads (jika belum ada)**
```bash
mkdir -p public/uploads/foto
mkdir -p public/uploads/ijazah
mkdir -p public/uploads/rapor
mkdir -p public/uploads/ktp
mkdir -p public/uploads/pembayaran
```

**6. Jalankan aplikasi**

Buka browser dan akses:
```
http://localhost/pmb-online/public/
```

---

## 🔐 Akun Default

| Role | Email | Password |
|------|-------|----------|
| Admin | `admin@pmb.com` | `admin123` |
| Mahasiswa | *(daftar sendiri)* | *(sesuai saat registrasi)* |

> ⚠️ **Penting:** Segera ganti password admin setelah pertama kali login.

---

## 🔄 Alur Penggunaan

```
Mahasiswa                          Admin
    │                                │
    ▼                                │
Registrasi Akun                      │
    │                                │
    ▼                                │
Isi Biodata                          │
    │                                │
    ▼                                │
Upload Berkas ──────────────────► Verifikasi Berkas
(foto, ijazah,                  (Terima / Tolak)
 rapor, KTP)                         │
                                     ▼
                               Set Hasil Seleksi
                               (Diterima / Tidak)
                                     │
                                     ▼
                               Publikasi Pengumuman
                                     │
    ┌────────────────────────────────┘
    │
    ▼
Lihat Pengumuman
    │
    ▼ (jika Diterima)
Form Daftar Ulang ──────────────► Verifikasi Pembayaran
(pilih kelas, data                (konfirmasi admin)
 ortu, asrama,
 bukti bayar)
```

---

## 🛠️ Teknologi yang Digunakan

| Teknologi | Versi | Kegunaan |
|-----------|-------|----------|
| PHP | 8.3 | Backend & logika aplikasi |
| MySQL | 8.4 | Database |
| Bootstrap | 5.3 | UI framework |
| Bootstrap Icons | 1.13 | Ikon |
| Google Fonts | — | Tipografi (Playfair Display, DM Sans) |

---

## 📋 Catatan Pengembangan

- Aplikasi menggunakan **PHP Native** tanpa framework
- Autentikasi menggunakan `$_SESSION` dan `password_hash()` / `password_verify()`
- Upload file disimpan di folder `public/uploads/`
- Role guard diimplementasikan di `app/helpers/auth.php`

---

## 📄 Lisensi

Proyek ini menggunakan lisensi [MIT](LICENSE).

---

## 👤 Author

**Noval Adrian**

[![GitHub](https://img.shields.io/badge/GitHub-@novaladrian-181717?style=flat-square&logo=github)](https://github.com/novaladriann)

---

> Dibuat sebagai proyek sistem informasi Penerimaan Mahasiswa Baru berbasis web.
