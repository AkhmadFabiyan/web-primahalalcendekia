# Database - Payments

## Tujuan

Dokumen ini menjelaskan entitas **Payments** pada modul Payment PHC System.

Payment merupakan transaksi pembayaran terhadap suatu Invoice.

Satu Invoice dapat memiliki satu atau lebih Payment.

Modul Payment mencakup entitas Invoice dan Payment. Dokumen ini hanya menjelaskan struktur dan aturan entitas Payment; struktur Invoice dijelaskan pada `database/invoices.md`.

Arsitektur database dijelaskan pada:

- 04-database.md

---

# Referensi

Dokumen terkait

- 04-database.md
- 07-status.md

Workflow

- workflow/finance.md
- workflow/pembayaran.md

UI

- ui/pembayaran.md

---

# Tujuan Entitas

Payments digunakan untuk:

- mencatat transaksi pembayaran
- menyimpan bukti pembayaran
- mencatat proses verifikasi
- menghitung total pembayaran Invoice
- menjadi dasar laporan keuangan

Payment tidak menentukan status Project secara langsung.

Payment memengaruhi status Invoice setelah diverifikasi.

Payment hanya dapat ditampilkan pada Dashboard Klien apabila Invoice dan Project terkait berelasi dengan `users.client_id`.

---

# Tanggung Jawab

Payment bertanggung jawab menyimpan:

- transaksi pembayaran
- nominal pembayaran
- tanggal pembayaran
- bukti pembayaran
- hasil verifikasi

---

# Struktur Data

## Primary Key

id

UUID

---

## Business Identifier

Opsional.

Apabila digunakan disarankan:

PAY/YYYY/XXXXXX

Contoh:

PAY/2026/000001

Identifier digunakan untuk pencarian dan audit.

---

## Informasi Dasar

Minimal terdiri dari:

- invoice_id
- payment_number
- payment_date
- amount
- payment_method
- reference_number
- notes

---

## Bukti Pembayaran

Payment dapat memiliki:

- file bukti transfer
- foto bukti pembayaran
- dokumen pendukung

File disimpan melalui Spatie Media Library pada collection `payment-proofs`. Tabel Payments tidak menyimpan path atau metadata fisik file.

---

## Verifikasi

Minimal terdiri dari:

- verified_by
- verified_at
- verification_notes

---

## Status

Status mengikuti:

07-status.md

Nilai enum:

- PENDING
- VERIFIED
- REJECTED

Label UI mengikuti pemetaan pada `07-status.md`.

---

# Relasi

Payment

N

↓

1

Invoice

---

Payment

N

↓

1

User (Verifier)

---

Payment

1

↓

N

Activity Logs

---

# Cardinality

Invoice

1

↓

N

Payment

---

User

1

↓

N

Payment Verification

---

# Payment Method

Contoh:

- Bank Transfer
- Virtual Account
- Cash
- QRIS
- Payment Gateway

Daftar metode dapat dikonfigurasi.

---

# Partial Payment

Satu Invoice dapat menerima beberapa Payment.

Contoh:

Invoice

15.000.000

↓

Payment

5.000.000

↓

Payment

5.000.000

↓

Payment

5.000.000

↓

Invoice Paid

---

# Verifikasi

Payment baru tidak langsung dianggap sah.

Workflow:

Payment

↓

Pending

↓

Finance Review

↓

Verified

atau

↓

Rejected

---

# Soft Delete

Payment menggunakan Soft Delete.

Payment yang telah diverifikasi tidak boleh dihapus permanen.

---

# Audit Trail

Catat aktivitas berikut.

- dibuat
- upload bukti
- diverifikasi
- ditolak
- diperbarui

---

# Business Rule

Payment wajib memiliki Invoice.

Payment wajib memiliki nominal.

Payment wajib memiliki tanggal pembayaran.

Payment yang telah Verified tidak boleh diubah.

Payment yang Rejected tidak dihitung sebagai pembayaran.

Invoice dianggap lunas berdasarkan total Payment berstatus Verified.

---

# Validasi

Invoice wajib tersedia.

Nominal lebih dari nol.

Tanggal pembayaran wajib diisi.

Status wajib tersedia.

Bukti pembayaran wajib tersedia.

Verifier wajib tersedia ketika status menjadi Verified.

---

# Index

Direkomendasikan

- invoice_id
- payment_date
- status
- verified_by
- payment_method
- deleted_at

---

# Integritas Data

Menghapus Payment tidak mengubah histori Invoice.

Payment yang telah diverifikasi tidak boleh dihapus.

Activity Log harus tetap tersedia.

---

# Migration Recommendation

Contoh struktur minimal.

payments

- id
- invoice_id
- payment_number
- payment_date
- amount
- payment_method
- reference_number
- status
- verification_notes
- verified_by
- verified_at
- created_at
- updated_at
- deleted_at

Media collection:

- `payment-proofs`

---

# Future Enhancement

Mendukung:

- Payment Gateway
- Auto Verification
- Virtual Account
- QRIS
- Refund
- Multi Currency
- Rekonsiliasi Bank
- Integrasi ERP

---

# Hubungan Dokumen

Workflow

- workflow/finance.md
- workflow/pembayaran.md

Status

- 07-status.md

Database

- 04-database.md
