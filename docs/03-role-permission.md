# 03. Role & Permission

## Tujuan

Dokumen ini mendefinisikan seluruh Role (Hak Akses) yang terdapat pada PHC System.

Seluruh proses otorisasi sistem harus mengacu pada dokumen ini.

Dokumen ini tidak menjelaskan alur kerja (workflow), tetapi hanya menjelaskan hak akses setiap Role.

---

# Referensi

Dokumen terkait:

- 00-overview.md
- 00-glossary.md
- 02-workflow.md
- 06-routing.md
- 07-status.md

---

# Prinsip Hak Akses

PHC System menggunakan Role Based Access Control (RBAC).

Setiap User memiliki tepat satu Role.

Hak akses ditentukan berdasarkan Role.

User hanya dapat melihat menu dan melakukan aksi yang diizinkan.

---

# Daftar Role

## Super Admin

Role dengan hak akses penuh.

### Tanggung Jawab

- Mengelola seluruh sistem
- Mengelola User
- Mengelola Role
- Mengelola Pengaturan
- Mengakses seluruh Project
- Membuat akun login Klien melalui aksi **Buat Akun**
- Melakukan override status progress dengan alasan wajib
- Menggunakan Dashboard Administrasi Sistem sebagai beranda

### Akses

✅ Semua Menu

---

## Direktur

Role monitoring.

### Tanggung Jawab

- Monitoring seluruh Project
- Monitoring Dashboard
- Melihat laporan

### Akses

Dashboard

Klien

Laporan

---

## Manager Operasional

Role pengawas operasional.

### Tanggung Jawab

- Monitoring seluruh proses
- Monitoring seluruh divisi
- Melihat seluruh Project

### Akses

Dashboard

Klien

Laporan

---

## Marketing

Role pencari klien.

### Tanggung Jawab

- Membuat Lead
- Mengubah Lead
- Mengubah Status Lead
- Deal / Batal

### Tidak Dapat

- Membuat Invoice
- Mengedit Project Aktif
- Mengubah Status Workflow

### Akses

Dashboard

Leads

Klien (Read Only)

---

## Finance

Role keuangan.

### Tanggung Jawab

- Memeriksa dan menerbitkan Invoice yang dibuat otomatis
- Verifikasi Pembayaran
- Mengelola Termin
- Mengelola Pelunasan

### Tidak Dapat

- Mengubah Dokumen
- Entry SIHALAL
- Audit

### Akses

Dashboard

Pembayaran (termasuk Invoice)

Klien

---

## Admin

Role administrasi Project.

### Tanggung Jawab

- Upload Dokumen
- Edit Dokumen
- Input Akun SIHALAL

### Tidak Dapat

- Approve Entry
- Approve Audit

### Akses

Dashboard

Klien

---

## Entry

Role Entry SIHALAL.

### Tanggung Jawab

- Membaca Dokumen
- Entry SIHALAL
- Menandai Entry Selesai
- Mengubah Status Entry untuk Project yang ditugaskan

### Tidak Dapat

- Approve Entry
- Mengubah Invoice

### Akses

Dashboard

Tugas

Klien

---

## SPV Entry

Role reviewer Entry.

### Tanggung Jawab

- Review Entry
- Approve
- Revisi
- Mengoreksi Status Entry saat review

### Akses

Dashboard

Tugas

Klien

---

## Pendamping Auditor

Role pendamping lapangan.

### Tanggung Jawab

- Booking Audit
- Menentukan Pendamping
- Input Temuan
- Upload Lampiran Audit
- Mengubah Status Pendamping untuk Project yang ditugaskan

### Tidak Dapat

- Approve Audit

### Akses

Dashboard

Tugas

Klien

---

## Auditor

Role pemeriksa audit.

### Tanggung Jawab

- Review Audit
- Approve Audit
- Revisi Audit
- Mengubah Status Auditor sampai Laporan Audit Selesai

### Akses

Dashboard

Tugas

Klien

---

## Admin Perusahaan

Role administrasi akhir.

### Tanggung Jawab

