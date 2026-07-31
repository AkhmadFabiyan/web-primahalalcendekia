# Database - Invoices

## Tujuan

Dokumen ini menjelaskan entitas **Invoices** di dalam modul Payment pada PHC System.

Invoice merupakan dokumen penagihan yang diterbitkan untuk suatu Project.

Satu Project dapat memiliki satu atau lebih Invoice sesuai skema pembayaran yang disepakati.

Invoice bukan modul terpisah. Dokumen ini hanya menjelaskan struktur dan aturan entitas Invoice di dalam modul Payment.

Arsitektur database dijelaskan pada:

- 04-database.md

---

# Referensi

Dokumen terkait

- 01-business.md
- 04-database.md
- 07-status.md

Workflow

- workflow/finance.md
- workflow/pembayaran.md
- workflow/sertifikat.md

UI

- ui/invoice.md

---

# Tujuan Entitas

Invoices digunakan untuk:

- mencatat tagihan Project
- mengelompokkan pembayaran
- menghitung piutang
- menghasilkan nomor invoice
- menjadi dasar laporan keuangan

Invoice tidak menyimpan histori pembayaran.

Invoice hanya dapat ditampilkan pada Dashboard Klien apabila Project Invoice berelasi dengan `users.client_id`.

Invoice tidak menyimpan bukti transfer.

---

# Tanggung Jawab

Invoice bertanggung jawab menyimpan:

- informasi tagihan
- jenis invoice
- nominal
- jatuh tempo
- status invoice

Detail pembayaran disimpan pada tabel Payments.

---

# Struktur Data

## Primary Key

id

UUID

---

## Business Identifier

Format:

INV/PHC/YYYY/XXXX-XX-A

Contoh

INV/PHC/2026/0001-01-C

INV/PHC/2026/0001-01-P

Keterangan:

- XXXX = nomor urut dari ID Client
- XX = urutan invoice milik Client
- A = audience Invoice: `C` untuk Client atau `P` untuk Partner

Contoh

Client

CLIENT-2026-0001

Invoice Client

INV/PHC/2026/0001-01-C

Invoice Partner

INV/PHC/2026/0001-01-P

Business ID wajib unik.

---

## Informasi Dasar

Minimal terdiri dari:

- project_id
- billing_group_id
- invoice_number
- invoice_type
- audience
- partner_id (nullable)
- billing_snapshot (JSON)
- sequence
- subtotal
- discount_total
- total
- due_date
- issued_at

---

## Jenis Invoice

Menggunakan enum.

| Kode Enum | Label UI |
|---|---|
| `ACTIVATION` | Invoice Aktivasi |
| `INSTALLMENT` | Invoice Termin |
| `GOVERNMENT` | Invoice Negara |
| `SETTLEMENT` | Invoice Pelunasan |

Referensi:

workflow/pembayaran.md

---

## Audience Invoice

| Kode | Label UI | Rule |
|---|---|---|
| `CLIENT` | Klien | Wajib untuk semua Tipe Klien |
| `PARTNER` | Mitra | Hanya untuk Client bertipe Mitra |

Rule:

- `partner_id` wajib kosong untuk audience Client.
- `partner_id` wajib tersedia dan sama dengan `clients.partner_id` untuk audience Partner.
- Client Langsung hanya menghasilkan audience Client.
- Client Mitra menghasilkan pasangan audience Client dan Partner pada `billing_group_id` yang sama.
- `billing_snapshot` menyimpan nama, alamat, PIC, dan kontak penerima pada saat Invoice diterbitkan agar dokumen historis tidak berubah ketika master diperbarui.

Dengan demikian, setiap commercial billing event Client Mitra memiliki tepat dua record: satu Invoice Client dan satu Invoice Partner.

Rule pasangan berlaku untuk `ACTIVATION`, `INSTALLMENT`, dan `SETTLEMENT`. Invoice `GOVERNMENT` merupakan dokumen eksternal resmi dan tidak otomatis digandakan menjadi Invoice Partner.

---

## Status

Status mengikuti:

07-status.md

Nilai enum:

- DRAFT
- PUBLISHED
- PARTIAL
- PAID
- CANCELLED

Label UI mengikuti pemetaan pada `07-status.md`.

