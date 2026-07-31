# UI - Klien

## Tujuan

Dokumen ini menjelaskan halaman **Klien** pada PHC System.

Halaman Klien merupakan pusat operasional Project tunggal setelah Lead berhasil dikonversi.

Semua aktivitas operasional dilakukan dari halaman ini tanpa berpindah ke halaman detail terpisah.

Halaman ini menggunakan pola:

Table → Drawer → Action

Dokumen ini hanya menjelaskan perilaku antarmuka (UI) dan pengalaman pengguna (UX).

Routing dijelaskan pada:

- 06-routing.md

Hak akses dijelaskan pada:

- 03-role-permission.md

Workflow dijelaskan pada:

- workflow/*

---

# Referensi

Dokumen terkait

- 01-business.md
- 02-workflow.md
- 03-role-permission.md
- 06-routing.md
- 07-status.md
- 09-ui-ux.md

Workflow

- workflow/admin.md
- workflow/entry.md
- workflow/spv-entry.md
- workflow/audit.md
- workflow/sertifikat.md
- workflow/pembayaran.md

---

# Tujuan Halaman

Halaman Klien digunakan untuk:

- melihat seluruh Project
- menjalankan workflow
- melihat status Project
- mengelola dokumen
- mengelola pembayaran
- melihat aktivitas
- melihat timeline
- melihat assignment

Halaman ini merupakan pusat pekerjaan seluruh divisi operasional.

Halaman ini khusus role internal. Role Klien melihat data miliknya melalui `/dashboard`.

---

# Hak Akses

Role yang dapat mengakses:

- Super Admin
- Marketing
- Finance
- Admin
- Entry
- SPV Entry
- Pendamping Auditor
- Auditor
- Admin Perusahaan
- Direktur
- Manager Operasional

Data yang ditampilkan mengikuti Role dan Assignment.

Seluruh staf internal dapat melihat detail Client beserta Project tunggalnya. Action tetap mengikuti Role, Permission, dan Assignment.

Role Klien tidak dapat mengakses `/clients`, `/projects`, atau halaman ini.

---

# Struktur Halaman

Halaman terdiri dari:

1. Header
2. Toolbar
3. Filter
4. Project Table
5. Drawer Detail
6. Timeline
7. Activity
8. Workflow Panel

---

# Header

Menampilkan:

- Judul Halaman
- Total Project
- Tombol Refresh

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

- Status Project
- Tipe Klien
- Mitra
- Workflow
- Marketing
- Admin
- Entry
- Auditor
- Finance
- Kota
- Tanggal
- Jenis Layanan

Filter dapat dikombinasikan.

---

# Project Table

Kolom utama:

- ID Klien
- Nama Perusahaan
- Tipe Klien
- Mitra (jika ada)
- PIC
- Jenis Layanan
- Status
- Workflow
- Assigned To
- Deadline
- Updated At
- Action

Kolom tambahan dapat diaktifkan sesuai kebutuhan.

---

# Search

Pencarian berdasarkan:

- ID Klien
- Nama Perusahaan
- Nama PIC
- Nomor Sertifikat
- Nomor Invoice

---

# Sorting

Mendukung:

- ID Klien
- Nama Perusahaan
- Deadline
- Status
- Tanggal Dibuat
- Terakhir Diubah

---

# Drawer Detail

Klik satu Project membuka Drawer.

Drawer terdiri dari beberapa tab.

---

## Tab Ringkasan

Menampilkan:

- ID Klien
- Informasi Project tunggal
- Informasi Client
- Tipe Klien: Langsung atau Mitra
- Mitra, jika Tipe Klien = Mitra
- Nominal Client
- Nominal Mitra, jika Tipe Klien = Mitra
- PIC
- Status
- Assignment

---

## Tab Workflow

Menampilkan tiga tracker utama:

| Tracker | Tampilan |
|---|---|
| Status Entry | Dropdown bagi Entry/SPV Entry berwenang; selain itu badge read-only |
| Progress Entry | Persentase otomatis dari Status Entry |
| Status Pendamping | Dropdown bagi Pendamping Auditor berwenang; selain itu badge read-only |
| Progress Pendamping | Persentase otomatis dari Status Pendamping |
| Status Auditor | Dropdown bagi Auditor atau Admin Perusahaan pada rentang kewenangannya; selain itu badge read-only |
| Progress Auditor | Persentase otomatis dari Status Auditor |

Setiap tracker juga menampilkan pengubah terakhir, waktu pembaruan, catatan terakhir, progress bar, dan tombol histori. Mapping status dan persentase mengikuti `07-status.md`.

Perubahan status bersifat manual melalui dropdown, tetapi persentase tidak dapat diketik. Perubahan mundur meminta konfirmasi serta alasan. Action yang tidak berizin tidak dirender.

Stepper proses keseluruhan tetap menampilkan:

Marketing

↓

Finance

↓

Admin

↓

Entry

↓

SPV Entry

↓

Audit

↓

Invoice Negara

↓

Certificate

↓

Pelunasan

↓

Finished

Workflow menggunakan Stepper.

---

## Tab Dokumen

Menampilkan:

- Daftar Dokumen
- Status
- Upload
- Download
- Preview
- Version

Action mengikuti hak akses.

---

## Tab Pembayaran

Menampilkan:

- Daftar Invoice
- Audience Invoice: Client atau Mitra
- Pasangan Invoice Mitra dalam satu billing group
- Status
- Nominal
- Sisa Tagihan
- Due Date
- Histori Payment
- Bukti Transfer
- Status Verifikasi

Action:

- Lihat
- Cetak
- Bayar
- Verifikasi
- Upload
- Tolak

Untuk penagihan komersial Client Mitra, tampilkan dua kartu atau baris Invoice yang dikelompokkan sebagai satu transaksi:

- Invoice Client — Nominal Client
- Invoice Mitra — Nominal Mitra

Jangan menampilkan field diskon pada skema Mitra. Ringkasan transaksi tidak menjumlahkan kedua Invoice sebagai dua transaksi.

Invoice Negara ditampilkan sendiri dan tidak memiliki pasangan Invoice Mitra.

---

## Tab Timeline

Menampilkan seluruh aktivitas Project berdasarkan waktu.

Contoh:

Lead Deal

↓

Invoice Aktivasi

↓

Dokumen Upload

↓

Entry

↓

Audit

↓

Invoice Negara

↓

Certificate

↓

Finished

---

## Tab Activity Log

Menampilkan Audit Trail.

Informasi:

- User
- Action
- Waktu
- Modul

---

## Tab Assignment

Menampilkan:

- Marketing
- Finance
- Admin
- Entry
- SPV
- Auditor

Super Admin dapat mengganti Assignment.

---

## Tab Sertifikat

Menampilkan:

- Nomor Sertifikat
- File Sertifikat
- Upload
- Download
- Tanggal Terbit

---

# Quick Action

Action yang muncul mengikuti kondisi Project.

Contoh:

Admin

- Upload Dokumen

Entry

- Input SIHALAL

SPV

- Approve

Finance

- Verifikasi Payment

Audit

- Jadwalkan Audit

Manager Operasional

- Lihat Laporan

Super Admin

- Buat Akun

Aksi **Buat Akun** hanya muncul apabila Client belum memiliki akun login. Tidak ada form manual; sistem membuat User Role Klien, relasi `client_id`, dan email `{user}@primahalalcendekia.com` secara otomatis.

---

# Empty State

Belum ada Project.

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

Drawer menjadi halaman penuh.

---

# UX Guideline

Project tidak membuka halaman baru.

Gunakan Drawer.

Semua informasi utama berada dalam satu workspace.

Status menggunakan Badge.

Workflow menggunakan Stepper.

Action utama selalu berada di kanan atas Drawer.

---

# Business Rule

Setiap Client memiliki tepat satu Project dan satu Drawer workspace.

ID Klien dibuat otomatis saat Lead menjadi Deal dan menjadi identitas utama pada tabel serta Drawer. ID Project tidak ditampilkan.

Action mengikuti:

- Role
- Status
- Assignment

Workflow tidak dapat dilompati.

Invoice Negara hanya muncul setelah Workflow Operasional selesai.

Pelunasan hanya muncul setelah Sertifikat tersedia.

Halaman tidak menyediakan Bulk Action atau Delete. Create manual hanya tersedia pada menu Leads. Aksi **Buat Akun** adalah context action khusus Super Admin, bukan Create generik.

Tipe Klien dan Partner terkunci setelah salah satu Invoice diterbitkan.

Nominal Mitra hanya ditampilkan pada detail internal dan tidak dikirim ke portal Klien.

---

# Future Enhancement

Mendukung:

- Split View
- Multi Drawer
- Live Collaboration
- Internal Comment
- Mention User
- AI Summary
- AI Recommendation
- Workflow Automation
- Timeline Filter
- Keyboard Shortcut

---

# Hubungan Dokumen

Workflow

- workflow/*

Role

- 03-role-permission.md

Status

- 07-status.md

UI

- ui/dashboard.md
- ui/invoice.md
- ui/pembayaran.md