- Upload Invoice Negara
- Upload Sertifikat
- Input Nomor Sertifikat
- Mengubah milestone Auditor pasca-audit dari Menunggu Sidang Fatwa sampai Sertifikat Halal Terbit

### Tidak Dapat

- Mengubah Workflow A
- Mengubah hasil pemeriksaan Audit 0%–75%; hanya milestone pasca-audit 82%–100% yang dapat diperbarui

### Akses

Dashboard

Klien

Tugas

---

## Klien

Role eksternal.

### Tanggung Jawab

Melihat data miliknya sendiri melalui `/dashboard`.

### Dapat Melihat

- Detail Klien miliknya
- Project miliknya
- Status dan progress Project
- Tahap workflow yang boleh ditampilkan
- Dokumen miliknya yang ditandai dapat dilihat Klien
- Pembayaran miliknya (Invoice dan histori Payment)
- Sertifikat miliknya
- Timeline yang ditandai dapat dilihat Klien
- Notifikasi miliknya

### Tidak Dapat

- Mengubah data Project
- Melihat data Klien lain
- Mengakses route operasional selain `/dashboard`
- Mengirim `client_id` atau `project_id` untuk memperluas scope data

---

# Permission Matrix

| Modul | Super Admin | Direktur | Manager Operasional | Marketing | Finance | Admin | Entry | SPV Entry | Pendamping Auditor | Auditor | Admin Perusahaan | Klien |
|---------|:----------:|:--------:|:-------:|:---------:|:-------:|:-----:|:-----:|:---:|:-----------:|:-------:|:----------------:|:------:|
| Dashboard | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | 👁 Data Sendiri |
| Leads | ✅ | 👁 | 👁 | CRUD | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| Klien | CRUD | 👁 | 👁 | 👁 | 👁 | Edit | 👁 | 👁 | 👁 | 👁 | Edit | ❌ |
| Tugas | View/Update | 👁 | View/Update | ❌ | ❌ | View/Update | View/Update | View/Update | View/Update | View/Update | View/Update | ❌ |
| Pembayaran (Invoice & Payment) | View/Update | 👁 | 👁 | 👁 Invoice | View/Update | ❌ | ❌ | ❌ | ❌ | ❌ | 👁 Invoice | ❌ |
| Dokumen | CRUD | 👁 | 👁 | 👁 | 👁 | CRUD | 👁 | 👁 | Upload | 👁 | Upload | ❌ |
| Entry | CRUD | 👁 | 👁 | ❌ | ❌ | 👁 | Edit | Approve | ❌ | ❌ | ❌ | ❌ |
| Audit | CRUD | 👁 | 👁 | ❌ | ❌ | 👁 | ❌ | ❌ | Edit | Approve | ❌ | ❌ |
| Sertifikat | CRUD | 👁 | 👁 | 👁 | 👁 | 👁 | 👁 | 👁 | 👁 | 👁 | Upload | ❌ |
| User | CRUD | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| Setting | CRUD | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |

---

## Permission Status Progress

Matrix status progress berikut memperinci permission modul Entry/Audit di atas:

| Jalur | Super Admin | Entry | SPV Entry | Pendamping Auditor | Auditor | Admin Perusahaan | Internal Lain | Klien |
|---|---|---|---|---|---|---|---|---|
| Status Entry | Override | Update Assigned | Review/Update | View | View | View | View | View Own |
| Status Pendamping | Override | View | View | Update Assigned | View | View | View | View Own |
| Status Auditor 0%–75% | Override | View | View | View | Update Assigned | View | View | View Own |
| Status Auditor 82%–100% | Override | View | View | View | View | Update | View | View Own |

`Override` oleh Super Admin selalu meminta alasan. `View Own` pada Klien hanya berlaku melalui `/dashboard` dan tidak membuka endpoint perubahan.

---

# Permission Level

Hak akses menggunakan empat level.

| Permission | Keterangan |
|------------|------------|
| View | Hanya melihat |
| Create | Membuat data |
| Update | Mengubah data |
| Delete | Menghapus data |

CRUD merupakan gabungan seluruh permission di atas.

Tanda `❌` pada kolom Klien berarti route modul tidak dapat diakses. Data milik Klien yang relevan tetap ditampilkan secara read-only melalui Dashboard.

