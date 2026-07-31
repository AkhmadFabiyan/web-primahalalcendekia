# 00. Glossary

## Tujuan

Dokumen ini berisi definisi seluruh istilah yang digunakan pada sistem Prima Halal Cendekia (PHC System).

Semua dokumentasi wajib menggunakan istilah yang ada pada dokumen ini.

Dilarang membuat istilah baru tanpa memperbarui glossary.

---

# Aturan Penamaan

- Gunakan satu istilah untuk satu konsep.
- Hindari sinonim pada dokumentasi.
- Nama tabel database, API, UI, dan kode program harus mengikuti glossary.
- Jika terdapat perubahan istilah, glossary menjadi dokumen pertama yang diperbarui.

Konvensi bahasa:

- Narasi bisnis dan label UI menggunakan istilah Indonesia: Klien, Pembayaran, Notifikasi, dan Pengguna.
- Nama entitas kode, tabel, endpoint, serta route menggunakan istilah Inggris: `Client`, `Payment`, `Notification`, `User`, `/clients`, `/payments`, dan `/notifications`.
- `Project` dan `Invoice` tetap digunakan pada narasi maupun kode karena merupakan istilah produk yang ditetapkan.

---

# Istilah Bisnis

## Lead

Calon klien yang belum menjadi Project.

Lead dibuat oleh Marketing.

Status Lead:

- Draft
- Deal
- Batal

Referensi:
- workflow/marketing.md

---

## Klien (Client)

Perusahaan, yayasan, atau pelaku usaha yang menggunakan layanan Prima Halal Cendekia.

Satu Klien memiliki tepat satu Project.

ID Klien dibuat otomatis ketika Lead berubah menjadi Deal dan menjadi identitas bisnis utama pada seluruh tampilan operasional.

User dengan Role Klien hanya mengakses data miliknya sendiri melalui `/dashboard`.

Referensi:
- database/clients.md

---

## Tipe Klien

Klasifikasi hubungan komersial Client.

| Kode | Label UI | Keterangan |
|---|---|---|
| `DIRECT` | Langsung | Transaksi dilakukan langsung dengan Client |
| `PARTNER` | Mitra | Transaksi melibatkan satu Partner dan menggunakan dua nominal serta dua Invoice |

Tipe Klien ditampilkan pada detail Client versi internal dan tidak dapat diubah setelah Invoice diterbitkan.

---

## Mitra (Partner)

Pihak perantara atau rekanan yang menghubungkan PHC dengan Client.

Satu Mitra dapat berelasi dengan banyak Client, sedangkan satu Client bertipe Mitra hanya terhubung ke satu Mitra.

---

## Nominal Klien

Nilai yang digunakan untuk Invoice dengan penerima Client.

---

## Nominal Mitra

Nilai yang digunakan untuk Invoice dengan penerima Mitra.

Nominal Mitra hanya tersedia untuk Client bertipe Mitra. Selisih antara Nominal Klien dan Nominal Mitra tidak dicatat sebagai diskon.

---

## Project

Satu proses sertifikasi halal milik satu Klien.

Seluruh aktivitas sistem selalu terhubung ke Project.

Project tetap menggunakan UUID internal untuk kebutuhan relasi database, tetapi tidak memiliki Business ID yang ditampilkan kepada pengguna. Pada UI, API bisnis, pencarian, tugas, Invoice, dan laporan, Project dikenali melalui ID Klien.

Referensi:
- database/projects.md

---

## Layanan (Service)

Jenis layanan yang dipilih oleh Klien.

Contoh:

- Sertifikasi Halal Reguler
- Self Declare
- Pendampingan
- Perpanjangan Sertifikat

---

## Paket

Kategori layanan yang dijual kepada Klien.

Nominal pembayaran mengikuti Paket.

---

## PIC

Person In Charge.

Orang yang menjadi kontak utama dari Klien.

---

## Tim Internal

Karyawan Prima Halal Cendekia yang menangani Project.

---

# Workflow

## Workflow A

Proses administrasi dokumen dan Entry SIHALAL.

Tahapan:

Admin
↓

Entry
↓

SPV Entry

---

## Workflow B

Proses pendampingan audit.

Tahapan:

Pendamping Auditor
↓

Auditor

---

## Paralel Workflow

Workflow A dan Workflow B berjalan secara bersamaan.

Tahap berikutnya hanya dapat dimulai apabila kedua workflow selesai.

---

# Dokumen

## Dokumen Wajib

Dokumen yang harus diunggah sebelum proses Entry SIHALAL.

Jumlah dokumen wajib ditentukan oleh kebijakan perusahaan.

---

## Dokumen Opsional

Dokumen tambahan yang dapat diunggah apabila diperlukan.

---

## Lampiran

Seluruh file yang berhubungan dengan Project.

