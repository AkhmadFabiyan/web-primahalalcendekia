# Workflow - Admin

## Tujuan

Dokumen ini menjelaskan proses kerja Admin Operasional pada PHC System.

Admin bertanggung jawab memastikan seluruh dokumen persyaratan Project telah lengkap sebelum proses Entry SIHALAL dimulai.

Workflow ini dimulai setelah pembayaran aktivasi diverifikasi dan Project berstatus Aktif.

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
- database/documents.md

UI:

- ui/klien.md

---

# Aktor

Role utama:

- Admin

Role yang dapat melihat:

- Manager Operasional
- Direktur
- Super Admin

---

# Tujuan Bisnis

Admin bertugas:

- Memastikan Project siap diproses.
- Mengumpulkan dokumen dari klien.
- Mengunggah dokumen ke sistem.
- Memastikan seluruh dokumen wajib tersedia.
- Menginput akun SIHALAL milik klien.
- Menyerahkan Project kepada Entry.

---

# Trigger

Workflow dimulai ketika:

Pembayaran Aktivasi

↓

Diverifikasi Finance

↓

Status Project

Aktif

↓

Admin menerima notifikasi.

---

# Workflow

Project Aktif

↓

Upload Dokumen Wajib

↓

Upload Dokumen Opsional

↓

Input Akun SIHALAL

↓

Validasi Kelengkapan

↓

Dokumen Lengkap

↓

Entry menerima notifikasi

↓

Workflow Admin selesai

---

# Dokumen Wajib

Minimal dokumen berikut harus tersedia.

- NIB
- NPWP
- KTP PIC
- Daftar Produk
- Daftar Bahan
- Manual SJPH
- Surat Pernyataan
- Denah Lokasi
- Foto Fasilitas
- Dokumen pendukung lainnya

Daftar dokumen mengikuti konfigurasi sistem.

---

# Dokumen Opsional

Admin dapat menambahkan dokumen lain apabila diperlukan.

Contoh:

- Sertifikat sebelumnya
- Foto tambahan
- Surat pendukung
- Dokumen internal

---

# Akun SIHALAL

Admin menginput informasi akun SIHALAL.

Data yang disimpan.

- Email
- Password

Akses terhadap data ini dibatasi sesuai Role.

---

# Detail Workflow

## 1. Upload Dokumen

Admin mengunggah seluruh dokumen persyaratan.

Status Modul Dokumen

↓

Proses

---

## 2. Validasi Kelengkapan

Sistem memeriksa apakah seluruh dokumen wajib tersedia.

Apabila masih ada yang kurang.

Status

↓

Proses

Apabila lengkap.

Status

↓

Lengkap

---

## 3. Input Akun SIHALAL

Admin memasukkan akun SIHALAL yang akan digunakan oleh Entry.

---

## 4. Serahkan ke Entry

Apabila seluruh syarat terpenuhi.

Sistem:

- Mengubah Status Modul Dokumen menjadi Lengkap.
- Mengirim notifikasi kepada Entry.
- Membuka Workflow Entry.

---

# Output

Dokumen lengkap.

↓

Akun SIHALAL tersedia.

↓

Entry dapat memulai pekerjaannya.

---

# Business Rule

Project harus berstatus Aktif.

Seluruh dokumen wajib harus tersedia sebelum Workflow Entry dimulai.

Admin tidak dapat melakukan Entry SIHALAL.

Admin tidak dapat melakukan Approval Entry.

Admin tidak dapat melakukan Audit.

---

# Validasi

Seluruh dokumen wajib harus diunggah.

Format file harus sesuai.

Ukuran file mengikuti batas sistem.

Email SIHALAL harus valid.

Password SIHALAL wajib diisi.

---

# Exception

Project belum Aktif.

↓

Tidak dapat upload dokumen.

---

Dokumen wajib belum lengkap.

↓

Workflow tidak dapat diteruskan.

---

File gagal diunggah.

↓

Dokumen tetap berstatus belum lengkap.

---

# Status Modul

Belum Mulai

↓

Proses

↓

Lengkap

atau

↓

Revisi

↓

Proses

↓

Lengkap

Referensi:

07-status.md

---

# Activity Log

Catat aktivitas berikut.

- Upload Dokumen
- Hapus Dokumen
- Ganti Dokumen
- Input Akun SIHALAL
- Dokumen Lengkap

Referensi:

database/logs.md

---

# Notification

Pembayaran Aktivasi Diverifikasi

↓

Admin menerima notifikasi.

---

Dokumen Lengkap

↓

Entry menerima notifikasi.

Referensi:

08-notification.md

---

# KPI

Admin dapat dimonitor berdasarkan:

- Jumlah Project diproses
- Jumlah Dokumen diunggah
- Kelengkapan Dokumen
- Rata-rata waktu kelengkapan dokumen
- Project siap Entry

---

# Hubungan Workflow

Workflow ini menerima hasil dari:

workflow/finance.md

Workflow berikutnya:

workflow/entry.md

Dokumen ini hanya menjelaskan proses Admin Operasional.
