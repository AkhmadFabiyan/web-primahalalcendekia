# 00. Overview

## Tujuan

Dokumen ini merupakan titik awal dokumentasi sistem Prima Halal Cendekia (PHC System).

Semua dokumen lain pada folder `/docs` mengacu pada dokumen ini sebagai referensi utama.

Dokumen ini tidak menjelaskan implementasi teknis secara detail, melainkan memberikan gambaran umum mengenai tujuan sistem, ruang lingkup, arsitektur dokumentasi, dan hubungan antar dokumen.

---

# Tentang Sistem

PHC System adalah aplikasi internal berbasis web yang digunakan untuk mengelola seluruh proses layanan sertifikasi halal mulai dari prospek klien (Lead), administrasi, pembayaran, entry SIHALAL, audit, penerbitan sertifikat, hingga pelunasan.

Website ini bukan sekadar sistem pencatatan data, melainkan sistem operasional yang digunakan setiap divisi untuk menjalankan pekerjaannya.

---

# Tujuan Sistem

Sistem dibuat untuk:

- Mengelola seluruh data klien dalam satu platform.
- Mengurangi penggunaan spreadsheet manual.
- Mengontrol workflow antar divisi.
- Mencatat seluruh histori aktivitas.
- Mempermudah monitoring progress setiap proyek.
- Menjadi sumber data utama (Single Source of Truth).

---

# Konsep Utama

Sistem dibangun berdasarkan konsep berikut.

## 1. Project-Centric

Seluruh aktivitas operasional berpusat pada Project milik Klien.

Semua modul seperti pembayaran, dokumen, audit, entry, hingga sertifikat selalu terhubung dengan satu Project.

Setiap Client memiliki tepat satu Project. Project menggunakan UUID internal untuk relasi, sedangkan ID Client menjadi identitas bisnis utama yang ditampilkan kepada pengguna.

Referensi:
- database/projects.md

---

## 2. Workflow Driven

Setiap Project bergerak mengikuti workflow bisnis yang telah ditentukan.

Workflow dikontrol oleh status sistem.

Referensi:
- 02-workflow.md
- 07-status.md

---

## 3. Role Based Access

Setiap pengguna hanya dapat melihat dan mengakses fitur sesuai perannya.

Role Klien hanya mengakses `/dashboard` dan hanya melihat data yang berelasi dengan Client miliknya.

Referensi:

- 03-role-permission.md

---

## 4. Modular

Setiap fitur dipisahkan menjadi modul yang saling terhubung.

Contoh:

Lead
↓

Project
↓

Pembayaran

(Invoice + transaksi Payment)
↓

Dokumen
↓

Audit
↓

Sertifikat

---

# Arsitektur Dokumentasi

docs/

├── 00-overview.md

↓

├── 01-business.md

↓

├── 02-workflow.md

↓

├── 03-role-permission.md

↓

├── 04-database.md

↓

├── 05-api.md

↓

├── 06-routing.md

↓

├── dst...

Semakin besar nomor dokumen, semakin teknis pembahasannya.

---

# Prinsip Dokumentasi

Seluruh dokumentasi wajib mengikuti aturan berikut.

## Single Source of Truth

Setiap informasi hanya memiliki satu sumber resmi.

Contoh:

Role hanya dijelaskan pada:

03-role-permission.md

Status hanya dijelaskan pada:

07-status.md

Database hanya dijelaskan pada:

04-database.md

Dokumen lain cukup memberikan referensi.

---

## Tidak Ada Duplikasi

Dilarang menuliskan ulang informasi yang sudah dijelaskan pada dokumen lain.

Gunakan referensi.

Contoh:

"Lihat 04-database.md"

bukan menjelaskan ulang struktur tabel.

---

## Hubungan Antar Dokumen

Business
↓
Workflow
↓
Role
↓
Status
↓
Database
↓
API
↓
Routing
↓
UI

Semua dokumen mengikuti urutan tersebut.

---

# Struktur Folder

Lihat:

11-folder-structure.md

---

# Roadmap

Lihat:

13-roadmap.md

---

# Catatan

Dokumen ini adalah root document.

Seluruh AI Assistant, Developer, maupun Contributor wajib membaca dokumen ini sebelum membaca dokumen lainnya.


# Technology Stack

PHC System dibangun menggunakan teknologi berikut.

## Backend

- PHP 8.4.23
- Laravel 13.8.0
- MySQL / MariaDB
- Redis
- Queue
- Scheduler

## Admin Panel

- Filament 5.x

## Frontend

- Livewire 4.x
- Volt
- Alpine.js
- Tailwind CSS 4.1+
- shadcn/ui
- Heroicons / Lucide

## Realtime

- Laravel Reverb

## Storage

- Local Storage
- S3 Compatible Storage (Opsional)

## PDF

- DomPDF

## Excel

- Laravel Excel

## Media

- Spatie Media Library

## Permission

- Spatie Permission
- Filament Shield

## Activity Log

- Spatie Activity Log

## Backup

- Spatie Backup

## Health Monitoring

- Spatie Health

## Queue Monitor

- Laravel Queue Monitor

Seluruh library yang digunakan harus bersifat open-source dan memiliki komunitas aktif.

Seluruh implementasi wajib mengikuti Version Lock pada `start-here.md`. Kode atau dokumentasi Filament sebelum v5, fitur yang membutuhkan PHP di atas 8.4.23, dan compatibility layer PHP lama tidak boleh digunakan.
