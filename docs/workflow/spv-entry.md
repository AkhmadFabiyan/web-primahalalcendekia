# Workflow - SPV Entry

## Tujuan

Dokumen ini menjelaskan proses kerja Supervisor Entry (SPV Entry) pada PHC System.

SPV Entry bertanggung jawab melakukan review terhadap hasil Entry SIHALAL yang telah dikerjakan oleh Entry.

Workflow ini memastikan kualitas data sebelum proses Entry dinyatakan selesai.

---

# Referensi

Dokumen terkait:

- 01-business.md
- 02-workflow.md
- 03-role-permission.md
- 07-status.md
- 08-notification.md

Database:

- database/projects.md
- database/logs.md

UI:

- ui/klien.md
- ui/tugas.md

---

# Aktor

Role utama:

- SPV Entry

Role yang dapat melihat:

- Manager Operasional
- Direktur
- Super Admin

---

# Tujuan Bisnis

SPV Entry bertugas:

- Memeriksa hasil Entry.
- Memastikan data sesuai dokumen.
- Memberikan catatan revisi apabila diperlukan.
- Menyetujui hasil Entry.
- Menutup Workflow Entry.

---

# Trigger

Workflow dimulai ketika:

Entry

↓

Submit

↓

Status Entry

Pengajuan ke LPH

↓

SPV Entry menerima notifikasi.

---

# Workflow

Entry Submit

↓

Review

↓

Approve

atau

↓

Revisi

↓

Entry memperbaiki

↓

Review ulang

↓

Approve

↓

Workflow Entry selesai

---

# Detail Workflow

## 1. Review

SPV membuka Project.

Memeriksa:

- Kelengkapan data.
- Kesesuaian dokumen.
- Kesesuaian hasil Entry.
- Catatan sebelumnya (jika ada).

---

## 2. Approve

Apabila hasil Entry telah sesuai.

SPV memilih:

Approve

Sistem akan:

- Mengubah Status Entry menjadi **Entry Selesai** (`ENTRY_COMPLETED`, 100%).
- Menutup Workflow Entry.
- Memperbarui Progress Project.
- Mengevaluasi apakah Workflow A telah selesai.
- Mengirim notifikasi kepada Pendamping Auditor (apabila diperlukan).

---

## 3. Revisi

Apabila terdapat kesalahan.

SPV memilih:

Revisi

SPV wajib mengisi:

- Catatan revisi.

Sistem akan:

- Mengubah Status Entry menjadi **Revisi Dokumen** (`DOCUMENT_REVISION`, 90%).
- Mengirim notifikasi kepada Entry.
- Membuka kembali tugas Entry.

SPV Entry menggunakan dropdown Status Entry yang sama dengan Entry. Persentase tidak dapat diedit dan mengikuti `07-status.md`. Jika koreksi menurunkan progress, catatan alasan wajib diisi.

---

# Output

Apabila Approve

↓

Workflow Entry selesai.

Apabila Revisi

↓

Workflow kembali ke Entry.

---

# Business Rule

SPV tidak dapat mengubah data hasil Entry secara langsung.

SPV hanya dapat:

- Approve
- Revisi

Catatan revisi wajib diisi apabila memilih Revisi.

Approve bersifat final.

---

# Validasi

Sebelum Approve.

Pastikan:

- Data sesuai dokumen.
- Tidak terdapat kesalahan yang diketahui.
- Seluruh proses Entry telah selesai.

---

# Revisi

Apabila memilih Revisi.

SPV wajib memberikan:

- Penjelasan yang jelas.
- Bagian yang harus diperbaiki.

Setiap revisi disimpan sebagai histori.

---

# Status Review

Status progress yang disimpan tetap menggunakan sembilan Status Entry pada `07-status.md`. Alur di bawah menjelaskan keputusan review SPV dan bukan daftar enum dropdown:

Review

↓

Selesai

atau

↓

Revisi

↓

Proses

↓

Review

Referensi:

07-status.md

---

# Exception

Project belum disubmit.

↓

Tidak dapat direview.

---

Project telah selesai.

↓

Tidak dapat direvisi.

---

# Activity Log

Catat aktivitas berikut.

- Membuka Review.
- Approve.
- Revisi.
- Menambahkan Catatan Revisi.

Referensi:

database/logs.md

---

# Notification

Entry Submit

↓

SPV menerima notifikasi.

---

Revisi

↓

Entry menerima notifikasi.

---

Approve

↓

Pendamping Auditor menerima notifikasi (apabila Workflow Audit belum dimulai).

Referensi:

08-notification.md

---

# KPI

SPV Entry dapat dimonitor berdasarkan:

- Jumlah Review.
- Jumlah Approve.
- Jumlah Revisi.
- Rata-rata waktu Review.
- Rasio Revisi terhadap Approve.

---

# Hubungan Workflow

Workflow ini menerima hasil dari:

workflow/entry.md

Workflow berikutnya:

workflow/audit.md

Workflow ini juga memengaruhi penyelesaian Workflow A.

Dokumen ini hanya menjelaskan proses review oleh SPV Entry.
