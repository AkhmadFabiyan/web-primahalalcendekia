# Workflow - Finance

## Tujuan

Dokumen ini menjelaskan proses kerja Finance dalam PHC System.

Seluruh pengelolaan Invoice dan transaksi Payment pada workflow ini diimplementasikan dalam satu modul Payment.

Finance bertanggung jawab mengelola seluruh transaksi keuangan Project, mulai dari Invoice Aktivasi, verifikasi pembayaran, Invoice Termin, Invoice Negara, hingga pelunasan Project.

Workflow ini dimulai setelah Marketing mengubah Lead menjadi Deal.

---

# Referensi

Dokumen terkait:

- 01-business.md
- 02-workflow.md
- 03-role-permission.md
- 07-status.md
- 08-notification.md

Database:

- database/invoices.md
- database/payments.md
- database/projects.md

UI:

- ui/invoice.md
- ui/pembayaran.md

---

# Aktor

Role utama:

- Finance

Role yang dapat melihat:

- Manager Operasional
- Direktur
- Super Admin

---

# Tujuan Bisnis

Finance bertugas:

- Memeriksa dan menerbitkan Invoice yang dibuat otomatis oleh workflow.
- Memverifikasi pembayaran.
- Mengelola pembayaran termin.
- Mengelola Invoice Negara.
- Memastikan seluruh pembayaran Project selesai.

---

# Trigger

Workflow dimulai ketika:

Lead

↓

Deal

↓

Project dibuat

↓

Finance menerima notifikasi.

---

# Workflow

Lead Deal

↓

Draft Invoice Aktivasi dibuat otomatis

↓

Finance memeriksa dan menerbitkan

↓

Invoice Dikirim

↓

Menunggu Pembayaran

↓

Verifikasi Pembayaran

↓

Project Aktif

↓

Workflow Operasional

↓

Invoice Termin dibuat otomatis (jika diperlukan)

↓

Invoice Negara tersedia dari proses Admin Perusahaan

↓

Verifikasi Pembayaran

↓

Pelunasan

↓

Project Selesai

---

# Detail Workflow

## 1. Memeriksa Invoice Aktivasi

Sistem membuat Invoice pertama secara otomatis ketika Lead menjadi Deal. Finance memeriksa data lalu menerbitkannya.

- Client Langsung menghasilkan satu Invoice Client.
- Client Mitra menghasilkan satu Invoice Client dan satu Invoice Mitra.
- Kedua Invoice Mitra menggunakan Project dan ID Client yang sama serta satu transaction group.
- Billing group Mitra dinyatakan lunas hanya jika Invoice Client dan Invoice Mitra sama-sama Lunas.

Rule pasangan yang sama berlaku untuk Invoice Termin dan Pelunasan. Invoice Negara tidak digandakan karena merupakan dokumen eksternal resmi.

Invoice berisi:

- Penerima Invoice: Client atau Mitra
- Nominal sesuai penerima
- Termin
- Jatuh Tempo
- Catatan

Status Invoice:

Draft

↓

Diterbitkan

---

## 2. Menunggu Pembayaran

Klien melakukan pembayaran.

Status Invoice:

Belum Bayar

atau

Sebagian

---

## 3. Verifikasi Pembayaran

Finance memverifikasi bukti pembayaran.

Apabila valid.

Status Payment

↓

Terverifikasi

Status Project

↓

Aktif

Workflow Admin dimulai.

---

## 4. Mengelola Invoice Termin

Apabila Project menggunakan lebih dari satu termin.

Sistem membuat draft Invoice berikutnya berdasarkan skema pembayaran. Finance memeriksa dan menerbitkannya ketika syarat workflow terpenuhi.

Contoh:

Termin 2

Termin 3

Termin 4

Jumlah Invoice mengikuti konfigurasi Project.

---

## 5. Mengelola Invoice Negara

Setelah Workflow Operasional selesai.

Admin Perusahaan mengunggah Invoice Negara resmi dari BPJPH.

Finance menerima dan mengelola Invoice tersebut untuk proses pembayaran ke BPJPH.

Setelah Invoice Negara diunggah, Status Project berubah menjadi:

