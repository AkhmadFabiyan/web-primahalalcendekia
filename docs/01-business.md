# 01. Business

## Tujuan

Dokumen ini menjelaskan proses bisnis (Business Process) pada PHC System.

Dokumen ini hanya membahas proses bisnis tingkat tinggi dan tidak membahas implementasi teknis seperti database, API, routing, maupun UI.

---

# Referensi

Dokumen terkait:

- 00-overview.md
- 00-glossary.md
- 02-workflow.md
- 03-role-permission.md
- 07-status.md

---

# Gambaran Sistem

PHC System merupakan sistem operasional internal Prima Halal Cendekia yang digunakan untuk mengelola seluruh proses sertifikasi halal mulai dari Lead hingga Project selesai.

Seluruh aktivitas sistem berpusat pada Project.

Workflow dikontrol menggunakan status sistem sehingga setiap divisi mengetahui pekerjaan yang harus dilakukan tanpa perlu berkomunikasi secara manual.

---

# Tujuan Bisnis

PHC System dibuat untuk:

- Menyatukan seluruh data klien dalam satu sistem.
- Mengurangi penggunaan spreadsheet manual.
- Mengurangi human error.
- Mengontrol workflow antar divisi.
- Mempermudah monitoring progres Project.
- Menyediakan histori aktivitas yang lengkap.
- Mempermudah pembuatan laporan operasional.

---

# Aktor Bisnis

Sistem memiliki beberapa aktor utama.

| Aktor | Tanggung Jawab |
|--------|----------------|
| Marketing | Mencari Lead dan melakukan Deal |
| Finance | Mengelola Invoice dan Pembayaran |
| Admin | Mengelola dokumen Project |
| Entry | Melakukan Entry SIHALAL |
| SPV Entry | Melakukan Review Entry |
| Pendamping Auditor | Menjadwalkan dan mendampingi Audit |
| Auditor | Memvalidasi hasil Audit |
| Admin Perusahaan | Upload Invoice Negara dan Sertifikat |
| Direktur | Monitoring |
| Klien | Melihat data dan progres Project miliknya melalui Dashboard |

Detail hak akses dijelaskan pada:

03-role-permission.md

---

# Siklus Bisnis

PHC System memiliki lima fase utama.

## 1. Akuisisi

Marketing membuat Lead.

Output:

Lead

↓

Deal

Referensi:

workflow/marketing.md

---

## 2. Aktivasi

Sistem membuat draft Invoice Aktivasi otomatis setelah Lead Deal. Finance memeriksa dan menerbitkannya.

Project belum dapat diproses sebelum pembayaran diverifikasi.

Output:

Project Aktif

Referensi:

workflow/finance.md

---

## 3. Operasional

Setelah Project aktif, sistem menjalankan dua workflow secara paralel.

Workflow A

- Administrasi
- Entry SIHALAL
- Review SPV

Workflow B

- Booking Audit
- Pendampingan Audit
- Review Auditor

Kedua workflow harus selesai sebelum melanjutkan ke tahap berikutnya.

Referensi:

02-workflow.md

---

## 4. Penyelesaian

Admin Perusahaan mengunggah Invoice Negara.

Finance melakukan proses pembayaran sesuai kebutuhan.

Setelah proses negara selesai:

- Upload Sertifikat
- Input Nomor Sertifikat

Referensi:

workflow/sertifikat.md

---

## 5. Penutupan

Finance menyelesaikan seluruh pembayaran.

Project diubah menjadi:

Selesai

---

# Workflow Paralel

PHC System menggunakan dua workflow utama.

Workflow A

Admin

↓

Entry

↓

SPV Entry

Workflow B

Pendamping Auditor

↓

Auditor

Kedua workflow berjalan secara bersamaan.

Tahapan berikutnya hanya dapat dimulai apabila kedua workflow telah berstatus Selesai.

---

# Model Pembayaran

Invoice dan Payment berada dalam satu modul Pembayaran.

