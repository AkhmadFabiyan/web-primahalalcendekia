# UI - Tugas

## Tujuan

Dokumen ini menjelaskan halaman **Tugas (My Tasks)** pada PHC System.

Halaman ini digunakan untuk menampilkan seluruh pekerjaan yang harus diselesaikan oleh pengguna.

Daftar tugas dibangun secara otomatis berdasarkan:

- Assignment
- Workflow
- Status Project
- Role pengguna

Dokumen ini hanya menjelaskan antarmuka (UI) dan pengalaman pengguna (UX).

Routing dijelaskan pada:

- 06-routing.md

Hak akses dijelaskan pada:

- 03-role-permission.md

---

# Referensi

Dokumen terkait

- 03-role-permission.md
- 06-routing.md
- 07-status.md

Workflow

- workflow/admin.md
- workflow/entry.md
- workflow/spv-entry.md
- workflow/audit.md
- workflow/sertifikat.md
- workflow/pembayaran.md

UI

- ui/dashboard.md
- ui/klien.md

---

# Tujuan Halaman

Halaman Tugas digunakan untuk:

- melihat pekerjaan yang belum selesai
- memprioritaskan pekerjaan
- membuka Project terkait
- memonitor deadline
- membantu pengguna menyelesaikan workflow

Halaman ini bukan daftar seluruh Project.

---

# Hak Akses

Menu Tugas wajib tersedia untuk:

- Admin Perusahaan
- Entry
- SPV Entry
- Auditor
- Pendamping Auditor

Super Admin, Manager Operasional, dan Admin dapat mengakses sesuai permission monitoring dan operasional.

Data yang ditampilkan mengikuti:

- Role
- Assignment
- Status Workflow

Pengguna hanya melihat tugas miliknya.

---

# Struktur Halaman

Halaman terdiri dari:

1. Header
2. Ringkasan
3. Filter
4. Task Table
5. Drawer Detail

---

# Header

Menampilkan:

- Judul Halaman
- Jumlah Tugas
- Jumlah Deadline Hari Ini

Navigation menu **Tugas** menampilkan badge angka berisi jumlah tugas milik User yang belum berstatus Selesai. Badge diperbarui ketika tugas dibuat, dimulai, direvisi, atau diselesaikan.

---

# Ringkasan

Widget:

- Total Tugas
- Prioritas Tinggi
- Deadline Hari Ini
- Terlambat
- Selesai Hari Ini

---

# Filter

Filter yang tersedia:

- Status
- Prioritas
- Workflow
- Deadline
- Project

---

# Search

Pencarian berdasarkan:

- ID Klien
- Nama Perusahaan
- Nama Tugas

---

# Task Table

Kolom wajib dan urutannya:

1. No.
2. ID Klien
3. Timestamp Masuk
4. Deadline
5. PIC

Kolom status dan action dapat ditampilkan setelah kolom wajib apabila dibutuhkan.

`Timestamp Masuk` adalah waktu tugas memasuki antrean role atau User terkait.

Kolom PIC menampilkan seluruh pihak yang terlibat dalam Project, bukan hanya pemilik tugas saat ini. Tampilkan sebagai daftar ringkas, avatar stack, atau chips yang dapat diperluas, minimal:

- Klien
- Entry
- Admin Perusahaan
- Finance
- Auditor
- Pendamping Auditor
- SPV Entry
- Admin dan role terkait lain sesuai Assignment

Setiap PIC menampilkan nama dan role. Jika belum ditugaskan, tampilkan status **Belum Ditentukan**.

---

# Prioritas

Menggunakan Badge:

- Low
- Medium
- High
- Critical

---

# Status

Menggunakan label Badge:

- Belum Dikerjakan
- Sedang Dikerjakan
- Menunggu Review
- Revisi
- Selesai

Referensi status:

07-status.md

---

# Action

Setiap tugas memiliki aksi:

- Buka Project
- Kerjakan
- Lihat Detail

---

# Drawer Detail

Drawer terdiri dari:

## Informasi Tugas

- Nama Tugas
- Workflow
- ID Klien
- Client
- Prioritas
- Seluruh PIC yang terlibat

---

## Instruksi

Menampilkan:

- deskripsi pekerjaan
- langkah yang harus dilakukan
- dokumen yang diperlukan

---

## Timeline

Menampilkan:

- dibuat
- mulai dikerjakan
- revisi
- selesai

---

## Activity

Audit Trail terkait tugas.

---

## Quick Action

Contoh:

Admin

- Upload Dokumen

Entry

- Input SIHALAL

SPV

- Approve

Finance

- Verify Payment

Audit

- Jadwalkan Audit

---

# Empty State

Tidak ada tugas.

Pesan:

"Semua pekerjaan telah selesai."

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

Table + Drawer.

Tablet

Drawer Full Width.

Mobile

Card List.

---

# UX Guideline

Tugas dengan prioritas tinggi selalu berada di bagian atas.

Deadline yang telah lewat menggunakan indikator warna.

Quick Action harus dapat dijalankan langsung dari Drawer.

Pengguna tidak perlu mencari Project secara manual.

---

# Business Rule

Tugas dibuat otomatis berdasarkan perubahan workflow.

Tugas selesai apabila langkah workflow selesai.

Menyelesaikan tugas dapat membuat tugas baru untuk role berikutnya.

Tugas yang telah selesai tidak dapat diedit.

Assignment menentukan pemilik tugas.

Satu Klien memiliki satu Project, sehingga pencarian dan tampilan tugas menggunakan ID Klien. ID Project tidak ditampilkan.

Halaman Tugas tidak menyediakan:

- Bulk Action
- Create
- Delete

Pengguna hanya menjalankan context action yang diizinkan oleh workflow, seperti Kerjakan, Submit, Approve, Revisi, Verifikasi, atau Selesai.

---

# Future Enhancement

Mendukung:

- Kanban View
- Calendar View
- Drag & Drop Priority
- Reminder
- SLA Countdown
- Estimasi Durasi
- AI Prioritization

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
- ui/klien.md