---

# Relasi

Invoice

N

↓

1

Project

---

Invoice

N

↓

1

Partner

(Khusus audience Partner)

---

Invoice

1

↓

N

Payments

---

Invoice

1

↓

N

Activity Logs

---

# Cardinality

Project

1

↓

N

Invoice

---

Invoice

1

↓

N

Payment

---

# Nomor Invoice

Nomor Invoice dibuat otomatis.

Format mengikuti standar perusahaan.

Nomor Invoice tidak boleh berubah setelah diterbitkan.

---

# Nominal

Invoice menyimpan:

- subtotal
- discount_total
- total

`total = subtotal - discount_total`.

`total` menggunakan generated column atau dihitung oleh domain service dan tidak pernah diterima sebagai input request. `subtotal` dan `discount_total` menjadi sumber perhitungan.

Untuk Client Mitra:

- Invoice Client menggunakan `projects.client_nominal`.
- Invoice Partner menggunakan `projects.partner_nominal`.
- `discount_total` wajib `0` pada kedua Invoice.
- Selisih nilai tidak dicatat sebagai diskon.

Untuk Client Langsung, discount dapat digunakan hanya apabila kebijakan bisnis mengizinkan.

---

# Payment

Invoice tidak menyimpan:

- payment_date
- payment_method
- payment_reference
- payment_proof

Seluruh data pembayaran disimpan pada:

database/payments.md

---

# Soft Delete

Invoice menggunakan Soft Delete.

Invoice yang telah memiliki Payment tidak boleh dihapus.

Invoice yang sudah Published tidak boleh dihapus permanen.

---

# Audit Trail

Catat aktivitas berikut.

- dibuat
- diterbitkan
- diubah
- dibatalkan
- lunas

---

# Business Rule

Invoice harus memiliki Project.

Invoice harus memiliki nominal.

Invoice yang telah Published tidak dapat mengubah nomor invoice.

Invoice yang telah Paid tidak dapat diubah.

Invoice yang Cancelled tidak menerima Payment.

Invoice Government hanya dapat dibuat setelah Workflow Operasional selesai.

Pasangan Invoice Mitra wajib memiliki `project_id`, `billing_group_id`, `invoice_type`, dan `sequence` yang sama tetapi audience berbeda.

Jumlah transaksi tidak dihitung dari jumlah Invoice. KPI transaction count menggunakan jumlah Project unik.

Status billing group dihitung dari seluruh Invoice wajib di dalamnya. Billing group Mitra hanya Lunas apabila Invoice audience Client dan Partner sama-sama `PAID`.

---

# Validasi

Project wajib tersedia.

Nominal lebih dari nol.

Tanggal jatuh tempo wajib tersedia.

Jenis Invoice wajib dipilih.

Audience Invoice wajib dipilih.

Sequence unik berdasarkan kombinasi Project, jenis, sequence, dan audience.

`discount_total` wajib nol untuk Client Mitra.

---

# Index

Direkomendasikan

- invoice_number
- project_id
- invoice_type
- status
- due_date
- deleted_at

---

# Integritas Data

Menghapus Invoice tidak menghapus:

- Payment
- Activity Log

Invoice yang memiliki Payment tidak boleh dihapus.

---

# Migration Recommendation

Contoh struktur minimal.

invoices

- id
- project_id
- invoice_number
- invoice_type
- billing_group_id
- audience
- partner_id
- billing_snapshot
- sequence
- subtotal
- discount_total
- total
- status
- issued_at
- due_date
- published_at
- cancelled_at
- created_at
- updated_at
- deleted_at

Constraint yang direkomendasikan:

- unique `invoice_number`
- unique (`project_id`, `invoice_type`, `sequence`, `audience`)
- index `billing_group_id`
- index `partner_id`

Invariant `discount_total = 0` untuk Project bertipe Mitra divalidasi oleh domain service karena bergantung pada relasi Client.

---

# Future Enhancement

Mendukung:

- Tax
- Credit Note
- Debit Note
- Multi Currency
- PDF Generator
- Digital Signature
- Payment Gateway

---

# Hubungan Dokumen

Workflow

- workflow/finance.md
- workflow/pembayaran.md

Status

- 07-status.md

Database

- 04-database.md
