# Database - Notifications

## Tujuan

Dokumen ini menjelaskan entitas **Notifications** pada PHC System.

Notifications digunakan untuk mengirim informasi kepada pengguna mengenai perubahan workflow, penugasan, pembayaran, maupun aktivitas penting lainnya.

Dokumen ini hanya menjelaskan struktur dan aturan data Notification.

Arsitektur database dijelaskan pada:

- 04-database.md

---

# Referensi

Dokumen terkait

- 04-database.md
- 07-status.md
- 08-notification.md

Workflow

- workflow/marketing.md
- workflow/finance.md
- workflow/admin.md
- workflow/entry.md
- workflow/spv-entry.md
- workflow/audit.md
- workflow/sertifikat.md
- workflow/pembayaran.md

---

# Tujuan Entitas

Notifications digunakan untuk:

- memberitahu pengguna mengenai aktivitas baru
- mengirim penugasan
- mengirim pengingat
- menginformasikan perubahan status
- menghubungkan pengguna dengan Project terkait

Notification bukan Audit Trail.

Notification bukan histori aktivitas.

---

# Tanggung Jawab

Notification bertanggung jawab menyimpan:

- penerima
- judul
- isi pesan
- tipe notifikasi
- status baca
- waktu pengiriman
- referensi objek

---

# Struktur Data

## Primary Key

id

UUID

---

## Informasi Dasar

Minimal terdiri dari:

- recipient_id
- project_id (opsional)
- type
- title
- message
- priority
- read_at (opsional)
- archived_at (opsional)
- sent_at

---

## Referensi Objek

Untuk memudahkan navigasi, notification dapat menyimpan:

- entity
- entity_id
- route

Contoh:

entity

Invoice

entity_id

UUID Invoice

route

/payments/invoices/{invoiceId}

Untuk penerima dengan Role Klien, route wajib menggunakan anchor Dashboard, misalnya:

`/dashboard?section=payments&invoice={invoiceId}`

---

## Prioritas

Nilai enum:

- LOW
- MEDIUM
- HIGH

Prioritas digunakan untuk pengurutan tampilan.

---

## Status

Status baca tidak disimpan sebagai kolom terpisah:

- `read_at IS NULL` → `UNREAD`
- `read_at IS NOT NULL` → `READ`

Kode status pada API dan UI diturunkan dari `read_at`.

---

# Relasi

Notification

N

↓

1

User

---

Notification

N

↓

1

Project

(Opsional)

---

Notification

N

↓

1

Entity

(Opsional)

---

# Cardinality

User

1

↓

N

Notification

---

Project

1

↓

N

Notification

---

# Jenis Notification

Nilai `type` mengikuti daftar event pada `08-notification.md`.

Nama kode menggunakan `UPPER_SNAKE_CASE` dari nama event, misalnya:

- `LEAD_DEAL`
- `INVOICE_CREATED`
- `PAYMENT_VERIFIED`
- `DOCUMENTS_COMPLETED`
- `ENTRY_REVISION_REQUESTED`
- `AUDIT_SCHEDULED`
- `CERTIFICATE_UPLOADED`
- `PAYMENT_COMPLETED`

Jenis baru tidak boleh ditambahkan hanya pada database; `08-notification.md` harus diperbarui terlebih dahulu beserta trigger dan penerimanya.

---

# Delivery Channel

Versi awal:

- In App

Pengembangan berikutnya:

- Email
- WhatsApp
- Push Notification
- SMS
- Webhook

---

# Soft Delete

Notification menggunakan Soft Delete.

Notification yang telah dihapus pengguna tidak benar-benar dihapus dari database.

---

# Audit Trail

Catat aktivitas berikut.

- dikirim
- dibaca
- diarsipkan
- dipulihkan
- dihapus

---

# Business Rule

Notification memiliki minimal satu penerima.

Notification dibuat otomatis oleh sistem.

Notification tidak dapat dibuat manual oleh User.

Notification tidak boleh diubah setelah dikirim.

Notification boleh diarsipkan oleh pengguna.

Arsip tidak mengubah Status Notification (`UNREAD` atau `READ`); visibilitas arsip ditentukan oleh `archived_at`.

Notification untuk role Klien hanya boleh mereferensikan resource milik `users.client_id` dan tidak boleh mengarahkan ke route operasional.

---

# Validasi

Recipient wajib tersedia.

Title wajib tersedia.

Message wajib tersedia.

Type wajib dipilih.

Priority wajib tersedia.

---

# Index

Direkomendasikan:

- recipient_id
- project_id
- entity
- entity_id
- read_at
- archived_at
- priority
- sent_at
- deleted_at

---

# Integritas Data

Menghapus User tidak menghapus Notification.

Notification tetap tersedia sebagai histori komunikasi.

---

# Migration Recommendation

Contoh struktur minimal.

notifications

- id
- recipient_id
- project_id
- entity
- entity_id
- route
- type
- priority
- title
- message
- read_at
- archived_at
- sent_at
- created_at
- updated_at
- deleted_at

---

# Future Enhancement

Mendukung:

- Email Notification
- WhatsApp Gateway
- Push Notification
- Notification Template
- Scheduled Notification
- Notification Digest
- User Preference
- Multi Channel Delivery

---

# Hubungan Dokumen

Workflow

- workflow/*

Notification

- 08-notification.md

Status

- 07-status.md

Database

- 04-database.md
