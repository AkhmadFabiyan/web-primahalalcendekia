# UI Detail - Invoice pada Modul Pembayaran

## Tujuan

Dokumen ini menjelaskan tab dan subroute **Invoice** di dalam modul Pembayaran pada PHC System.

Halaman ini digunakan untuk membuat, melihat, menerbitkan, dan mengelola seluruh Invoice Project.

Invoice selalu berhubungan dengan satu Project.

Invoice bukan modul atau menu sidebar terpisah. Dokumen ini hanya menjelaskan detail UI Invoice; shell halaman, navigasi utama, dan pengalaman modul dijelaskan pada `ui/pembayaran.md`.

Route:

- Parent: `/payments`
- Daftar Invoice: `/payments/invoices`
- Detail Invoice: `/payments/invoices/{invoiceId}`

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

- database/invoices.md
- database/payments.md

---

# Tujuan Halaman

Halaman Invoice digunakan untuk:

- melihat daftar Invoice
- memeriksa Invoice yang dibuat otomatis
- menerbitkan Invoice
- mencetak Invoice
- melihat pembayaran
- memonitor status pembayaran

Halaman ini tidak digunakan untuk verifikasi pembayaran.

---

# Hak Akses

Role yang dapat mengakses:

- Finance
- Super Admin

Read Only:

- Direktur
- Manager Operasional
- Marketing
- Admin Perusahaan

Role Klien tidak mengakses subroute Invoice; data Invoice miliknya ditampilkan melalui `/dashboard`.

---

# Struktur Halaman

Halaman terdiri dari:

1. Header
2. Toolbar
3. Filter
4. Invoice Table
5. Drawer Detail
6. Form Invoice

---

# Header

Menampilkan:

- Judul Halaman
- Total Invoice

---

# Toolbar

Berisi:

- Search
- Filter
- Export
- Refresh

---

# Filter

Filter yang tersedia:

- Status
- Jenis Invoice
- Audience Invoice
- Tipe Klien
- Mitra
- Periode
- Project
- Client
- Jatuh Tempo

---

# Search

Pencarian berdasarkan:

- Nomor Invoice
- ID Klien
- Nama Perusahaan
- Nama PIC

---

# Invoice Table

Kolom utama:

- Nomor Invoice
- Project
- Client
- Tipe Klien
- Audience
- Jenis Invoice
- Total Tagihan
- Total Dibayar
- Sisa Tagihan
- Due Date
- Status
- Action

---

# Action

Setiap baris memiliki aksi:

- Lihat
- Cetak PDF
- Edit
- Publish
- Batalkan

Action mengikuti status Invoice.

---

# Drawer Detail

Drawer terdiri dari beberapa tab.

---

## Tab Ringkasan

Menampilkan:

- Nomor Invoice
- Project
- Client
- Tipe Klien
- Audience Invoice
- Mitra, untuk audience Mitra
- Billing Group
- Jenis Invoice
- Nominal
- Status

---

## Tab Pembayaran

Menampilkan:

- Histori Payment
- Total Dibayar
- Sisa Tagihan
- Status Verifikasi

Tombol:

- Lihat Pembayaran

---

## Tab Timeline

Menampilkan:

- Invoice Dibuat
- Invoice Publish
- Pembayaran Masuk
- Invoice Lunas

---

## Tab Activity

Menampilkan Audit Trail terkait Invoice.

---

# Form Invoice

Minimal terdiri dari:

## Informasi Project

- Project
- Client

---

## Informasi Invoice

- Jenis Invoice
- Audience Invoice
- Mitra, jika audience Mitra
- Nominal
- Discount, hanya untuk Client Langsung jika diizinkan
- Tanggal Terbit
- Jatuh Tempo

---

## Catatan

- Keterangan

---

# Status Badge

Menggunakan label badge sesuai:

- Draft
- Diterbitkan
- Sebagian
- Lunas
- Dibatalkan

Referensi status terdapat pada:

07-status.md

---

# Empty State

Belum ada Invoice.

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

Invoice dibuka menggunakan Drawer.

Nomor Invoice selalu terlihat.

Status menggunakan Badge.

Nominal menggunakan format mata uang.

Invoice yang telah Published tidak dapat diedit.

Halaman tidak menyediakan Create, Delete, atau Bulk Action. Invoice dibuat otomatis oleh workflow; Finance menggunakan context action untuk memeriksa dan menerbitkan.

Untuk penagihan komersial Client Mitra, tampilkan Invoice Client dan Invoice Mitra sebagai pasangan dalam satu billing group. Field Discount disembunyikan dan nilainya selalu nol. Invoice Negara tidak dipasangkan.

---

# Business Rule

Invoice dibuat dari Project.

Nomor Invoice dibuat otomatis.

Invoice Published tidak dapat diubah.

Invoice Paid tidak dapat dibatalkan.

Status Invoice dihitung dari Payment yang telah diverifikasi.

---

# Future Enhancement

Mendukung:

- PDF Preview
- Email Invoice
- WhatsApp Invoice
- Digital Signature
- Tax
- Credit Note
- Multi Currency
- Payment Gateway

---

# Hubungan Dokumen

Workflow

- workflow/finance.md
- workflow/pembayaran.md

Database

- database/invoices.md
- database/payments.md

UI

- ui/klien.md
- ui/pembayaran.md
