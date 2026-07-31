# UI - Leads

## Tujuan

Dokumen ini menjelaskan halaman **Lead Management** pada PHC System.

Halaman Lead digunakan oleh tim Marketing untuk mengelola calon pelanggan sebelum menjadi Client dan Project.

Lead merupakan titik awal seluruh proses bisnis.

Perubahan status Lead menjadi **Deal** akan membuat Client dan Project secara otomatis.

Dokumen ini hanya menjelaskan antarmuka (UI) dan pengalaman pengguna (UX).

Routing dijelaskan pada:

- 06-routing.md

Hak akses dijelaskan pada:

- 03-role-permission.md

Workflow dijelaskan pada:

- workflow/marketing.md

---

# Referensi

Dokumen terkait

- 01-business.md
- 03-role-permission.md
- 06-routing.md
- 07-status.md

Workflow

- workflow/marketing.md

---

# Tujuan Halaman

Halaman Lead digunakan untuk:

- melihat daftar Lead
- membuat Lead baru
- memperbarui informasi Lead
- mengubah status Lead
- mengonversi Lead menjadi Project

Halaman ini tidak digunakan untuk operasional Project.

---

# Hak Akses

Role yang dapat mengakses:

- Marketing
- Super Admin

Read Only:

- Direktur
- Manager Operasional

---

# Struktur Halaman

Halaman terdiri dari:

1. Header
2. Toolbar
3. Filter
4. Data Table
5. Pagination
6. Drawer Detail
7. Form Lead

---

# Header

Menampilkan:

- Judul Halaman
- Jumlah Lead
- Tombol Tambah Lead

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
- Marketing
- Tanggal
- Sumber Lead
- Kota

Filter dapat digunakan bersamaan.

---

# Data Table

Kolom yang ditampilkan:

- Lead ID
- Nama Perusahaan
- PIC
- Nomor HP
- Email
- Kota
- Sumber Lead
- Marketing
- Status
- Created At
- Action

---

# Action

Setiap baris memiliki aksi:

- Lihat
- Edit
- Ubah Status
- Deal
- Batal

Action mengikuti hak akses pengguna.

---

# Drawer Detail

Drawer menampilkan:

## Informasi Lead

- Nama Perusahaan
- PIC
- Kontak
- Alamat
- Catatan

## Aktivitas

- Riwayat perubahan
- Riwayat komunikasi

## Timeline

- Dibuat
- Follow Up
- Deal
- Batal

---

# Form Lead

Minimal terdiri dari:

## Informasi Perusahaan

- Nama Perusahaan
- Bentuk Usaha
- Bidang Usaha
- Tipe Klien: Langsung atau Mitra
- Pilih Mitra yang tersedia atau isi data Mitra baru, jika Tipe Klien = Mitra

## Kontak

- Nama PIC
- Nomor HP
- Email

## Lokasi

- Alamat
- Kota
- Provinsi

## Informasi Marketing

- Marketing
- Sumber Lead
- Nominal Client
- Nominal Mitra, jika Tipe Klien = Mitra
- Catatan

---

# Search

Pencarian berdasarkan:

- Nama Perusahaan
- PIC
- Nomor HP
- Email

---

# Sorting

Mendukung:

- Nama Perusahaan
- Tanggal Dibuat
- Status
- Marketing

---

# Empty State

Apabila belum ada Lead.

Tampilkan:

Belum ada Lead.

Tambahkan Lead pertama Anda.

---

# Loading State

Gunakan Skeleton Table.

---

# Error State

Tampilkan:

- pesan kesalahan
- tombol Muat Ulang

---

# Responsive

Desktop

Tabel penuh.

Tablet

Tabel dengan kolom prioritas.

Mobile

Card List.

Detail dibuka menggunakan Drawer penuh.

---

# UX Guideline

Form dibagi menjadi beberapa section.

Field wajib diberi indikator.

Status menggunakan Badge.

Action utama menggunakan warna primer.

Konfirmasi diperlukan sebelum mengubah status menjadi:

- Deal
- Batal

---

# Business Rule

Lead hanya memiliki satu status aktif.

Status Deal:

- membuat Client
- membuat ID Klien otomatis
- membuat satu Project
- mengirim notifikasi ke Finance

Status Batal:

- menghentikan proses Lead

Lead yang telah Deal tidak dapat diedit. Koreksi data dilakukan pada Klien atau Project oleh role yang berwenang.

Leads merupakan satu-satunya menu operasional yang menyediakan tombol Create. Bulk Action dan Delete tidak tersedia.

---

# Validasi

Nama Perusahaan wajib diisi.

PIC wajib tersedia.

Nomor HP wajib valid.

Marketing wajib dipilih.

Status wajib tersedia.

Untuk Tipe Mitra, Partner existing atau data Partner baru, Nominal Client, dan Nominal Mitra wajib tersedia. Field Discount tidak ditampilkan.

---

# Future Enhancement

Mendukung:

- Import Excel
- Integrasi CRM
- WhatsApp Follow Up
- Reminder Follow Up
- Lead Scoring
- Attachment
- Riwayat Komunikasi
- AI Lead Qualification

---

# Hubungan Dokumen

Workflow

- workflow/marketing.md

Role

- 03-role-permission.md

Status

- 07-status.md

UI

- ui/dashboard.md
- ui/klien.md
