# Workflow - Marketing

## Tujuan

Dokumen ini menjelaskan proses kerja Marketing dalam PHC System.

Marketing bertanggung jawab memperoleh calon klien (Lead), melakukan pengelolaan informasi awal, dan mengubah Lead menjadi Project apabila terjadi kesepakatan.

Workflow ini merupakan titik awal seluruh proses sertifikasi halal.

---

# Referensi

Dokumen terkait:

- 01-business.md
- 02-workflow.md
- 03-role-permission.md
- 07-status.md

Database:

- database/clients.md
- database/projects.md

UI:

- ui/leads.md

---

# Aktor

Role utama:

- Marketing

Role yang dapat melihat:

- Manager Operasional
- Direktur
- Super Admin

---

# Tujuan Bisnis

Marketing bertugas:

- Mencatat Lead baru.
- Mengelola informasi calon klien.
- Melakukan pembaruan informasi Lead.
- Mengubah Lead menjadi Deal.
- Membatalkan Lead apabila tidak jadi bekerja sama.

---

# Input

Marketing mengisi informasi berikut.

## Informasi Usaha

- Nama usaha
- Bidang usaha
- Alamat
- Tipe Klien: Langsung atau Mitra
- Pilih Mitra yang sudah ada atau isi identitas Mitra baru, apabila Tipe Klien = Mitra

---

## PIC

- Nama PIC
- Nomor HP
- Email (opsional)

---

## Layanan

- Jenis layanan
- Nominal Klien
- Nominal Mitra, apabila Tipe Klien = Mitra
- Sistem pembayaran
- Jumlah termin

---

# Workflow

Lead Baru

↓

Input Data

↓

Simpan Draft

↓

Negosiasi

↓

Deal

atau

↓

Batal

---

# Detail Workflow

## 1. Membuat Lead

Status

Draft

Marketing mengisi seluruh data awal.

Output

Lead berhasil dibuat.

---

## 2. Mengubah Lead

Marketing dapat memperbarui informasi Lead selama status masih Draft.

---

## 3. Deal

Apabila calon klien menyetujui penawaran.

Marketing memilih:

Deal

Sistem akan:

- Mengubah Status Lead menjadi Deal.
- Membuat Client.
- Menggunakan Partner yang tersedia atau membuat Partner baru setelah pemeriksaan duplikat.
- Membuat ID Client.
- Membuat Project.
- Membuat satu draft Invoice Aktivasi untuk Client Langsung.
- Membuat dua draft Invoice Aktivasi untuk Client Mitra: Invoice Client dan Invoice Mitra.
- Mengubah Status Project menjadi Menunggu Aktivasi.
- Mengirim Notifikasi kepada Finance.

---

## 4. Batal

Apabila kerja sama tidak terjadi.

Marketing memilih:

Batal

Status Lead menjadi:

Batal

Lead tidak dapat diproses lebih lanjut.

---

# Output

Apabila Draft

↓

Lead tersimpan.

Apabila Deal

↓

Client dibuat.

↓

Project dibuat.

↓

Finance menerima tugas.

Apabila Batal

↓

Workflow selesai.

---

# Business Rule

Lead wajib memiliki:

- Nama usaha
- PIC
- Jenis layanan

Lead yang sudah Deal tidak dapat kembali menjadi Draft.

Lead yang sudah menghasilkan Project tidak dapat dihapus.

Project hanya dibuat satu kali dari satu Lead.

---

# Validasi

Nomor HP harus valid.

Nominal Client lebih dari nol.

Untuk Client Mitra, Nominal Klien dan Nominal Mitra wajib lebih dari nol dan Mitra wajib dipilih.

`discount_total` untuk skema Mitra wajib nol.

Jumlah termin minimal satu.

Nama usaha tidak boleh kosong.

---

# Exception

Data belum lengkap.

↓

Lead tidak dapat disimpan.

---

Lead sudah Deal.

↓

Tidak dapat diedit.

---

Lead sudah memiliki Project.

↓

Tidak dapat dihapus.

---

# Activity Log

Catat aktivitas berikut.

- Membuat Lead.
- Mengubah Lead.
- Deal.
- Batal.

Referensi:

database/logs.md

---

# Notification

Lead Deal

↓

Finance menerima notifikasi.

Referensi:

08-notification.md

---

# KPI

Marketing dapat dimonitor berdasarkan:

- Jumlah Lead.
- Jumlah Deal.
- Jumlah Batal.
- Conversion Rate.
- Nilai layanan.
- Total Project yang dihasilkan.

---

# Hubungan Workflow

Workflow ini menghasilkan:

Client

↓

Project

↓

Workflow Finance

Dokumen ini hanya menjelaskan proses Marketing.

Workflow berikutnya dijelaskan pada:

workflow/finance.md
