# 12. Development Rules

## Tujuan

Dokumen ini mendefinisikan standar pengembangan PHC System.

Seluruh Developer, AI Assistant, maupun Contributor wajib mengikuti aturan yang terdapat pada dokumen ini.

Dokumen ini menjadi acuan utama dalam proses implementasi, review, dan maintenance aplikasi.

---

# Referensi

Dokumen terkait:

- 04-database.md
- 05-api.md
- 06-routing.md
- 07-status.md
- 10-design-system.md
- 11-folder-structure.md

---

# Prinsip Pengembangan

PHC System mengikuti prinsip berikut.

- Clean Code
- Modular
- Reusable
- Readable
- Scalable
- Secure
- Consistent

Kode yang mudah dipahami lebih diprioritaskan dibanding kode yang terlalu kompleks.

---

# Single Source of Truth

Seluruh aturan sistem hanya boleh memiliki satu sumber.

Contoh:

Status

↓

07-status.md

Role

↓

03-role-permission.md

Database

↓

04-database.md

Developer tidak diperbolehkan mendefinisikan ulang aturan tersebut di tempat lain.

---

# Business Rule

Business Rule tidak boleh ditulis langsung di UI.

Business Rule berada pada:

- Service
- Backend
- Domain Logic

UI hanya bertugas menampilkan data dan menerima input.

---

# Separation of Concern

Pisahkan tanggung jawab setiap layer.

UI

↓

Hook

↓

Service

↓

API

↓

Database

Jangan mencampurkan beberapa tanggung jawab dalam satu file.

---

# Reusability

Apabila komponen telah tersedia, gunakan kembali.

Hindari membuat komponen baru dengan fungsi yang sama.

---

# DRY (Don't Repeat Yourself)

Logika yang sama tidak boleh ditulis berulang.

Gunakan:

- Helper
- Hook
- Service
- Shared Component

---

# KISS (Keep It Simple)

Gunakan solusi yang paling sederhana selama memenuhi kebutuhan.

Hindari abstraksi yang belum diperlukan.

---

# YAGNI (You Aren't Gonna Need It)

Jangan membuat fitur yang belum digunakan.

Implementasikan hanya kebutuhan yang telah disepakati.

---

# Penamaan

Gunakan nama yang jelas.

Baik

```
createInvoice()
```

Buruk

```
processData()
```

---

# Magic Number

Hindari angka tetap di dalam kode.

Buruk

```
if(status == 3)
```

Baik

```
if(status === PROJECT_STATUS.ACTIVE)
```

---

# Enum

Gunakan Enum untuk:

- Status
- Role
- Permission
- Notification Type
- Client Type
- Invoice Audience

Jangan menggunakan String bebas.

---

# Hardcode

Hindari Hardcode.

Contoh yang salah:

- Role
- Status
- Jenis Dokumen
- Jenis Invoice

Gunakan:

- Database
- Constant
- Enum

---

# Aturan Identitas Client dan Project

- Satu Client memiliki tepat satu Project.
- ID Client dibuat otomatis ketika Lead menjadi Deal.
- Project menggunakan UUID internal dan tidak memiliki Business ID yang ditampilkan.
- UI, pencarian, tugas, Invoice, dan laporan menggunakan ID Client.
- Constraint unik pada `projects.client_id` wajib diterapkan.

---

# Aturan Normalisasi Database

- Role hanya disimpan melalui tabel Spatie Permission; dilarang menambah `users.role`.
- Activity bisnis hanya disimpan melalui Spatie Activity Log pada tabel `activity_log`.
- Perubahan before/after disimpan di `activity_log.properties`, bukan kolom atau tabel audit kedua.
- File dan metadata fisik hanya disimpan melalui Spatie Media Library.
- Documents, Payments, Certificates, dan Users tidak menyimpan path, MIME type, ukuran, atau nama file secara duplikat.
- Status baca Notification diturunkan dari `read_at`; dilarang menambah `is_read` atau kolom status baca kedua.
- Project Assignment hanya menyimpan User internal; Client diturunkan dari `projects.client_id`.
- Field turunan tidak disimpan kecuali sebagai cache yang memiliki aturan invalidation.
- Foreign key, unique constraint, check constraint, dan index wajib mengikuti `database/*.md`.

---

# Aturan Client Mitra

- Client Type menggunakan `DIRECT` atau `PARTNER`.
- `PARTNER` wajib memiliki `partner_id`, Nominal Client, dan Nominal Mitra.
- Setiap event penagihan komersial Mitra menghasilkan Invoice audience Client dan Partner pada satu billing group.
- Kedua Invoice menggunakan satu Project dan satu ID Client.
- `discount_total` wajib nol untuk Invoice Mitra.
- Transaction count dihitung dengan Project unik, bukan jumlah Invoice.
- Nominal Mitra dan Invoice Partner tidak boleh dikirim ke Role Klien.

---

# Aturan Aksi Operasional

