# 09. UI / UX

## Tujuan

Dokumen ini mendefinisikan prinsip User Interface (UI) dan User Experience (UX) pada PHC System.

Tujuannya adalah memastikan seluruh halaman memiliki tampilan, perilaku, dan pola interaksi yang konsisten.

Dokumen ini tidak menjelaskan desain setiap halaman secara detail.

Spesifikasi masing-masing halaman terdapat pada folder `/ui`.

---

# Referensi

Dokumen terkait:

- 02-workflow.md
- 03-role-permission.md
- 06-routing.md
- 07-status.md
- 10-design-system.md

Detail Halaman:

- ui/dashboard.md
- ui/leads.md
- ui/klien.md
- ui/invoice.md
- ui/pembayaran.md
- ui/tugas.md
- ui/laporan.md
- ui/settings.md

---

# Filosofi UI

PHC System merupakan aplikasi operasional.

Prioritas utama adalah:

- Cepat digunakan
- Mudah dipahami
- Sedikit klik
- Konsisten
- Fokus pada pekerjaan

Visual menarik bukan tujuan utama.

Efisiensi operasional lebih diutamakan.

---

# Prinsip UX

Seluruh halaman mengikuti prinsip berikut.

## Simple

Informasi penting langsung terlihat.

---

## Consistent

Komponen yang sama memiliki perilaku yang sama.

---

## Efficient

Semua pekerjaan dapat diselesaikan dengan klik seminimal mungkin.

---

## Predictable

User dapat menebak lokasi tombol dan informasi.

---

## Responsive

Aplikasi dapat digunakan pada Desktop maupun Tablet.

Desktop merupakan prioritas utama.

---

# Layout Aplikasi

Layout utama terdiri dari:

Header

↓

Sidebar

↓

Content

↓

Footer (opsional)

---

# Header

Header berisi:

- Logo
- Judul Halaman
- Global Search
- Notification
- Profile Menu

Header selalu terlihat saat berpindah halaman.

---

# Sidebar

Sidebar digunakan sebagai navigasi utama.

Menu yang tampil mengikuti Role User.

Sidebar dapat:

- Expand
- Collapse

---

# Content Area

Content merupakan area kerja utama.

Setiap halaman memiliki:

- Page Title
- Breadcrumb
- Action Bar
- Content

---

# Struktur Halaman

Setiap halaman menggunakan struktur berikut.

Page Header

↓

Filter

↓

Action

↓

Table / Card

↓

Pagination

---

# Navigasi

User tidak boleh melakukan navigasi yang berlebihan.

Target maksimal:

3 klik menuju informasi utama.

---

# Dashboard

Dashboard menampilkan Dashboard Progres Operasional organisasi untuk seluruh staf internal selain Super Admin, Dashboard Administrasi Sistem untuk Super Admin, dan ringkasan data milik sendiri untuk role Klien.

Dashboard Progres Operasional mengikuti susunan:

- header PHC Halal Progress Dashboard
- dua baris KPI
- ringkasan tahap dan panduan cepat
- bar chart distribusi tahap sertifikasi
- donut chart kondisi pembaruan data
- daftar Project prioritas

Dashboard bukan halaman CRUD. Satu-satunya perubahan kontekstual yang diperbolehkan adalah dropdown Status Entry, Pendamping, dan Auditor bagi role yang berwenang. Persentase selalu otomatis mengikuti status.

Untuk role Klien, Dashboard adalah satu-satunya halaman aplikasi dan seluruh informasi bersifat read-only.

Dashboard berisi:

- Statistik
- Progress
- Notifikasi
- Tugas
- Aktivitas Terbaru

Dashboard Klien juga berisi detail Client, progress Project, dokumen publik, Pembayaran, Sertifikat, dan Timeline miliknya.

Action, dropdown, tab, shortcut, dan menu yang tidak berizin tidak dirender. Navigasi langsung ke web route yang tidak berizin kembali ke `/dashboard`, sehingga tidak ada halaman 403 yang terlihat oleh pengguna.

Referensi:

ui/dashboard.md

---

# Halaman Master

Contoh:

- Leads
- Clients
- Users

Menggunakan tampilan:

Filter

↓

Table

↓

Drawer Detail

↓

Modal Edit

Tidak menggunakan halaman detail terpisah kecuali diperlukan.

---

# Halaman Operasional

Contoh:

Clients

Projects

Workflow

Menggunakan:

Table

↓

Drawer

↓

