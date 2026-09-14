<div align="center">

# SahabatPeduli

SahabatPeduli adalah platform web interaktif Lembaga Amil Zakat, Infaq, Sedekah, Wakaf, dan Fidyah yang transparan, amanah, dan profesional. Platform ini dilengkapi dengan kalkulator zakat otomatis, pemetaan lokasi penyaluran, publikasi berita, transparansi laporan keuangan PDF, serta panel administrasi lengkap dengan dukungan mode terang & gelap.

<p>
  <img src="https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white">
  <img src="https://img.shields.io/badge/Tailwind_CSS-38B2AC?style=for-the-badge&logo=tailwind-css&logoColor=white">
  <img src="https://img.shields.io/badge/JavaScript-F7DF1E?style=for-the-badge&logo=javascript&logoColor=black">
  <img src="https://img.shields.io/badge/MySQL-4479A1?style=for-the-badge&logo=mysql&logoColor=white">
  <img src="https://img.shields.io/badge/Status-In_Development-F59E0B?style=for-the-badge">
</p>

</div>

---

## Overview

SahabatPeduli dirancang untuk memudahkan masyarakat dalam menghitung dan menunaikan kewajiban zakat (Penghasilan & Maal) secara presisi, memantau program donasi, serta mengakses laporan transparansi publik. Dikembangkan dengan antarmuka yang modern, ramah pengguna, serta mendukung penuh adaptasi tema (Light & Dark Mode) secara konsisten di seluruh halaman pengguna maupun dashboard admin.

---

## Key Features

* **Kalkulator Zakat Interaktif:** Perhitungan otomatis Zakat Penghasilan dan Zakat Maal/Emas berdasarkan nisab yang disesuaikan secara real-time.
* **Form Donasi & Format Nominal:** Penginputan nominal donasi interaktif dengan format angka otomatis (pemisah ribuan).
* **Responsive & Full Adaptive Theme:** Tampilan modern dengan dukungan Light Mode dan Dark Mode di halaman publik, autentikasi (Login/Register), hingga Dashboard Admin.
* **Peta Penyaluran:** Visualisasi interaktif lokasi dan distribusi program bantuan kemanusiaan secara langsung.
* **Publikasi Berita & Artikel:** Integrasi penerbitan berita dari admin yang terhubung dan sinkron otomatis dengan halaman utama.
* **Transparansi Laporan Keuangan:** Manajemen dan publikasi berkas laporan PDF ter-audit untuk transparansi publik.
* **Dashboard Admin Komprehensif:** Panel pengelolaan program, riwayat donasi, titik penyaluran, artikel berita, dan dokumen laporan secara terpusat.
* **Identitas & Media Sosial Resmi:** Informasi kontak, alamat lembaga, serta tautan ke kanal media sosial yang terintegrasi.

---

## Technologies

| Technology | Description |
| ------------ | ---------------------------------------- |
| PHP | Pemrosesan *back-end* dan manajemen logika aplikasi |
| Tailwind CSS | Utility-first CSS framework untuk tampilan responsif dan adaptif tema |
| JavaScript (ES6+) | Logika kalkulator, manipulasi DOM, dan format nominal otomatis |
| Leaflet.js | Library peta interaktif untuk pemetaan lokasi penyaluran |
| MySQL | Basis data relational untuk penyimpanan data program, donasi, berita, dan laporan |

---

## Setup & Installation

Ikuti langkah-langkah berikut untuk menjalankan SahabatPeduli di komputer lokal Anda.

### Prerequisites

* Web Server lokal (XAMPP / Laragon / WampServer) dengan dukungan PHP & MySQL
* Web browser modern (Google Chrome, Mozilla Firefox, Microsoft Edge, Brave)
* Git terpasang di komputer

### 1. Clone Repository

Buka terminal atau Command Prompt dan jalankan:

```bash
git clone [https://github.com/devilovers/PeduliUmat.git](https://github.com/devilovers/PeduliUmat.git) SahabatPeduli
cd SahabatPeduli
```

2. Konfigurasi Database
Buka dashboard MySQL (misal: phpMyAdmin) dan buat database baru bernama sahabatpeduli (atau sesuai konfigurasi lokal Anda).

Impor struktur tabel database (public_reports, programs, distributions, articles, dll).

Sesuaikan konfigurasi koneksi database pada file config/database.php.

3. Run Project
Pastikan folder proyek berada dalam direktori server lokal Anda (htdocs untuk XAMPP atau www untuk Laragon), lalu buka melalui browser:

Plaintext
http://localhost/SahabatPeduli

## Author

<p>
<strong>Nur Islami Sabila</strong><br>
Informatics Engineering Student from Indonesia.
</p>

<blockquote>
Learning by building, growing by creating.
</blockquote>

<p align="left">
If you found this project helpful, consider giving it a ⭐ to support the repository.
</p>
