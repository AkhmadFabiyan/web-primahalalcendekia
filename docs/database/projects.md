# Database - Projects

## Tujuan

Dokumen ini menjelaskan entitas **Projects** pada PHC System.

Project merupakan entitas utama (Core Entity) yang mewakili satu layanan sertifikasi halal yang dikerjakan oleh PHC.

Seluruh proses operasional, workflow, pembayaran, dokumen, dan monitoring berpusat pada Project.

Dokumen ini hanya menjelaskan struktur dan aturan data Project.

Arsitektur database dijelaskan pada:

- 04-database.md

---

# Referensi

Dokumen terkait

- 01-business.md
- 02-workflow.md
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
- workflow/pembayaran.md

UI

- ui/klien.md

---

# Tujuan Entitas

Project digunakan untuk:

- menyimpan informasi pekerjaan
- menghubungkan Client
- menghubungkan Workflow
- menghubungkan Invoice
- menghubungkan Payment
- menghubungkan Document
- menghubungkan Assignment
- menghubungkan Activity Log
- menghubungkan Notification

Project merupakan pusat relasi seluruh modul.

---

# Tanggung Jawab

Project bertanggung jawab menyimpan:

- identitas Project
- jenis layanan
- status Project
- tanggal penting
- relasi Client

Project tidak menyimpan detail workflow.

Project tidak menyimpan histori pembayaran.

Project tidak menyimpan file dokumen.

---

# Struktur Data

## Primary Key

id

UUID

---

## Business Identifier

Project tidak memiliki Business ID terpisah.

Project menggunakan UUID internal sebagai Primary Key dan Foreign Key. ID yang ditampilkan pada UI, tugas, Dashboard, Invoice, laporan, dan pencarian adalah `clients.business_id`.

---

## Informasi Umum

Minimal terdiri dari:

- client_id
- project_name
- service_type
- client_nominal
- partner_nominal (nullable)
- payment_scheme

---

## Informasi Waktu

Minimal:

- created_at
- activated_at
- completed_at
- cancelled_at

---

## Status

Menggunakan status yang didefinisikan pada:

07-status.md

Nilai enum:

- WAITING_ACTIVATION
- ACTIVE
- OPERATIONAL
- WAITING_GOVERNMENT_INVOICE
- WAITING_CERTIFICATE
- CERTIFICATE_ISSUED
- WAITING_SETTLEMENT
- COMPLETED
- CANCELLED

Label UI mengikuti pemetaan pada `07-status.md`.

Role Klien hanya dapat membaca Project tunggal yang memiliki `client_id` sama dengan `users.client_id`, dan hanya melalui `/dashboard`.

---

## Progress

Project tidak menyimpan tiga status tracker sebagai kolom terpisah. Status Entry, Pendamping, dan Auditor disimpan pada `workflow_steps` agar tidak terjadi duplikasi sumber kebenaran.

Pengguna berwenang memilih status tracker secara manual. Persentase setiap tracker tidak dapat diedit dan diturunkan dari mapping `07-status.md`.

Progress Project keseluruhan dihitung dari milestone wajib workflow, Sertifikat, dan pembayaran. Apabila progress atau agregat disimpan sebagai cache untuk performa Dashboard, cache wajib dapat dibangun ulang dan diperbarui setiap status tracker atau milestone berubah.

`last_progress_updated_at` juga merupakan nilai turunan dari `workflow_histories` terbaru pada tiga tracker, bukan kolom profil Client yang diedit manual.

---

# Relasi

Project

1

↓

1

Client

---

Project

1

↓

N

Invoice

---

Project

1

↓

N

Payment

(melalui Invoice)

---

Project

1

↓

N

Documents

---

Project

1

↓

N

Workflow Steps

---

Project

1

↓

N

Workflow History

---

Project

1

↓

N

Project Assignment

---

Project

1

↓

N

Activity Log

---

Project

1

↓

N

Notification

---

Project

1

↓

1

Certificate

---

Project

1

↓

1

SIHALAL Credential

---

# Cardinality

Client

1

↓

1

Project

---

Project

1

↓

N

Invoice

---

Project

1

↓

N

Document

---

Project

1

↓

N

Assignment

---

Project

1

↓

N

Workflow History

---

# Workflow

Project tidak menyimpan setiap langkah workflow.

Workflow dipisahkan ke tabel:

- workflow_steps
- workflow_histories

Project hanya mengetahui status akhirnya.

---

# Assignment

User tidak disimpan langsung pada Project.

Gunakan tabel:

project_assignments

Contoh

Project

↓

Marketing

↓

Finance

↓

Admin

↓

Entry

↓

SPV Entry

↓

Pendamping Auditor

↓

Auditor

---

# Soft Delete

Project menggunakan Soft Delete.

Project yang telah memiliki transaksi tidak boleh dihapus permanen.

---

# Audit Trail

Catat perubahan berikut.

- dibuat
- aktif
- selesai
- dibatalkan
- perubahan layanan
- perubahan nilai layanan
- perubahan assignment

---

# Business Rule

Project hanya dibuat dari Lead yang berstatus Deal.

Project selalu memiliki Client.

Project memiliki minimal satu Assignment.

Project memiliki tepat satu Client dan `client_id` wajib unik pada tabel Projects.

Seluruh staf internal yang terlibat disimpan sebagai Assignment, minimal mencakup Entry, Admin Perusahaan, Finance, Auditor, Pendamping Auditor, serta role terkait lain sesuai workflow.

Client tidak disimpan sebagai Assignment karena sudah ditentukan oleh `projects.client_id`.

Project memiliki minimal satu Workflow.

Satu Project merupakan satu transaction group. Untuk Client Mitra, dua Invoice pada event yang sama tetap dihitung sebagai satu transaksi berdasarkan `project_id`.

Project tidak dapat selesai apabila:

- Workflow belum selesai.
- Invoice belum lunas.

Project tidak boleh dihapus setelah memiliki Invoice.

---

# Validasi

Client wajib tersedia.

Jenis layanan wajib dipilih.

Nominal Client lebih dari nol.

Nominal Mitra wajib lebih dari nol jika Client bertipe Mitra dan harus kosong jika Client bertipe Langsung.

Status wajib tersedia.

Business ID wajib unik.

---

# Index

Direkomendasikan

- client_id
- status
- service_type
- created_at
- deleted_at

---

# Integritas Data

Menghapus Project tidak menghapus:

- Invoice
- Payment
- Document
- Notification
- Activity Log
- Workflow History

Data historis harus tetap tersedia.

---

# Migration Recommendation

Contoh struktur minimal.

projects

- id
- client_id
- project_name
- service_type
- client_nominal
- partner_nominal
- payment_scheme
- status
- activated_at
- completed_at
- cancelled_at
- created_at
- updated_at
- deleted_at

Constraint yang direkomendasikan:

- unique `client_id`

---

# Future Enhancement

Mendukung:

- Multi Service
- Multi Branch
- Parent Project
- Renewal Project
- Clone Project
- Template Workflow
- SLA Monitoring
- Digital Signature

---

# Hubungan Dokumen

Workflow

- workflow/*

Status

- 07-status.md

Database

- 04-database.md