Tab

↓

Action

User tidak perlu berpindah halaman untuk pekerjaan umum.

---

# Drawer

Drawer digunakan untuk:

- Melihat Detail
- Edit Ringan
- Progress
- Timeline
- Activity Log

Drawer lebih diutamakan daripada halaman baru.

---

# Modal

Modal digunakan untuk:

- Konfirmasi
- Form Singkat
- Upload
- Approval
- Revisi

Modal tidak digunakan untuk Form yang panjang.

---

# Form

Form mengikuti prinsip berikut.

- Label jelas
- Placeholder seperlunya
- Validasi langsung
- Error di bawah field
- Required diberi tanda (*)

---

# Tabel

Sebagian besar data menggunakan Table.

Tabel mendukung:

- Search
- Filter
- Sorting
- Pagination
- Column Resize (opsional)
- Column Hide (opsional)

---

# Search

Search selalu berada di atas Table.

Search bersifat realtime atau menggunakan tombol Search.

---

# Filter

Filter ditempatkan sebelum Table.

Contoh:

- Status
- Marketing
- Tahun
- Jenis Layanan

Filter dapat digabungkan.

---

# Sorting

Setiap kolom yang relevan dapat diurutkan.

Contoh:

- Nama
- Tanggal
- Nominal
- Status

---

# Pagination

Pagination berada di bawah Table.

Informasi yang ditampilkan:

- Total Data
- Halaman
- Data per Halaman

---

# Empty State

Apabila tidak ada data.

Tampilkan:

- Ilustrasi sederhana
- Pesan
- Tombol aksi (jika diperlukan)

Contoh:

```
Belum ada Invoice.
```

---

# Loading State

Saat mengambil data.

Gunakan:

- Skeleton
- Loading Spinner

Hindari halaman kosong.

---

# Error State

Jika terjadi kesalahan.

Tampilkan:

- Pesan Error
- Tombol Muat Ulang

---

# Success Feedback

Setelah aksi berhasil.

Gunakan:

- Toast Notification

Contoh:

```
Invoice berhasil dibuat.
```

---

# Confirmation

Aksi penting memerlukan konfirmasi.

Contoh:

- Delete
- Cancel
- Approve
- Reject

---

# Aturan Aksi Data

- Bulk Action dinonaktifkan pada halaman operasional.
- Delete tidak ditampilkan.
- Create manual hanya tersedia pada menu Leads.
- Resource setelah Lead Deal dibuat otomatis oleh workflow.
- Context action seperti **Buat Akun**, Publish, Submit, Approve, Revisi, Verifikasi, dan Selesai tetap tersedia sesuai Role dan status.

---

# Visibility Berdasarkan Permission

- Komponen yang tidak diizinkan tidak dirender.
- Berlaku untuk navigation menu, tombol, tab, action, shortcut, widget, dan link.
- User tidak melihat halaman penolakan akses.
- Akses URL langsung diarahkan ke halaman aman yang dapat diakses, dengan prioritas Dashboard.
- Tampilkan pesan netral tanpa membocorkan nama resource atau permission internal.
- Backend tetap wajib menjalankan authorization dan ownership scope.

---

# Status

Status menggunakan Badge.

Contoh:

Aktif

Selesai

Review

Revisi

Lunas

Referensi:

07-status.md

---

# Workflow

Workflow ditampilkan dalam bentuk Step Progress.

Contoh:

Dokumen

↓

Entry

↓

SPV

↓

Audit

↓

Sertifikat

↓

Selesai

---

# Activity Log

Activity Log ditampilkan dalam Timeline.

Urutan:

Terbaru di atas.

---

# Notification

Notification menggunakan:

- Bell Icon
- Badge
- Dropdown
- Halaman Semua Notifikasi

Referensi:

08-notification.md

---

# Aksesibilitas

Seluruh halaman harus:

- Mendukung navigasi keyboard
- Memiliki kontras yang baik
- Menggunakan ukuran teks yang mudah dibaca
- Memiliki label pada seluruh input

---

# Responsive

Prioritas tampilan:

1. Desktop
2. Tablet

Mobile hanya mendukung fungsi dasar.

---

# Konsistensi

Komponen yang sama harus memiliki:

- Warna yang sama
- Ukuran yang sama
- Posisi yang sama
- Perilaku yang sama

---

# Detail Halaman

Dokumen ini hanya menjelaskan prinsip UI.

Detail setiap halaman terdapat pada folder:

ui/