Contoh:

- PDF
- Word
- Excel
- Gambar

---

# SIHALAL

## Akun SIHALAL

Email dan password akun SIHALAL milik Klien.

Digunakan oleh Tim Entry.

---

## Entry SIHALAL

Proses memasukkan data ke sistem SIHALAL.

Dilakukan di luar PHC System.

PHC System hanya mencatat progresnya.

---

# Audit

## Booking Audit

Proses penjadwalan audit.

Dilakukan oleh Pendamping Auditor.

---

## Temuan Audit

Catatan hasil audit.

Dapat berupa revisi maupun rekomendasi.

---

## Revisi Audit

Perbaikan berdasarkan hasil audit.

---

# Sertifikat

## Nomor Sertifikat

Nomor resmi sertifikat halal.

Diinput setelah sertifikat diterbitkan.

---

## Sertifikat Halal

Dokumen resmi yang diterbitkan setelah proses selesai.

---

# Pembayaran

Pembayaran (Payment) adalah satu modul yang mencakup pengelolaan Invoice dan transaksi Payment. Invoice bukan modul terpisah.

## Invoice

Tagihan pembayaran yang diterbitkan oleh Finance.

Satu Project dapat memiliki banyak Invoice.

Pada Client bertipe Mitra, satu event penagihan komersial menghasilkan dua Invoice:

- Invoice Client
- Invoice Mitra

Kedua Invoice tetap merupakan satu transaksi bisnis karena terhubung ke Client dan Project yang sama.

---

## Termin

Satu tahapan pembayaran.

Contoh:

- DP
- Termin 2
- Pelunasan

Jumlah Termin tidak dibatasi.

---

## Pembayaran

Proses pelunasan Invoice.

Status pembayaran dapat berbeda dengan status Invoice.

---

## Invoice Aktivasi

Invoice pertama yang harus diverifikasi agar Project menjadi aktif.

---

## Invoice Negara

Tagihan resmi dari BPJPH yang diunggah oleh Admin Perusahaan.

---

## Pelunasan

Pembayaran terakhir dari Klien.

Tidak selalu merupakan Termin terakhir apabila terdapat skema pembayaran khusus.

---

# Status

## Status Utama

Status global Project.

Contoh:

Aktif

Selesai

---

## Status Modul

Status pada masing-masing divisi.

Contoh:

Entry

Audit

Finance

Dokumen

---

## Progress

Persentase penyelesaian Project.

Dihitung oleh sistem berdasarkan status modul.

---

# User

## Role

Hak akses pengguna dalam sistem.

Referensi:

03-role-permission.md

---

## User

Akun yang dapat masuk ke sistem.

Satu User hanya memiliki satu Role.

---

## Akun Klien

User dengan Role Klien yang digunakan untuk login ke PHC System.

Akun Klien bukan data Client. Data Client dibuat saat Lead Deal, sedangkan akun login dibuat kemudian oleh Super Admin melalui aksi **Buat Akun**.

Email dibuat otomatis dengan pola:

`{user}@primahalalcendekia.com`

---

# Sistem

## Dashboard

Halaman ringkasan kondisi sistem pada `/dashboard`.

- Staf internal selain Super Admin melihat Dashboard Progres Operasional organisasi.
- Super Admin melihat Dashboard Administrasi Sistem.
- Klien melihat portal read-only untuk datanya sendiri.

---

## Status Progress

Status perkembangan yang dipilih manual oleh role berwenang pada jalur Entry, Pendamping, atau Auditor.

Persentase progress bukan input manual. Sistem menurunkannya dari status resmi pada `07-status.md`.

---

## Timeline

Riwayat seluruh aktivitas pada Project.

Tidak dapat dihapus.

---

## Activity Log

Catatan seluruh perubahan data.

Digunakan untuk audit internal.

---

## Notifikasi

Pesan otomatis yang dikirim oleh sistem berdasarkan suatu kejadian.

---

## SLA

Batas waktu maksimal penyelesaian suatu tahapan.

---

# Identitas

## ID Klien

Identitas bisnis unik setiap Klien sekaligus Project tunggal miliknya.

Format:

CLIENT-YYYY-XXXX

Contoh:

CLIENT-2026-0001

ID Klien dibuat otomatis saat Lead berubah menjadi Deal, tidak dapat diisi manual, dan tidak berubah selama siklus hidup Klien.

---

## Nomor Invoice

Nomor unik Invoice.

Format:

INV/PHC/YYYY/XXXX-XX-A

Contoh:

INV/PHC/2026/0045-01-C

Invoice Client pertama milik Klien CLIENT-2026-0045. Suffix `P` digunakan untuk Invoice Partner.

---

# Referensi

- 01-business.md
- 02-workflow.md
- 03-role-permission.md
- 04-database.md
- 07-status.md