---

# Aturan Otorisasi

1. User harus Login.
2. User hanya memiliki satu Role.
3. Seluruh request API wajib melakukan pengecekan Role.
4. Menu, tombol, tab, action, shortcut, dan tautan disembunyikan apabila User tidak memiliki izin.
5. User tidak boleh melihat halaman penolakan akses. Apabila membuka route tanpa kewenangan secara langsung, sistem mengarahkan User ke halaman aman yang dapat diakses, dengan prioritas `/dashboard`.
6. Seluruh aksi penting dicatat pada Activity Log.
7. Role Klien hanya dapat mengakses `/dashboard`.
8. Query role Klien wajib dibatasi menggunakan `client_id` milik User yang sedang Login.
9. `client_id` dari query parameter, request body, atau URL tidak boleh digunakan untuk menentukan scope role Klien.
10. Seluruh staf internal dapat melihat detail Client beserta Project tunggalnya; aksi tetap mengikuti Role, Permission, dan Assignment.
11. Menu Tugas wajib tersedia untuk Admin Perusahaan, Entry, SPV Entry, Auditor, dan Pendamping Auditor.
12. Data operasional tidak menyediakan Create generik, Bulk Action, atau Delete. Create manual hanya tersedia pada Leads; aksi **Buat Akun** merupakan aksi khusus Super Admin.
13. Penyembunyian UI bukan pengganti authorization. Policy, middleware, ownership scope, dan query scope tetap wajib diterapkan pada backend.
14. Seluruh staf internal dapat melihat Tipe Klien. Nominal Client, Nominal Mitra, dan Invoice Mitra mengikuti permission keuangan; Role Klien hanya melihat Invoice audience Client dan tidak dapat melihat data Mitra.
15. Seluruh staf internal selain Super Admin menggunakan Dashboard Progres Operasional yang sama; scope data adalah organisasi, sedangkan action tetap dibatasi Role, Permission, dan Assignment.
16. Super Admin menggunakan Dashboard Administrasi Sistem sebagai beranda dan dapat membuka halaman monitoring operasional dari menu Client atau Laporan.
17. Status Entry, Pendamping, dan Auditor dipilih manual oleh role pemilik jalur. Persentase diturunkan otomatis dari `07-status.md` dan tidak dapat dikirim sebagai input.
18. Status yang turun progress wajib memiliki alasan. Override Super Admin selalu wajib memiliki alasan.
19. Admin Perusahaan hanya dapat mengubah Status Auditor pada milestone 82%–100% sebagaimana didefinisikan pada `07-status.md`.

---

# Pembuatan Akun Login Klien

Data Client dan akun login Klien merupakan dua hal berbeda.

- Data Client dibuat otomatis ketika Lead berubah menjadi Deal.
- Akun login Klien hanya dibuat oleh Super Admin melalui tombol **Buat Akun** pada detail Client.
- Super Admin tidak mengisi form akun secara manual.
- Sistem mengambil nama, relasi `client_id`, dan data kontak dari Client.
- Sistem membuat email unik dengan pola `{user}@primahalalcendekia.com`.
- Nilai `{user}` dibuat otomatis dari identitas Client dan harus unik.
- Aksi bersifat idempotent: Client yang telah memiliki akun tidak boleh dibuatkan akun kedua melalui tombol yang sama.
- Aktivitas pembuatan akun wajib tercatat pada Activity Log.

---

# Perubahan Role

Perubahan Role hanya dapat dilakukan oleh Super Admin.

Perubahan Role harus tercatat pada Activity Log.

---

# Hubungan Dengan Workflow

Workflow tidak menentukan Role.

Role menjalankan Workflow.

Referensi:

02-workflow.md

---

# Hubungan Dengan Routing

Setiap Route memiliki daftar Role yang diperbolehkan.

Referensi:

06-routing.md

---

# Hubungan Dengan API

Setiap Endpoint API harus memiliki middleware authorization.

Referensi:

05-api.md

---

# Hubungan Dengan Database

Role disimpan pada tabel:

users

Referensi:

database/users.md
