# UI - Laporan

## Tujuan

Dokumen ini menjelaskan halaman **Laporan (Reports & Analytics)** pada PHC System.

Halaman ini digunakan untuk melihat data analitik, KPI, statistik, dan laporan operasional dari seluruh proses bisnis.

Halaman ini tidak digunakan untuk melakukan perubahan data.

Semua informasi berasal dari data Project, Invoice, Payment, Workflow, dan Activity Log.

Dokumen ini hanya menjelaskan antarmuka (UI) dan pengalaman pengguna (UX).

Routing dijelaskan pada:

- 06-routing.md

Hak akses dijelaskan pada:

- 03-role-permission.md

---

# Referensi

Dokumen terkait

- 01-business.md
- 02-workflow.md
- 03-role-permission.md
- 04-database.md
- 06-routing.md

Database

- database/projects.md
- database/invoices.md
- database/payments.md
- database/logs.md

---

# Tujuan Halaman

Halaman Laporan digunakan untuk:

- melihat KPI
- memonitor performa operasional
- melihat statistik bisnis
- melihat statistik keuangan
- melihat performa tim
- melakukan ekspor laporan

Halaman ini tidak digunakan untuk menjalankan workflow.

---

# Hak Akses

Role yang dapat mengakses:

- Manager Operasional
- Direktur
- Super Admin

Role operasional tidak memiliki akses penuh.

---

# Struktur Halaman

Halaman terdiri dari:

1. Header
2. Global Filter
3. KPI Cards
4. Charts
5. Report Table
6. Export Panel

---

# Header

Menampilkan:

- Judul Halaman
- Periode Aktif
- Tombol Export

---

# Global Filter

Filter berlaku untuk seluruh widget.

Filter meliputi:

- Periode
- Marketing
- Admin
- Auditor
- Jenis Layanan
- Tipe Klien
- Mitra
- Kota
- Status Project

---

# KPI Cards

Menampilkan ringkasan:

- Total Lead
- Total Client
- Total Project
- Project Aktif
- Project Selesai
- Project Terlambat
- Invoice Aktif
- Invoice Lunas
- Outstanding Payment
- Sertifikat Terbit

Klik KPI membuka detail laporan terkait.

---

# Dashboard Chart

## Dashboard Progres Operasional

Dataset ini juga digunakan oleh `/dashboard` untuk seluruh staf internal selain Super Admin:

- KPI operasional sesuai `ui/dashboard.md`
- distribusi tahap sertifikasi
- kondisi pembaruan data
- Status/Progress Entry
- Status/Progress Pendamping
- Status/Progress Auditor
- daftar Kritis dan Perlu Follow Up

Agregat menghitung Project/Client unik. Pasangan Invoice Mitra, banyak Assignment, dan banyak dokumen tidak boleh menggandakan jumlah transaksi atau Client.

Chart Dashboard dan laporan drill-down harus menggunakan definisi filter serta query dasar yang sama.

## Lead Conversion

Menampilkan:

- Lead
- Deal
- Conversion Rate

Visualisasi:

- Funnel
- Bar Chart

---

## Project Status

Menampilkan distribusi seluruh Status Project:

- Menunggu Aktivasi
- Aktif
- Operasional
- Menunggu Invoice Negara
- Menunggu Sertifikat
- Sertifikat Terbit
- Menunggu Pelunasan
- Selesai
- Dibatalkan

Visualisasi:

- Donut Chart

---

## Workflow Performance

Menampilkan jumlah Project pada setiap tahapan workflow.

Visualisasi:

- Horizontal Bar Chart

---

## Revenue

Menampilkan:

- Nilai Invoice
- Pembayaran
- Outstanding

Visualisasi:

- Line Chart
- Area Chart

---

## Payment Status

Menampilkan:

- Menunggu Verifikasi
- Terverifikasi
- Ditolak

Visualisasi:

- Pie Chart

---

## Certificate

Menampilkan:

- Sudah Terbit
- Belum Terbit

Visualisasi:

- Progress Chart

---

# Report Table

Tabel dapat berubah sesuai laporan yang dipilih.

Kolom umum:

- ID Klien
- Client
- Workflow
- Status
- PIC
- Marketing
- Nilai Invoice
- Total Payment
- Deadline

---

# Detail Report

Klik salah satu baris membuka Drawer.

Drawer menampilkan:

- Ringkasan Project
- Timeline
- Workflow
- Invoice
- Payment

---

# Export

Mendukung:

- Excel
- CSV
- PDF

Export mengikuti filter yang aktif.

---

# Empty State

Belum ada data.

---

# Loading State

Gunakan Skeleton untuk:

- KPI
- Chart
- Table

---

# Error State

Tampilkan:

- pesan kesalahan
- tombol Muat Ulang

---

# Responsive

Desktop

Semua widget ditampilkan.

Tablet

Chart menjadi satu kolom.

Mobile

KPI menjadi Card Scroll.

Chart ditampilkan vertikal.

---

# UX Guideline

Gunakan warna yang konsisten.

Semua chart dapat diklik.

Filter berlaku global.

Perubahan filter memperbarui seluruh widget secara bersamaan.

---

# Business Rule

Laporan hanya menampilkan data yang telah tersimpan.

Tidak boleh mengubah data operasional.

Perhitungan KPI dilakukan oleh backend.

Export menggunakan data hasil filter.

Jumlah transaksi dihitung dengan `COUNT(DISTINCT project_id)`, bukan jumlah Invoice.

Untuk Client Mitra:

- Invoice Client dan Invoice Mitra ditampilkan sebagai dua dokumen dalam satu transaction group.
- Nilai Client dan Nilai Mitra dilaporkan pada kolom terpisah.
- Keduanya tidak dijumlahkan sebagai dua transaksi.
- Selisih dapat dihitung dari Nominal Client dikurangi Nominal Mitra dan bukan dari discount.

---

# Future Enhancement

Mendukung:

- Dashboard Personal
- Saved Filter
- Scheduled Report
- Email Report
- Drill Down Analytics
- AI Insight
- Forecast Revenue
- SLA Dashboard
- Productivity Dashboard
- Real-time Dashboard

---

# Hubungan Dokumen

Database

- database/projects.md
- database/invoices.md
- database/payments.md

Workflow

- workflow/*

UI

- ui/dashboard.md
- ui/klien.md
