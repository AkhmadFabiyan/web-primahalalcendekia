# Database - Clients

## Tujuan

Dokumen ini menjelaskan entitas **Clients** pada PHC System.

Client merupakan representasi badan usaha atau organisasi yang menggunakan layanan PHC.

Entitas ini menyimpan informasi administratif klien dan menjadi induk dari tepat satu Project.

Dokumen ini hanya menjelaskan struktur dan aturan data Client.

Arsitektur database dijelaskan pada:

- 04-database.md

---

# Referensi

Dokumen terkait

- 01-business.md
- 04-database.md
- 07-status.md

Workflow

- workflow/marketing.md
- workflow/finance.md

UI

- ui/klien.md

---

# Tujuan Entitas

Clients digunakan untuk:

- menyimpan data perusahaan
- menyimpan identitas klien
- menghubungkan Project
- menyimpan informasi kontak
- menjadi referensi administrasi
- menentukan Tipe Klien Langsung atau Mitra

Clients tidak menyimpan progress workflow.

Clients tidak menyimpan invoice.

Clients tidak menyimpan status operasional Project.

---

# Tanggung Jawab

Entitas Client bertanggung jawab menyimpan:

- identitas perusahaan
- informasi kontak
- data legal dasar
- informasi PIC

---

# Struktur Data

## Primary Key

id

UUID

---

## Business Identifier

Wajib menggunakan:

CLIENT-YYYY-XXXX

Contoh:

CLIENT-2026-0001

Business ID dibuat otomatis ketika Lead berubah menjadi Deal. Nilai ini menjadi identitas utama pada UI, tugas, pencarian, Invoice, laporan, dan komunikasi bisnis.

Business ID tidak dapat diisi atau diubah secara manual.

---

## Informasi Perusahaan

Minimal terdiri dari:

- nama perusahaan
- tipe klien
- partner_id (khusus Mitra)
- bentuk usaha
- bidang usaha
- alamat
- kota
- provinsi
- kode pos

---

## Informasi PIC

Minimal terdiri dari:

- nama PIC
- nomor HP
- email

PIC dapat berubah tanpa mengubah identitas Client.

---

## Informasi Legal

Opsional sesuai kebutuhan bisnis.

Contoh:

- NIB
- NPWP
- Nomor Akta
- Nama Direktur

Dokumen legal disimpan pada modul Documents, bukan langsung pada tabel Client.

---

## Tipe Klien

| Kode | Label UI |
|---|---|
| `DIRECT` | Langsung |
| `PARTNER` | Mitra |

Rule:

- `partner_id` wajib kosong untuk `DIRECT`.
- `partner_id` wajib tersedia untuk `PARTNER`.
- Tipe Klien dan Partner tidak dapat diubah setelah salah satu Invoice diterbitkan.

---

# Relasi

Client

1

↓

1

Users dengan Role Klien

---

Client

1

↓

1

Projects

---

# Cardinality

Client

1

↓

1

User Klien

Setiap User dengan Role Klien wajib terhubung ke tepat satu Client.

---

Client

1

↓

1

Project

Satu Client memiliki tepat satu Project. Layanan, perpanjangan, atau perubahan proses dicatat dalam Project yang sama sesuai workflow yang berlaku.

---

# Soft Delete

Client menggunakan Soft Delete.

Client tidak boleh dihapus apabila telah memiliki Project.

---

# Audit Trail

Catat perubahan berikut.

- dibuat
- diubah
- perubahan PIC
- perubahan alamat
- perubahan informasi legal
- dinonaktifkan

---

# Business Rule

Lead yang berstatus Deal menghasilkan satu Client apabila Client belum ada.

Apabila perusahaan yang sama sudah terdaftar.

↓

Gunakan Client yang sudah ada.

↓

Jangan membuat Client baru.

Client memiliki tepat satu Project.

Project wajib memiliki satu Client.

Pembuatan Client dan Project harus berada dalam satu transaksi ketika Lead menjadi Deal agar relasi 1:1 tidak pernah terputus.

---

# Akun Login Klien

Akun login Klien bukan data Client.

Super Admin membuat akun melalui aksi **Buat Akun** pada detail Client tanpa mengisi data manual.

Sistem:

- memastikan Client belum memiliki akun login
- membuat User dengan Role Klien
- mengisi `client_id` secara otomatis
- membuat `{user}` yang unik dari identitas Client
- membuat email dengan format `{user}@primahalalcendekia.com`
- mencatat aktivitas pada Activity Log

Aksi harus idempotent dan tidak boleh menghasilkan akun ganda.

---

# Validasi

Nama perusahaan wajib diisi.

PIC wajib tersedia.

Nomor HP harus valid.

Email mengikuti format email.

Nama perusahaan sebaiknya unik.

Tipe Klien wajib dipilih.

Partner wajib tersedia jika Tipe Klien = Mitra dan harus kosong jika Tipe Klien = Langsung.

---

# Index

Direkomendasikan:

- business_id
- company_name
- phone
- email
- deleted_at

---

# Integritas Data

Menghapus Client tidak menghapus:

- Project
- Invoice
- Payment
- Activity Log
- Notification

Data historis harus tetap tersedia.

---

# Migration Recommendation

Contoh struktur minimal.

clients

- id
- business_id
- client_type
- partner_id
- company_name
- company_type
- business_sector
- address
- city
- province
- postal_code
- pic_name
- pic_phone
- pic_email
- created_at
- updated_at
- deleted_at

Constraint yang direkomendasikan:

- unique `business_id`
- unique `projects.client_id`
- unique `users.client_id`
- index `client_type`
- index `partner_id`

---

# Future Enhancement

Mendukung:

- Multi PIC
- Banyak Cabang
- Banyak Alamat
- Banyak Kontak
- Banyak NPWP
- Multi Company Group
- Integrasi CRM

---

# Hubungan Dokumen

Workflow

- workflow/marketing.md

Status

- 07-status.md

Arsitektur

- 04-database.md