Menunggu Sertifikat

---

## 6. Verifikasi Pembayaran Negara

Setelah pembayaran berhasil.

Status Payment berubah menjadi:

Terverifikasi

Status Invoice Negara berubah menjadi:

Lunas

Status Project tetap Menunggu Sertifikat sampai sertifikat diunggah.

---

## 7. Pelunasan

Sistem membuat draft Invoice terakhir apabila masih terdapat sisa pembayaran. Finance memeriksa dan menerbitkannya.

Setelah seluruh Invoice lunas.

Apabila Sertifikat telah terbit, Status Project berubah menjadi:

Selesai

Apabila masih ada Invoice lain yang belum lunas setelah Sertifikat terbit, Status Project menjadi Menunggu Pelunasan.

---

# Output

Invoice dibuat.

↓

Pembayaran diverifikasi.

↓

Project aktif.

↓

Project selesai.

---

# Business Rule

Invoice hanya dapat dibuat apabila Project tersedia.

Invoice Aktivasi wajib dibuat sebelum Workflow Operasional dimulai.

Finance tidak dapat mengubah Workflow.

Finance tidak dapat mengunggah Sertifikat.

Status Project berubah otomatis berdasarkan pembayaran yang telah diverifikasi.

---

# Validasi

Nominal lebih dari nol.

Untuk Client Mitra:

- Nominal Client dan Nominal Mitra wajib tersedia.
- Invoice Client menggunakan Nominal Client.
- Invoice Mitra menggunakan Nominal Mitra.
- `discount_total` wajib nol pada kedua Invoice.
- Pasangan Invoice tidak boleh dihitung sebagai dua transaksi pada KPI.
- Project Mitra baru aktif setelah kedua Invoice Aktivasi pada billing group berstatus Lunas.

Tanggal jatuh tempo wajib diisi.

Jenis Invoice wajib dipilih.

Project wajib tersedia. Status Aktif hanya diwajibkan untuk Invoice setelah Invoice Aktivasi.

---

# Jenis Invoice

PHC System mendukung beberapa jenis Invoice.

- Invoice Aktivasi
- Invoice Termin
- Invoice Negara
- Invoice Pelunasan

Jenis baru dapat ditambahkan apabila diperlukan.

---

# Pembayaran

Satu Invoice dapat memiliki lebih dari satu Payment.

Contoh.

Invoice

↓

Transfer pertama

↓

Transfer kedua

↓

Transfer ketiga

↓

Lunas

---

# Status Invoice

Draft

↓

Diterbitkan

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

# Status Payment

Menunggu Verifikasi

↓

Terverifikasi

atau

↓

Ditolak

Referensi:

07-status.md

---

# Exception

Nominal tidak sesuai.

↓

Payment ditolak.

---

Invoice telah lunas.

↓

Tidak dapat menerima Payment baru.

---

Invoice dibatalkan.

↓

Tidak dapat diverifikasi.

---

Project dibatalkan.

↓

Sistem tidak menghasilkan Invoice baru.

---

# Activity Log

Catat aktivitas berikut.

- Memeriksa Invoice
- Mengubah Invoice
- Menerbitkan Invoice
- Membatalkan Invoice
- Verifikasi Payment
- Menolak Payment

Referensi:

database/logs.md

---

# Notification

Lead Deal

↓

Finance menerima notifikasi.

---

Invoice diterbitkan

↓

Marketing menerima notifikasi.

---

Pembayaran diverifikasi

↓

Admin menerima notifikasi.

---

Workflow selesai

↓

Finance menerima notifikasi.

Referensi:

08-notification.md

---

# KPI

Finance dapat dimonitor berdasarkan:

- Jumlah Invoice
- Jumlah Payment
- Total Nilai Invoice
- Invoice Lunas
- Invoice Jatuh Tempo
- Outstanding Payment
- Waktu Verifikasi Pembayaran

---

# Hubungan Workflow

Workflow ini menerima hasil dari:

workflow/marketing.md

Workflow ini memicu:

workflow/admin.md

Dokumen ini hanya menjelaskan proses Finance.
