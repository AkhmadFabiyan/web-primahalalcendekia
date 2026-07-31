# UI - Pembayaran (Invoice & Payment)

## Tujuan

Dokumen ini menjelaskan modul tunggal **Pembayaran (Payment)** pada PHC System.

Modul ini digunakan untuk membuat serta mengelola Invoice, kemudian mencatat, memverifikasi, dan memonitor transaksi pembayarannya.

Setiap Payment selalu terkait dengan satu Invoice.

Dokumen ini hanya menjelaskan antarmuka (UI) dan pengalaman pengguna (UX).

Routing dijelaskan pada:

- 06-routing.md

Hak akses dijelaskan pada:

- 03-role-permission.md

Workflow dijelaskan pada:

- workflow/finance.md
- workflow/pembayaran.md

---

# Referensi

Dokumen terkait

- 03-role-permission.md
- 04-database.md
- 06-routing.md
- 07-status.md

Workflow

- workflow/finance.md
- workflow/pembayaran.md

Database

- database/payments.md
- database/invoices.md

---

# Tujuan Halaman

Halaman Pembayaran digunakan untuk:

- melihat, membuat, dan menerbitkan Invoice
- memonitor status serta nilai Invoice
- melihat daftar pembayaran
- mengunggah bukti pembayaran
- memverifikasi pembayaran
- menolak pembayaran
- melihat histori pembayaran
- memonitor pembayaran yang menunggu verifikasi

Invoice bukan halaman atau modul navigasi terpisah; Invoice tersedia sebagai tab dan subroute di dalam halaman Pembayaran.

---

# Hak Akses

Role yang dapat mengakses:

- Finance
- Super Admin

Hak akses:

Finance

- Membuat dan menerbitkan Invoice
- Mengelola termin
- Upload Payment
- Verifikasi
- Reject

Direktur dan Manager Operasional

- View Only

Super Admin

- Full Access

Marketing dan Admin Perusahaan

- View Only pada tab Invoice

Role Klien tidak mengakses halaman ini; ringkasan Invoice dan Payment miliknya ditampilkan melalui `/dashboard`.

---

# Struktur Halaman

Halaman terdiri dari:

1. Header
2. Tab Invoice
3. Tab Transaksi Payment
4. Toolbar
5. Filter
6. Invoice Table atau Payment Table sesuai tab aktif
7. Drawer Detail
8. Form Invoice atau Verification Panel sesuai konteks

---

# Navigasi Tab

Modul menggunakan dua tab:

- Invoice — `/payments/invoices`
- Transaksi Payment — `/payments/transactions`

Perpindahan tab tidak berpindah modul dan menu sidebar Pembayaran tetap aktif.

---

# Header

Menampilkan:

- Judul Halaman
- Total Invoice
- Total Outstanding
- Total Payment
- Total Pending
- Total Verified

---

# Toolbar

Berisi:

- Search
- Filter
- Refresh
- Export

Modul tidak menampilkan Create, Delete, atau Bulk Action. Invoice dibuat otomatis oleh workflow.

---

# Filter

Filter yang tersedia:

- Status
- Audience Invoice
- Tipe Klien
- Mitra
- Metode Pembayaran
- Finance
- Periode
- Project
- Client

---

# Search

Pencarian berdasarkan:

- Nomor Payment
- Nomor Invoice
- ID Klien
- Nama Perusahaan
- Nomor Referensi

---

# Payment Table

Kolom utama:

- Payment ID
- Invoice
- Project
- Client
- Nominal
- Metode
- Payment Date
- Status
- Verified By
- Action

---

# Action

Setiap baris memiliki aksi:

- Lihat
- Verifikasi
- Tolak
- Download Bukti

Action mengikuti Status.

---

# Drawer Detail

Drawer terdiri dari beberapa tab.

---

## Tab Ringkasan

Menampilkan:

- Payment ID
- Invoice
- Project
- Client
- Nominal
- Metode Pembayaran
- Nomor Referensi
- Status

---

## Tab Bukti Pembayaran

Menampilkan:

- Preview Bukti
- Download
- Zoom

Mendukung:

- PDF
- JPG
- PNG

---

## Tab Verifikasi

Menampilkan:

- Status
- Verifier
- Waktu Verifikasi
- Catatan

Action:

- Verify
- Reject

---

## Tab Timeline

Menampilkan:

- Payment Dibuat
- Bukti Diunggah
- Diverifikasi
- Ditolak

---

## Tab Activity

Menampilkan seluruh Audit Trail Payment.

---

# Form Payment

Minimal terdiri dari:

## Informasi Invoice

- Invoice
- Project

---

## Informasi Pembayaran

- Nominal
- Metode
- Nomor Referensi
- Tanggal Pembayaran

---

## Bukti Pembayaran

- Upload File

---

## Catatan

- Notes

---

# Verification Panel

Ketika status Pending.

Finance dapat:

- Verify
- Reject

Verifikasi memerlukan:

- Catatan (opsional)
- Konfirmasi

---

# Status Badge

Menggunakan label Badge:

- Menunggu Verifikasi
- Terverifikasi
- Ditolak

Referensi:

07-status.md

---

# Empty State

Belum ada Payment.

---

# Loading State

Gunakan Skeleton Table.

Drawer menggunakan Skeleton Detail.

---

# Error State

Tampilkan:

- pesan kesalahan
- tombol Muat Ulang

---

# Responsive

Desktop

Table + Drawer.

Tablet

Drawer Full Width.

Mobile

Card List.

---

# UX Guideline

Preview bukti pembayaran harus tersedia tanpa mengunduh file.

Status menggunakan Badge.

Nominal menggunakan format mata uang.

Verifikasi dilakukan dari Drawer.

Konfirmasi diperlukan sebelum Verify atau Reject.

---

# Business Rule

Payment harus memiliki Invoice.

Payment Pending belum dihitung sebagai pelunasan.

Payment Verified akan memperbarui status Invoice.

Payment Rejected tidak dihitung.

Payment yang telah Verified tidak dapat diubah.

Payment tetap direkonsiliasi per Invoice. Pada Client Mitra, dua Invoice ditampilkan dalam satu transaction group Project dan tidak menambah hitungan transaksi bisnis.

---

# Future Enhancement

Mendukung:

- QRIS
- Payment Gateway
- Virtual Account
- Auto Verification
- OCR Bukti Transfer
- Rekonsiliasi Bank
- Multi Currency
- Refund

---

# Hubungan Dokumen

Workflow

- workflow/finance.md
- workflow/pembayaran.md

Database

- database/payments.md
- database/invoices.md

UI

- ui/invoice.md
- ui/klien.md
