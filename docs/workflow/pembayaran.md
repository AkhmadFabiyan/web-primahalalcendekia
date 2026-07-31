# Workflow - Pembayaran

## Tujuan

Dokumen ini menjelaskan proses pembayaran pada PHC System.

Invoice dan transaksi Payment pada alur ini merupakan fitur dalam satu modul Payment, bukan dua modul terpisah.

Workflow pembayaran mengatur seluruh siklus keuangan Project, mulai dari Invoice Aktivasi, pembayaran bertahap (termin), Invoice Negara, hingga pelunasan.

Dokumen ini tidak menjelaskan pekerjaan Finance secara detail. Tanggung jawab Finance dijelaskan pada:

- workflow/finance.md

---

# Referensi

Dokumen terkait

- 01-business.md
- 02-workflow.md
- 05-api.md
- 07-status.md

Database

- database/invoices.md
- database/payments.md
- database/projects.md

UI

- ui/invoice.md
- ui/pembayaran.md

---

# Tujuan Bisnis

Workflow pembayaran dibuat agar:

- seluruh transaksi tercatat
- pembayaran dapat dilakukan bertahap
- setiap invoice memiliki histori pembayaran
- status pembayaran dapat dipantau
- project hanya selesai apabila kewajiban pembayaran telah dipenuhi

---

# Konsep Pembayaran

Model pembayaran terdiri dari dua entitas utama.

Project

↓

Invoice

↓

Payment

Project dapat memiliki banyak Invoice.

Invoice dapat memiliki banyak Payment.

Untuk Client Mitra, satu event penagihan komersial menghasilkan dua Invoice pada satu `billing_group_id`:

- Invoice Client berdasarkan Nominal Client
- Invoice Mitra berdasarkan Nominal Mitra

Kedua Invoice memiliki Project dan ID Client yang sama serta dihitung sebagai satu transaksi bisnis.

---

# Jenis Invoice

PHC System mendukung beberapa jenis Invoice.

## Invoice Aktivasi

Invoice pertama.

Menjadi syarat Project aktif.

---

## Invoice Termin

Digunakan apabila pembayaran dilakukan beberapa tahap.

Jumlah termin bersifat dinamis.

---

## Invoice Negara

Digunakan untuk pembayaran biaya negara.

Diterbitkan setelah Workflow Operasional selesai.

---

## Invoice Pelunasan

Invoice terakhir apabila masih terdapat sisa tagihan.

---

# Workflow

Lead Deal

↓

Invoice Aktivasi

↓

Pembayaran

↓

Verifikasi

↓

Project Aktif

↓

Workflow Operasional

↓

Invoice Termin (opsional)

↓

Pembayaran

↓

Invoice Negara

↓

Pembayaran Negara

↓

Upload Sertifikat

↓

Invoice Pelunasan (jika ada)

↓

Project Selesai

---

# Invoice Lifecycle

Draft

↓

Diterbitkan

↓

Belum Bayar

↓

Sebagian

↓

Lunas

atau

↓

Dibatalkan

Referensi:

07-status.md

---

# Payment Lifecycle

Menunggu Verifikasi

↓

Terverifikasi

atau

↓

Ditolak

Referensi:

07-status.md

---

# Detail Workflow

## 1. Menghasilkan Invoice

Sistem menghasilkan draft Invoice berdasarkan event workflow dan skema pembayaran. Finance memeriksa serta menerbitkannya.

Invoice terdiri dari:

- Jenis Invoice
- Audience Invoice
- Billing Group
- Nominal
- Termin
- Tanggal Terbit
- Jatuh Tempo

---

## 2. Publish Invoice

Invoice diterbitkan.

Status berubah menjadi:

Belum Bayar

---

## 3. Pembayaran

Klien melakukan pembayaran.

Satu Invoice dapat menerima lebih dari satu Payment.

Contoh.

Invoice

↓

Transfer 1

↓

Transfer 2

↓

Transfer 3

↓

Lunas

---

## 4. Verifikasi

Finance melakukan verifikasi.

Apabila valid.

↓

Payment

Terverifikasi

Nominal terbayar diperbarui.

---

## 5. Invoice Lunas

Apabila total Payment sama dengan nilai Invoice.

↓

Status Invoice

Lunas

---

## 6. Pelunasan Project

Project dianggap selesai apabila:

- seluruh workflow selesai
- seluruh invoice telah lunas

---

# Business Rule

Invoice hanya dapat dibuat pada Project yang valid.

Invoice tidak boleh bernilai nol.

Invoice yang sudah lunas tidak dapat diubah.

Invoice yang dibatalkan tidak dapat menerima Payment.

Payment harus diverifikasi sebelum memengaruhi status Invoice.

Project tidak dapat selesai apabila masih terdapat Invoice yang belum lunas.

Untuk Client Mitra:

- setiap billing group wajib memiliki satu Invoice Client dan satu Invoice Mitra
- `discount_total` kedua Invoice wajib nol
- selisih nominal tidak dicatat sebagai diskon
- KPI transaction count menggunakan Project unik, bukan jumlah Invoice
- billing group dinyatakan Lunas setelah Invoice Client dan Invoice Mitra sama-sama Lunas
- aktivasi atau pelunasan Project menunggu seluruh Invoice wajib pada billing group selesai

---

# Pembayaran Bertahap

PHC System mendukung pembayaran parsial.

Contoh.

Invoice

10.000.000

Payment 1

2.500.000

↓

Payment 2

5.000.000

↓

Payment 3

2.500.000

↓

Invoice Lunas

---

# Overpayment

Apabila total Payment melebihi nilai Invoice.

↓

Payment ditolak

atau

↓

kelebihan pembayaran diproses sesuai kebijakan perusahaan.

---

# Underpayment

Apabila Payment lebih kecil dari nilai Invoice.

↓

Status Invoice

Sebagian

---

# Validasi

Nominal Payment harus lebih dari nol.

Invoice harus aktif.

Invoice tidak boleh dibatalkan.

Tanggal pembayaran wajib tersedia.

Bukti pembayaran wajib diunggah.

---

# Exception

Invoice tidak ditemukan.

↓

Payment ditolak.

---

Invoice dibatalkan.

↓

Tidak dapat menerima pembayaran.

---

Invoice telah lunas.

↓

Payment baru tidak diperbolehkan.

---

# Activity Log

Catat aktivitas berikut.

- Menghasilkan Invoice otomatis.
- Mengubah Invoice.
- Menerbitkan Invoice.
- Upload Bukti Pembayaran.
- Verifikasi Payment.
- Menolak Payment.
- Invoice Lunas.

Referensi:

database/logs.md

---

# Notification

Invoice diterbitkan

↓

Klien menerima notifikasi.

---

Payment masuk

↓

Finance menerima notifikasi.

---

Payment diverifikasi

↓

Admin menerima notifikasi.

↓

Marketing menerima notifikasi.

---

Invoice lunas

↓

Manager Operasional menerima notifikasi.

Referensi:

08-notification.md

---

# KPI

Workflow pembayaran dapat dimonitor berdasarkan:

- Total Invoice
- Total Payment
- Outstanding Invoice
- Invoice Lunas
- Invoice Jatuh Tempo
- Nilai Piutang
- Lama Verifikasi Payment

---

# Hubungan Workflow

Workflow ini digunakan oleh:

- workflow/finance.md
- workflow/sertifikat.md

Dokumen ini menjadi referensi utama seluruh proses pembayaran pada PHC System.