Invoice merupakan tagihan, sedangkan Payment merupakan transaksi pembayaran terhadap tagihan tersebut. Keduanya tetap menggunakan entitas database terpisah agar histori dan rekonsiliasi terjaga.

PHC System tidak membatasi jumlah pembayaran.

Satu Project dapat memiliki:

- 1 Termin
- 2 Termin
- 3 Termin
- 4 Termin
- atau lebih

Setiap Termin menghasilkan satu Invoice.

Nominal setiap Invoice berasal dari data komersial Project dan diperiksa oleh Finance.

## Skema Klien Langsung

Client bertipe Langsung menggunakan satu Nominal Klien dan satu Invoice untuk setiap event penagihan komersial.

## Skema Klien Mitra

Client bertipe Mitra terhubung ke satu Partner.

Setiap event penagihan komersial menghasilkan:

- satu Invoice Client berdasarkan Nominal Klien
- satu Invoice Mitra berdasarkan Nominal Mitra

Kedua Invoice memiliki `project_id` dan ID Client yang sama. Pada KPI dan laporan, pasangan tersebut dihitung sebagai satu transaksi menggunakan Project sebagai transaction group.

Diskon tidak digunakan pada kedua Invoice dalam skema Mitra. Selisih nilai komersial dikelola melalui Nominal Mitra dan `discount_total` tetap nol.

Satu billing group Mitra dianggap lunas setelah Invoice Client dan Invoice Mitra sama-sama Lunas. Aktivasi dan penutupan Project menunggu seluruh Invoice wajib pada billing group terkait selesai.

Referensi:

database/invoices.md

database/payments.md

---

# Model Project

Satu Lead hanya dapat menghasilkan satu Client.

Satu Client memiliki tepat satu Project.

Seluruh aktivitas sistem selalu terhubung ke Project.

Project menjadi pusat relasi seluruh modul.

Project tidak memiliki Business ID yang ditampilkan. Seluruh tampilan operasional menggunakan ID Client yang dibuat otomatis saat Lead berubah menjadi Deal.

Contoh:

Project

↓

Pembayaran

(Invoice + Payment)

↓

Document

↓

Timeline

↓

Notification

↓

Activity Log

---

# Prinsip Bisnis

## Workflow Driven

Seluruh proses dikontrol oleh Workflow.

---

## Project Centric

Seluruh data terhubung dengan Project.

---

## Modular

Setiap divisi bekerja pada modul masing-masing.

---

## Role Based

Hak akses ditentukan berdasarkan Role.

---

## Activity Log

Seluruh perubahan data dicatat oleh sistem.

Data histori tidak boleh dihapus.

---

## Single Source of Truth

PHC System menjadi sumber data utama.

Spreadsheet, chat, atau dokumen lain bukan merupakan sumber data resmi.

---

# Batasan Sistem

PHC System tidak melakukan Entry SIHALAL secara langsung.

PHC System tidak menerbitkan Sertifikat Halal.

PHC System tidak terhubung langsung dengan sistem BPJPH.

PHC System hanya mengelola workflow, data, dan monitoring proses internal.

---

# Diagram Proses Bisnis

Lead

↓

Deal

↓

Invoice Aktivasi

↓

Pembayaran Diverifikasi

↓

Project Aktif

↓

━━━━━━━━━━━━━━━━━━━━━━

Workflow A

Admin

↓

Entry

↓

SPV

━━━━━━━━━━━━━━━━━━━━━━

||

━━━━━━━━━━━━━━━━━━━━━━

Workflow B

Pendamping

↓

Auditor

━━━━━━━━━━━━━━━━━━━━━━

↓

Invoice Negara

↓

Upload Sertifikat

↓

Pelunasan

↓

Project Selesai

---

# Dokumen Selanjutnya

Dokumen ini menjadi dasar bagi:

- 02-workflow.md
- 03-role-permission.md
- 04-database.md
- 05-api.md
- 06-routing.md