- Create manual hanya tersedia pada Leads.
- Resource lanjutan dibuat oleh event workflow.
- Bulk Action dinonaktifkan.
- Delete dinonaktifkan.
- Context action yang tervalidasi tetap diperbolehkan sesuai Role dan status.
- **Buat Akun** adalah context action khusus Super Admin dan bukan Create generik.
- Pembuatan akun Klien tidak menerima input manual dan harus idempotent.

---

# Validation

Semua input wajib divalidasi.

Validasi dilakukan:

Frontend

↓

Backend

---

# Error Handling

Seluruh Error harus ditangani.

Minimal:

- Logging
- User Friendly Message
- HTTP Status yang sesuai

---

# Logging

Gunakan Activity Log untuk aktivitas bisnis.

Gunakan System Log untuk Error aplikasi.

Keduanya memiliki fungsi berbeda.

---

# Security

Seluruh Endpoint harus:

- Authentication
- Authorization
- Validation

Tidak boleh mempercayai input dari Client.

Khusus role Klien:

- ownership scope wajib diterapkan di query/service/backend
- gunakan `users.client_id` dari session atau token yang tervalidasi
- abaikan atau tolak `client_id` dan `project_id` dari request sebagai penentu scope
- endpoint self-scoped tidak boleh menerima arbitrary Client ID
- resource milik Client lain dikembalikan sebagai 404
- pengujian authorization lintas Client wajib tersedia

Perilaku akses tanpa kewenangan:

- UI tidak merender menu, tombol, tab, action, atau link yang tidak diizinkan.
- Web route mengarahkan User ke halaman aman yang dapat diakses, dengan prioritas `/dashboard`.
- Aplikasi tidak menampilkan halaman penolakan akses.
- API memperlakukan resource di luar scope sebagai tidak tersedia dan mengembalikan 404.
- Policy, middleware, query scope, serta ownership check tetap wajib; menyembunyikan UI saja tidak cukup.

---

# Permission

Seluruh Action harus memeriksa Permission.

Contoh:

Delete Invoice

↓

Cek Permission

↓

Eksekusi

---

# Workflow

Workflow wajib mengikuti:

02-workflow.md

Developer tidak boleh mengubah alur Workflow tanpa memperbarui dokumentasi.

---

# Status

Seluruh Status wajib menggunakan:

07-status.md

Tidak diperbolehkan membuat Status baru secara langsung di kode.

---

# API

Seluruh Endpoint mengikuti:

05-api.md

Format Response harus konsisten.

---

# Database

Perubahan struktur Database wajib diperbarui pada:

04-database.md

dan

