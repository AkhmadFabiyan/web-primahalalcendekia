# Workflow - Entry

## Tujuan

Dokumen ini menjelaskan proses kerja Entry pada PHC System.

Entry bertanggung jawab melakukan input seluruh data Project ke sistem SIHALAL berdasarkan dokumen yang telah disiapkan oleh Admin.

Workflow ini dimulai setelah Admin menyatakan dokumen Project lengkap.

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
- database/logs.md

UI:

- ui/klien.md
- ui/tugas.md

---

# Aktor

Role utama:

- Entry

Role yang dapat melihat:

- SPV Entry
- Manager Operasional
- Direktur
- Super Admin

---

# Tujuan Bisnis

Entry bertugas:

- Memeriksa kelengkapan dokumen.
- Login ke akun SIHALAL.
- Melakukan Entry data.
- Memastikan seluruh data berhasil tersimpan.
- Mengirim hasil Entry kepada SPV untuk direview.

---

# Trigger

Workflow dimulai ketika:

Admin

↓

Dokumen Lengkap

↓

Entry menerima notifikasi.

---

# Workflow

Dokumen Lengkap

↓

Review Dokumen

↓

Login SIHALAL

↓

Entry Data

↓

Verifikasi

↓

Submit

↓

Menunggu Review SPV

---

# Detail Workflow

## 1. Review Dokumen

Entry memastikan dokumen yang tersedia sudah cukup untuk proses Entry.

Apabila terdapat kekurangan.

↓

Koordinasi dengan Admin.

Workflow belum dapat dilanjutkan.

---

## 2. Login SIHALAL

Entry menggunakan akun SIHALAL yang telah disiapkan oleh Admin.

Sistem PHC tidak melakukan autentikasi ke SIHALAL.

PHC hanya mencatat bahwa proses Entry dimulai.

---

## 3. Entry Data

Entry menginput data ke SIHALAL.

Contoh:

- Data perusahaan
- Data produk
- Data bahan
- Data fasilitas
- Data penyelia halal
- Data pendukung lainnya

Seluruh proses dilakukan di luar PHC System.

---

## 4. Verifikasi

Entry memastikan:

- Seluruh data berhasil tersimpan.
- Tidak ada kesalahan input.
- Dokumen telah terunggah ke SIHALAL (jika diperlukan).

---

## 5. Submit

Apabila proses Entry selesai.

Entry memilih:

**Selesai**

Sistem akan:

- Mengubah Status Entry menjadi **Pengajuan ke LPH** (`SUBMITTED_TO_LPH`, 80%).
- Mengirim notifikasi kepada SPV Entry.
- Menutup pekerjaan Entry.

---

# Output

Entry selesai.

↓

Menunggu Review SPV.

---

# Business Rule

Entry hanya dapat dimulai apabila:

- Project berstatus Aktif.
- Dokumen wajib lengkap.

Entry tidak dapat melakukan Approval.

Entry tidak dapat mengubah Status Project.

Entry tidak dapat mengubah hasil Review SPV.

Status Entry diperbarui manual melalui dropdown sesuai perkembangan nyata:

- Belum Dikerjakan — 0%
- Menunggu Dokumen Klien — 10%
- Dokumen Belum Lengkap — 20%
- Pembuatan Akun SiHalal — 35%
- Penyusunan Manual SJPH — 50%
- Input Bahan dan Produk — 65%
- Pengajuan ke LPH — 80%
- Revisi Dokumen — 90%
- Entry Selesai — 100%

Angka progress tidak dapat diketik dan selalu mengikuti `07-status.md`. Entry hanya dapat mengubah Project dengan Assignment aktif. Perubahan mundur wajib memiliki alasan.

---

# Validasi

Sebelum Submit.

Pastikan:

- Seluruh data telah diinput.
- Tidak ada kesalahan yang diketahui.
- Dokumen telah digunakan sesuai kebutuhan.

---

# Revisi

Apabila SPV mengembalikan pekerjaan.

Status Entry

↓

Revisi

Entry melakukan perbaikan.

↓

Submit kembali.

↓

Menunggu Review SPV.

Riwayat revisi tetap disimpan.

---

# Status Progress

Pilihan resmi adalah sembilan Status Entry dan persentase pada `07-status.md`. Alur di bawah hanya ringkasan lifecycle review, bukan nilai enum atau daftar dropdown:

Belum Mulai

↓

Proses

↓

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

Dokumen belum lengkap.

↓

Entry tidak dapat dimulai.

---

Akun SIHALAL tidak tersedia.

↓

Entry ditunda.

---

Terjadi kendala pada SIHALAL.

↓

Status tetap Proses.

Catatan kendala dapat ditambahkan pada Timeline Project.

---

# Activity Log

Catat aktivitas berikut.

- Memulai Entry.
- Submit Entry.
- Submit Ulang setelah Revisi.
- Menambahkan Catatan.
- Menyelesaikan Entry.

Referensi:

database/logs.md

---

# Notification

Dokumen Lengkap

↓

Entry menerima notifikasi.

---

Submit Entry

↓

SPV Entry menerima notifikasi.

---

Entry Direvisi

↓

Entry menerima notifikasi.

Referensi:

08-notification.md

---

# KPI

Entry dapat dimonitor berdasarkan:

- Jumlah Project dikerjakan.
- Jumlah Project selesai.
- Jumlah Revisi.
- Rata-rata waktu Entry.
- Tingkat keberhasilan Entry.

---

# Hubungan Workflow

Workflow ini menerima hasil dari:

workflow/admin.md

Workflow berikutnya:

workflow/spv-entry.md

Dokumen ini hanya menjelaskan proses Entry.
