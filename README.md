# Training Apps

Sebuah aplikasi manajemen training sederhana untuk keperluan internal (PHP + MySQL).

## Ringkasan
Aplikasi ini menyimpan data training, staff, dan menyediakan fitur export data (MTD/YTD). Berisi dashboard admin untuk mengelola staff dan training codes.

## Persyaratan
- PHP 7.4+ (atau versi yang tersedia pada XAMPP)
- MySQL / MariaDB
- XAMPP pada Windows
- Ekstensi PHP: mysqli, mbstring, json

## Instalasi cepat
1. Letakkan folder di `C:\xampp\htdocs\Training Apps`
2. Buat database dan import schema (mis. `schema.sql`)
3. Edit `config.php` sesuaikan koneksi database
4. Buka http://localhost/Training%20Apps/admin_dashboard.php

## Penggunaan
- Login sebagai admin untuk mengakses dashboard.
- Gunakan menu "Add New Training Code" untuk menambah training.
- "Data Staff" menampilkan staff dan statistik training per staff.

## Struktur penting
- admin_dashboard.php — antarmuka admin
- config.php — konfigurasi koneksi DB
- export_*.php — skrip export data
- preview_export.php — endpoint preview sebelum export

## Kontribusi
Buat branch baru, lakukan perubahan, dan ajukan pull request. Sertakan deskripsi perubahan dan langkah reproduski.

## Lisensi
Tentukan lisensi proyek di sini (mis. MIT).