database/*.md

---

# UI

Seluruh UI mengikuti:

09-ui-ux.md

dan

10-design-system.md

---

# Folder Structure

Seluruh file mengikuti:

11-folder-structure.md

---

# Import

Gunakan Absolute Import.

Contoh

```
@/modules/projects
```

Hindari Relative Import yang terlalu panjang.

---

# Comment

Komentar hanya digunakan apabila benar-benar diperlukan.

Hindari komentar yang menjelaskan sesuatu yang sudah jelas.

Buruk

```ts
// tambah invoice
createInvoice()
```

Baik

```ts
// Invoice Aktivasi harus dibuat sebelum Project menjadi Aktif.
```

---

# TODO

Gunakan format:

```
TODO:
FIXME:
NOTE:
```

Jangan menggunakan komentar acak.

---

# Console

Tidak boleh ada:

```
console.log()
```

pada Production.

---

# Testing

Minimal lakukan pengujian pada:

- Business Logic
- Workflow
- API
- Validation

---

# Performance

Hindari:

- Query berulang
- Render berulang
- Request API yang tidak diperlukan

Gunakan:

- Pagination
- Lazy Loading
- Caching apabila diperlukan

---

# Pull Request Checklist

Sebelum Merge.

Pastikan:

- Build berhasil.
- Tidak ada Error.
- Tidak ada Warning penting.
- Dokumentasi telah diperbarui.
- Tidak ada kode yang tidak digunakan.
- Permission telah diuji.
- Workflow telah diuji.
- Status telah sesuai.

---

# Code Review

Reviewer wajib memeriksa:

- Business Rule
- Naming
- Struktur Folder
- Permission
- Workflow
- Status
- Reusability
- Dokumentasi

---

# Breaking Change

Perubahan berikut dianggap Breaking Change.

- Struktur Database
- API
- Workflow
- Status
- Permission

Breaking Change wajib memperbarui dokumentasi terkait.

---

# Dokumentasi

Setiap perubahan fitur harus disertai pembaruan dokumentasi apabila memengaruhi:

- Workflow
- Database
- API
- Status
- UI
- Permission

Dokumentasi merupakan bagian dari Definition of Done.

---

# Definition of Done

Sebuah fitur dianggap selesai apabila:

- Requirement selesai.
- Business Rule sesuai.
- UI selesai.
- API selesai.
- Database selesai.
- Validation selesai.
- Permission selesai.
- Activity Log berjalan.
- Notifikasi berjalan (jika diperlukan).
- Dokumentasi diperbarui.
- Code Review disetujui.

Fitur belum dianggap selesai apabila salah satu poin di atas belum terpenuhi.

# Standard Libraries

Seluruh developer wajib menggunakan library standar berikut.

## Admin Panel

- filament/filament `^5.0`

Digunakan untuk:

- Authentication
- Dashboard
- Resource
- Form
- Table
- Widget
- Panel

Tidak diperbolehkan membuat Admin Panel manual.

Implementasi wajib menggunakan API, namespace, generator, resource, schema, action, table, form, dan panel Filament 5. Dilarang menyalin pola Filament 2, 3, atau 4.

---

## Permission

- spatie/laravel-permission
- bezhansalleh/filament-shield

Digunakan untuk:

- Role
- Permission
- Policy
- Authorization

Shield menjadi sinkronisasi utama dengan Filament 5.

---

## Activity Log

- spatie/laravel-activitylog

Digunakan untuk mencatat:

- Create
- Update
- Delete
- Login
- Workflow

---

## Media

- spatie/laravel-medialibrary
- spatie/image

Seluruh upload file wajib menggunakan Media Library.

Tidak diperbolehkan menyimpan path file secara manual.

---

## Tag

- spatie/laravel-tags

Digunakan untuk:

- Label
- Kategori
- Pencarian

---

## State Machine

- spatie/laravel-model-states

Digunakan untuk:

- Status Project
- Status Invoice
- Status Payment
- Workflow

Status tidak menggunakan string biasa.

---

## Settings

- spatie/laravel-settings

Digunakan untuk seluruh konfigurasi aplikasi.

Tidak diperbolehkan membuat tabel settings manual.

---

## Cache

- spatie/laravel-responsecache

Digunakan untuk endpoint read-only.

---

## PDF

- barryvdh/laravel-dompdf

Digunakan untuk:

- Invoice
- Laporan
- Dokumen

---

## Excel

- maatwebsite/excel

Digunakan untuk:

- Import
- Export

---

## Slug

- spatie/laravel-sluggable

Digunakan apabila terdapat URL publik.

---

## Backup

- spatie/laravel-backup

Digunakan untuk backup otomatis database dan storage.

---

## Sitemap

- spatie/laravel-sitemap

Digunakan apabila tersedia website publik.

---

## Health

- spatie/laravel-health

Digunakan untuk monitoring:

- Queue
- Database
- Storage
- Redis

---

## Queue Monitor

- romanzipp/laravel-queue-monitor

Digunakan untuk monitoring Job Queue.

---

## Log Viewer

- opcodesio/log-viewer

Digunakan untuk melihat log aplikasi.

---

## Realtime

- Laravel Reverb

Digunakan untuk:

- Notification
- Dashboard
- Progress Workflow

---

## Icons

Gunakan hanya:

- Heroicons
- Lucide

Tidak diperbolehkan mencampur banyak icon library.

---

## UI Component

Gunakan:

- Tailwind CSS 4.1+ dalam major 4.x
- shadcn/ui

Seluruh komponen baru mengikuti Design System.

---

## Filament Plugins

Plugin standar:

- Filament Shield
- Filament Apex Charts
- Filament Breezy (opsional)
- Filament Logger (opsional)
- Filament FullCalendar (jika diperlukan)

Plugin lain harus melalui proses review sebelum digunakan.

Seluruh plugin wajib memiliki release yang kompatibel dengan Filament 5.x, Laravel 13.8.0, Livewire 4.x, dan PHP 8.4.23. Plugin tanpa bukti kompatibilitas tidak boleh dipasang.

---

# Runtime dan Framework Version Policy

Versi wajib:

- PHP `8.4.23`
- Laravel `13.8.0`
- Filament `5.x`
- Livewire `4.x`
- Tailwind CSS `4.1+` dalam major `4.x`

Aturan implementasi:

1. Semua command PHP, Composer, Artisan, test, queue, scheduler, dan deployment dijalankan dengan PHP 8.4.23.
2. `composer.json` menggunakan `php: 8.4.23`, `laravel/framework: 13.8.0`, `filament/filament: ^5.0`, dan `livewire/livewire: ^4.0`.
3. `composer.lock` dan lockfile frontend wajib di-commit serta menjadi sumber versi patch yang diinstal.
4. Gunakan `composer install`, bukan update dependency tanpa review.
5. Dokumentasi Filament hanya dari `https://filamentphp.com/docs/5.x/`.
6. Dilarang menggunakan tutorial, snippet, generator, method, namespace, lifecycle hook, atau struktur resource dari Filament 2, 3, atau 4.
7. Dilarang menambahkan polyfill atau fallback untuk PHP sebelum 8.4.23.
8. Dilarang menggunakan syntax, fungsi, atau library yang membutuhkan PHP di atas 8.4.23.
9. CI wajib gagal apabila runtime bukan PHP 8.4.23, Laravel bukan 13.8.0, atau major Filament bukan 5.
10. Code review wajib memeriksa bahwa implementasi menggunakan pola Filament 5 dan kompatibel penuh dengan PHP 8.4.23.
11. Setiap perubahan versi PHP atau Laravel, termasuk patch version, merupakan Breaking Change dan harus mendapat persetujuan Project Owner.
12. Upgrade major Filament, Livewire, atau Tailwind merupakan Breaking Change dan harus mendapat persetujuan Project Owner.
