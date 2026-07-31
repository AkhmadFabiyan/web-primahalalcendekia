# UI - Settings

## Tujuan

Dokumen ini menjelaskan halaman **Settings** pada PHC System.

Halaman Settings digunakan untuk mengelola konfigurasi sistem, master data, pengguna, role, dan preferensi aplikasi.

Halaman ini hanya dapat diakses oleh pengguna yang memiliki hak administrasi.

Dokumen ini hanya menjelaskan antarmuka (UI) dan pengalaman pengguna (UX).

Routing dijelaskan pada:

- 06-routing.md

Hak akses dijelaskan pada:

- 03-role-permission.md

---

# Referensi

Dokumen terkait

- 03-role-permission.md
- 04-database.md
- 06-routing.md
- 08-notification.md
- 10-design-system.md

Database

- database/users.md
- database/documents.md

---

# Tujuan Halaman

Halaman Settings digunakan untuk:

- mengelola pengguna
- mengelola role
- mengelola permission
- mengelola master data
- mengatur notifikasi
- mengatur preferensi sistem
- melihat informasi aplikasi

Halaman ini tidak digunakan untuk operasional Project.

---

# Hak Akses

Role yang dapat mengakses:

- Super Admin

Role lain tidak memiliki akses ke halaman Settings.

---

# Struktur Halaman

Halaman terdiri dari:

1. Sidebar Menu
2. Header
3. Content Area
4. Action Bar

---

# Sidebar Menu

Menu utama:

- User Management
- Role Management
- Permission
- Workflow Configuration
- Document Type
- Notification
- Master Data
- System Preference
- Audit Log
- About

Sidebar tetap terlihat pada Desktop.

Pada Mobile menggunakan Drawer.

---

# Header

Menampilkan:

- Nama Menu
- Breadcrumb
- Tombol Simpan
- Tombol Refresh

---

# User Management

Digunakan untuk:

- melihat User
- mengelola akun internal
- mengubah User
- mengaktifkan User
- menonaktifkan User
- reset password

Akun login Klien tidak dibuat melalui form User Management. Super Admin menggunakan tombol **Buat Akun** pada detail Client. Sistem mengisi seluruh data dan membuat email `{user}@primahalalcendekia.com` secara otomatis.

## Kolom

- Nama
- Email
- Role
- Status
- Last Login

---

# Role Management

Digunakan untuk:

- membuat Role
- mengubah Role
- menghapus Role
- mengatur Permission

---

# Permission

Menampilkan daftar seluruh Permission.

Permission dikelompokkan berdasarkan modul.

Contoh:

- Dashboard
- Lead
- Client
- Invoice
- Payment
- Workflow
- Report
- Setting

---

# Workflow Configuration

Digunakan untuk:

- melihat workflow
- mengatur assignment default
- mengatur SLA
- mengaktifkan workflow tertentu

Perubahan workflow harus melalui Super Admin.

---

# Document Type

Mengelola master dokumen.

Contoh:

- NIB
- NPWP
- Sertifikat Halal
- Manual SJPH
- Foto Produk

Dokumen ini digunakan oleh seluruh Project.

---

# Notification

Pengaturan:

- Email
- WhatsApp
- In App Notification

Super Admin dapat:

- mengaktifkan
- menonaktifkan
- mengatur template

---

# Master Data

Berisi:

- Kota
- Provinsi
- Jenis Layanan
- Bidang Usaha
- Sumber Lead
- Metode Pembayaran

Master Data digunakan oleh seluruh modul.

Master Partner ditampilkan sebagai data terpisah untuk Client bertipe Mitra dan menyimpan kode, nama, PIC, serta kontak Partner. Record Partner dibuat otomatis melalui proses Lead Deal; Settings hanya menyediakan view dan update sesuai permission tanpa Create atau Delete.

---

# System Preference

Konfigurasi umum:

- Nama Aplikasi
- Logo
- Timezone
- Bahasa
- Format Tanggal
- Format Nomor

---

# Audit Log

Menampilkan:

- User
- Modul
- Aktivitas
- Waktu
- IP Address

Audit Log hanya dapat dibaca.

---

# About

Menampilkan:

- Nama Sistem
- Versi
- Build Number
- Environment
- Release Date

---

# Search

Mendukung pencarian pada:

- User
- Role
- Master Data

---

# Empty State

Belum ada data.

---

# Loading State

Gunakan Skeleton Table.

---

# Error State

Tampilkan:

- pesan kesalahan
- tombol Muat Ulang

---

# Responsive

Desktop

Sidebar tetap terlihat.

Tablet

Sidebar Collapse.

Mobile

Sidebar menjadi Drawer.

---

# UX Guideline

Perubahan konfigurasi harus memiliki konfirmasi.

Perubahan penting menggunakan dialog konfirmasi.

Gunakan Tab untuk memisahkan setiap modul.

Master Data menggunakan Table + Drawer.

---

# Business Rule

Role menentukan hak akses pengguna.

Permission mengikuti Role.

Master Data digunakan oleh seluruh modul.

Perubahan konfigurasi dicatat pada Audit Log.

Workflow hanya dapat diubah oleh Super Admin.

---

# Future Enhancement

Mendukung:

- Theme Configuration
- Branding
- API Key Management
- Webhook
- Backup & Restore
- SSO
- LDAP
- OAuth
- Feature Flag
- System Health Dashboard

---

# Hubungan Dokumen

Role

- 03-role-permission.md

Database

- database/users.md
- database/documents.md
- database/logs.md

Notification

- 08-notification.md

Design

- 10-design-system.md
