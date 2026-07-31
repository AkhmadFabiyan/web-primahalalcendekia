# Database - Users

## Tujuan

Dokumen ini menjelaskan entitas **Users** pada PHC System.

Entitas Users menyimpan seluruh akun yang dapat mengakses sistem, baik pengguna internal maupun eksternal.

Dokumen ini hanya menjelaskan struktur dan aturan data pengguna. Arsitektur database dijelaskan pada:

- 04-database.md

Hak akses dijelaskan pada:

- 03-role-permission.md

---

# Referensi

Dokumen terkait

- 03-role-permission.md
- 04-database.md
- 07-status.md

Workflow

- workflow/marketing.md
- workflow/finance.md
- workflow/admin.md
- workflow/entry.md
- workflow/spv-entry.md
- workflow/audit.md
- workflow/sertifikat.md

---

# Tujuan Entitas

Tabel Users digunakan untuk:

- autentikasi
- identifikasi pengguna
- kepemilikan data
- assignment pekerjaan
- activity log
- notification

Users tidak menyimpan data bisnis Project.

---

# Tanggung Jawab

Users bertanggung jawab menyimpan informasi:

- identitas pengguna
- informasi login
- status akun
- relasi Role melalui Spatie Permission
- relasi Client untuk role Klien
- metadata akun

---

# Struktur Data

## Primary Key

id

UUID

Tidak menggunakan auto increment.

---

## Business Identifier

Tidak memiliki Business ID.

Users hanya menggunakan UUID.

---

## Informasi Identitas

Minimal terdiri dari:

- nama
- email
- nomor HP
- foto profil melalui Media Library (opsional)

---

## Informasi Login

Minimal terdiri dari:

- email
- password
- email_verified_at
- last_login_at

Password wajib disimpan dalam bentuk hash.

---

## Status Akun

Menggunakan status yang didefinisikan pada:

07-status.md

Nilai enum:

- ACTIVE
- INACTIVE

---

## Role

Setiap User memiliki tepat satu Role.

Role mengikuti:

03-role-permission.md

Role disimpan hanya melalui tabel standar Spatie Permission. Tabel Users tidak memiliki kolom `role`.

Contoh:

- Super Admin
- Direktur
- Manager Operasional
- Marketing
- Finance
- Admin
- Entry
- SPV Entry
- Pendamping Auditor
- Auditor
- Admin Perusahaan
- Klien

---

## Relasi Client

Minimal terdiri dari:

- client_id (nullable)

Rule:

- `client_id` wajib tersedia apabila Role = Klien.
- `client_id` harus kosong untuk role internal.
- User Klien hanya boleh membaca data yang berelasi dengan `client_id` tersebut.
- Scope tidak boleh diambil dari query parameter atau request body.

---

# Pembuatan User Klien

User dengan Role Klien dibuat oleh Super Admin melalui tombol **Buat Akun** pada detail Client.

Tidak ada form input manual. Sistem mengambil data yang diperlukan dari Client, mengisi `client_id`, menetapkan Role Klien, dan membuat email:

`{user}@primahalalcendekia.com`

Nilai `{user}` dibuat otomatis dari identitas Client, dinormalisasi agar valid sebagai local-part email, dan diberi suffix apabila diperlukan untuk menjamin keunikan.

Satu Client hanya memiliki satu akun login utama yang dibuat melalui aksi tersebut. Tombol dinonaktifkan setelah akun berhasil dibuat.

Sistem membuat kredensial awal secara aman tanpa input Super Admin, lalu menyediakan mekanisme aktivasi atau reset password. Password sementara, jika digunakan, hanya boleh ditampilkan satu kali dan wajib diganti saat login pertama.

---

# Relasi

Users berelasi dengan:

Clients

↓

Portal Klien

Projects

↓

Assignment

Invoices

↓

Created By

Payments

↓

Verified By

Documents

↓

Uploaded By

Notifications

↓

Recipient

Activity Logs

↓

Actor

Workflow Reviews

↓

Reviewer

---

# Cardinality

Users

1

↓

N

Projects Assignment

---

Users

1

↓

N

Documents

---

Users

1

↓

N

Invoices

---

Users

1

↓

N

Payments

---

Users

1

↓

N

Notifications

---

Users

1

↓

N

Activity Logs

---

# Soft Delete

Users menggunakan Soft Delete.

Akun tidak boleh dihapus permanen apabila:

- pernah membuat Project
- pernah membuat Invoice
- pernah melakukan Approval
- pernah muncul pada Activity Log

---

# Audit Trail

Setiap perubahan data User dicatat.

Minimal:

- dibuat
- diubah
- login
- logout
- reset password
- perubahan role
- perubahan status

---

# Business Rule

Email harus unik.

Nomor HP sebaiknya unik.

Password tidak boleh disimpan dalam bentuk plain text.

Role tidak boleh ditentukan dari Frontend.

Status User menentukan hak akses.

User yang dinonaktifkan tidak dapat login.

Pembuatan akun Klien harus idempotent dan wajib menghasilkan Activity Log.

---

# Validasi

Nama wajib diisi.

Email wajib valid.

Password mengikuti kebijakan keamanan.

Role wajib tersedia.

Status wajib tersedia.

`client_id` wajib diisi untuk Role Klien dan harus merujuk ke Client aktif.

---

# Index

Direkomendasikan:

- email
- status
- client_id
- deleted_at

---

# Integritas Data

Apabila User dinonaktifkan.

↓

Assignment tetap ada.

↓

Activity Log tetap ada.

↓

Notification tetap ada.

Data historis tidak boleh hilang.

---

# Migration Recommendation

Contoh struktur minimal.

users

- id
- name
- email
- phone
- password
- client_id
- status
- last_login_at
- email_verified_at
- created_at
- updated_at
- deleted_at

Media collection:

- `profile-avatar`

Constraint yang direkomendasikan:

- unique `email`
- unique `client_id` untuk akun Role Klien; nilai null tetap diperbolehkan bagi User internal

---

# Future Enhancement

Mendukung:

- Multi Role
- Multi Company
- Multi Branch
- Two Factor Authentication
- OAuth
- Single Sign On

---

# Hubungan Dokumen

Role:

03-role-permission.md

Status:

07-status.md

Arsitektur:

04-database.md
