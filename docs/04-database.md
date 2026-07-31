# 04. Database

## Tujuan

Dokumen ini menjelaskan arsitektur database PHC System.

Dokumen ini hanya menjelaskan struktur besar database, hubungan antar entitas, serta prinsip penyimpanan data.

Struktur detail setiap tabel dijelaskan pada folder `/database`.

---

# Referensi

Dokumen terkait:

- 00-overview.md
- 00-glossary.md
- 01-business.md
- 02-workflow.md
- 03-role-permission.md

Detail tabel:

- database/users.md
- database/leads.md
- database/clients.md
- database/partners.md
- database/projects.md
- database/project-assignments.md
- database/workflows.md
- database/invoices.md
- database/payments.md
- database/documents.md
- database/certificates.md
- database/sihalal-credentials.md
- database/tasks.md
- database/logs.md
- database/notifications.md

---

# Tujuan Database

Database digunakan sebagai sumber data utama (Single Source of Truth).

Seluruh aktivitas sistem harus tersimpan di database.

Tidak boleh ada data penting yang hanya disimpan di Spreadsheet, WhatsApp, atau media lain.

---

# Prinsip Database

Database mengikuti prinsip berikut.

## Project Centric

Seluruh data selalu terhubung dengan Project.

Project merupakan pusat relasi seluruh sistem.

Contoh:

Project

↓

Invoice

↓

Payment

↓

Document

↓

Notification

↓

Timeline

↓

Activity Log

---

## Normalisasi

Data tidak boleh diduplikasi.

Contoh:

Nama Marketing tidak disimpan pada tabel Invoice.

Invoice hanya menyimpan:

marketing_id

Relasi dilakukan melalui tabel User.

---

## Audit Trail

Setiap perubahan data wajib menghasilkan Activity Log.

Data histori tidak boleh dihapus.

---

## Soft Delete

Data penting tidak dihapus secara permanen.

Gunakan:

deleted_at

untuk menandai data yang dihapus.

---

## Timestamp

Seluruh tabel wajib memiliki:

created_at

updated_at

Apabila menggunakan Soft Delete:

deleted_at

---

# Struktur Database

Database dikelompokkan berdasarkan domain. Jumlah tabel tidak dijadikan kontrak karena tabel histori dan tabel package dapat bertambah tanpa menciptakan modul bisnis baru.

## User

Menyimpan akun pengguna.

Referensi:

database/users.md

---

## Lead

Menyimpan prospek sebelum dikonversi menjadi Client dan Project.

Referensi:

database/leads.md

---

## Client

Menyimpan data perusahaan atau yayasan.

Referensi:

database/clients.md

---

## Partner

Menyimpan master Mitra untuk Client bertipe Mitra.

Referensi:

database/partners.md

---

## Project

Menyimpan proses sertifikasi halal.

Seluruh modul terhubung ke tabel ini.

Referensi:

database/projects.md

---

## Assignment dan Workflow

Menyimpan PIC internal, status langkah, dan histori workflow.

Referensi:

- database/project-assignments.md
- database/workflows.md

---

## Payment

Modul Payment menggunakan dua entitas.

### Invoice

Menyimpan tagihan pembayaran.

Satu Project dapat memiliki banyak Invoice.

Referensi:

database/invoices.md

---

### Payment

Menyimpan histori pembayaran.

Satu Invoice dapat memiliki satu atau lebih pembayaran.

Referensi:

database/payments.md

---

## Document

Menyimpan seluruh lampiran Project.

Referensi:

database/documents.md

---

## Certificate dan SIHALAL Credential

Menyimpan metadata Sertifikat serta kredensial eksternal pada tabel khusus.

Referensi:

- database/certificates.md
- database/sihalal-credentials.md

---

## Notification

Menyimpan notifikasi sistem.

Referensi:

database/notifications.md

---

## Task

Menyimpan antrean pekerjaan yang dibuat otomatis oleh workflow.

Referensi:

database/tasks.md

---

## Activity Log

Menyimpan seluruh histori perubahan sistem.

Referensi:

database/logs.md

---

# Hubungan Antar Modul

User

↓

Lead

↓

Client

↓

Project

↓

├── Invoice

├── Payment

├── Document

├── Workflow

├── Timeline

├── Notification

└── Activity Log

---

# Entity Relationship

User

│

├──── membuat ────► Lead

│

├──── menangani ─► Project

│

└──── menerima ─► Notification



Lead

│

└──── menghasilkan ─► Client

Partner

│

└──── memiliki ─► Client bertipe Mitra



Client

│

└──── memiliki ─► Project



Project

│

├──── memiliki ─► Invoice

├──── memiliki ─► Document

├──── memiliki ─► Activity Log

├──── memiliki ─► Notification

└──── memiliki ─► Workflow

Client bertipe Mitra menghasilkan dua Invoice per event penagihan komersial, masing-masing untuk audience Client dan Partner, tetapi tetap berada dalam satu transaction group Project.



Invoice

│

└──── memiliki ─► Payment

---

# Primary Key

Seluruh tabel menggunakan UUID atau BIGINT sesuai implementasi.

ID yang ditampilkan kepada User bukan Primary Key.

Contoh:

Primary Key

```
id = 81
```

Ditampilkan menjadi

```
CLIENT-2026-0001
```

Nomor tersebut merupakan Business ID Client. Project tidak memiliki Business ID terpisah.

---

# Business ID

Client

```
CLIENT-YYYY-XXXX
```

Contoh

```
CLIENT-2026-0001
```

ID Client dibuat otomatis ketika Lead menjadi Deal dan menjadi Business ID utama untuk Client beserta Project tunggalnya. Project tetap memiliki UUID internal, tetapi tidak memiliki Business ID terpisah.

Invoice

```
INV/PHC/YYYY/XXXX-XX-A
```

Contoh

```
INV/PHC/2026/0001-01-C
```

Payment

```
PAY/PHC/YYYY/XXXXXX
```

Contoh

```
PAY/PHC/2026/000145
```

---

# Relasi Utama

Satu User

↓

Memiliki satu Role

---

Satu Lead

↓

Menghasilkan satu Client

---

Satu Client

↓

Memiliki tepat satu Project

---

Satu Project

↓

Memiliki banyak Dokumen

↓

Memiliki banyak Invoice

↓

Memiliki banyak Activity Log

↓

Memiliki banyak Notification

---

Satu Invoice

↓

Memiliki banyak Payment

---

# Activity Log

Seluruh perubahan berikut harus dicatat.

- Login
- Logout
- Create
- Update
- Delete
- Approve
- Reject
- Upload
- Download
- Generate Invoice
- Verifikasi Pembayaran
- Upload Sertifikat

Referensi:

database/logs.md

---

# Integritas Data

Database wajib menjaga integritas data.

Contoh:

Project tidak boleh dihapus apabila masih memiliki Invoice.

Invoice tidak boleh dihapus apabila sudah memiliki Payment.

User tidak boleh dihapus apabila masih menjadi PIC Project.

---

# Dokumentasi Detail

Dokumen ini hanya menjelaskan arsitektur.

Seluruh struktur tabel dijelaskan pada folder:

database/

Setiap perubahan struktur tabel harus dilakukan pada dokumen masing-masing.

Contoh:

Perubahan tabel Invoice

↓

database/invoices.md

Bukan pada dokumen ini.
