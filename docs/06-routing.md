# 06. Routing

## Tujuan

Dokumen ini mendefinisikan seluruh Route (URL) yang digunakan pada PHC System.

Dokumen ini hanya menjelaskan struktur navigasi aplikasi.

Dokumen ini tidak menjelaskan business logic maupun workflow.

---

# Referensi

Dokumen terkait:

- 00-overview.md
- 02-workflow.md
- 03-role-permission.md
- 05-api.md
- 09-ui-ux.md

---

# Prinsip Routing

PHC System menggunakan Route berbasis Modul (Module Based Routing).

Route tidak mengikuti Role.

Route tidak mengikuti Workflow.

Route mengikuti Modul Sistem.

Contoh:

✓ /clients

✓ /projects

✓ /payments

✗ /marketing

✗ /entry

✗ /audit

Role menentukan hak akses terhadap Route, bukan nama Route.

---

# Struktur Routing

/

├── login

├── dashboard

├── leads

├── clients

├── tasks

├── payments

├── notifications

├── reports

├── settings

├── users

└── profile

---

# Public Route

Route yang tidak membutuhkan Login.

| Route | Keterangan |
|--------|------------|
| /login | Login |
| /forgot-password | Lupa Password |

---

# Protected Route

Seluruh Route berikut memerlukan Login.

---

## Dashboard

Route

```
/dashboard
```

Deskripsi

Dashboard utama sistem.

Untuk role Klien, route ini merupakan satu-satunya protected route dan berfungsi sebagai portal read-only untuk seluruh data miliknya.

Resolver Dashboard berdasarkan Role:

- seluruh staf internal selain Super Admin: Dashboard Progres Operasional organisasi
- Super Admin: Dashboard Administrasi Sistem
- Klien: Dashboard Klien self-scoped

Dashboard Progres Operasional menampilkan struktur KPI, ringkasan tahap, panduan cepat, chart, dan daftar prioritas sesuai `ui/dashboard.md`.

Referensi UI

ui/dashboard.md

Hak Akses

Semua Role.

Mode Klien:

- hanya menampilkan data berdasarkan `client_id` pada User yang sedang Login
- tidak menerima `client_id` atau `project_id` sebagai penentu scope
- seluruh detail dibuka sebagai section, tab, drawer, atau modal di `/dashboard`

Status progress diubah dari detail Client, drawer Dashboard, atau detail Tugas. Tidak dibuat route halaman terpisah untuk setiap status.

---

## Leads

Route

```
/leads
```

Deskripsi

Manajemen Lead.

Menu:

- Daftar Lead
- Tambah Lead
- Edit Lead
- Deal
- Batal

Referensi

workflow/marketing.md

Hak Akses

- Super Admin
- Marketing

Read Only:

- Direktur
- Manager Operasional

---

## Clients

Route

```
/clients
```

Deskripsi

Daftar seluruh Klien sekaligus pintu masuk workspace Project tunggalnya.

Halaman ini menjadi pusat informasi Client dan pekerjaan Project.

Referensi

database/clients.md

ui/klien.md

Hak Akses

Semua role internal.

Hak Edit mengikuti Permission.

Role Klien tidak dapat mengakses route ini.

---

## Projects

Route

```
/projects
```

Deskripsi

Daftar operasional yang menampilkan Project berdasarkan ID Client.

Project merupakan pusat proses internal, tetapi tidak memiliki Business ID yang ditampilkan.

Setiap Project memiliki:

- Timeline
- Workflow
- Dokumen
- Pembayaran (Invoice dan Payment)
- Activity Log
- Notification

Referensi

database/projects.md

workflow/

Hak Akses

Semua role internal sesuai permission dan Assignment.

Role Klien tidak dapat mengakses route ini.

Membuka sebuah baris mengarah ke `/clients/{clientId}`.

---

## Client Detail dan Project Workspace

Route

```
/clients/{clientId}
```

Deskripsi

Menampilkan detail Client beserta satu Project miliknya.

Halaman ini merupakan halaman kerja utama.

Isi halaman:

- Informasi Project
- Tipe Klien dan Mitra
- Nominal komersial sesuai permission
- Workflow
- Dokumen
- Pembayaran (Invoice dan Payment)
- Timeline
- Activity Log
- Sertifikat

Referensi

ui/klien.md

Hak Akses

Semua role internal dapat melihat. Action mengikuti permission dan Assignment.

Role Klien tidak dapat mengakses route ini.

---

## Tasks

Route

```
/tasks
```

Deskripsi

Daftar tugas operasional milik User berdasarkan Assignment, Workflow, Status Project, dan Role.

Referensi

ui/tugas.md

Hak Akses

- Super Admin
- Manager Operasional
- Admin
- Entry
- SPV Entry
- Pendamping Auditor
- Auditor
- Admin Perusahaan

Navigation menu menampilkan badge angka berisi jumlah tugas milik User yang belum berstatus Selesai.

Halaman Tugas tidak menyediakan Create, Delete, atau Bulk Action.

---

## Payments

Route

```
/payments
```

Deskripsi

Workspace tunggal untuk mengelola Invoice dan transaksi Payment.

Referensi

database/invoices.md

database/payments.md

ui/pembayaran.md

Subroute:

```
/payments/invoices
/payments/transactions
```

Tab default adalah Invoice.

Hak Akses

Finance

Super Admin

Read Only:

Direktur

Manager Operasional

Marketing

Admin Perusahaan

---

### Payment Transactions

Route

```
/payments/transactions
```

Deskripsi

Daftar seluruh transaksi pembayaran di dalam modul Payment.

Referensi

database/payments.md

Hak Akses

Finance

Super Admin

Read Only:

Direktur

Manager Operasional

---

## Notifications

Route

```
/notifications
```

Deskripsi

Daftar seluruh notifikasi milik User yang sedang Login.

Referensi

08-notification.md

Hak Akses

Semua role internal.

Notifikasi role Klien ditampilkan di `/dashboard`, bukan melalui route ini.

---

## Reports

Route

```
/reports
```

Deskripsi

Laporan operasional.

Referensi

ui/laporan.md

Hak Akses

Manager Operasional

Direktur

Super Admin

---

## Users

Route

```
/users
```

Deskripsi

Manajemen User.

Hak Akses

Super Admin

---

## Settings

Route

```
/settings
```

Deskripsi

Pengaturan Sistem.

Hak Akses

Super Admin

---

## Profile

Route

```
/profile
```

Deskripsi

Profil User yang sedang Login.

Hak Akses

Semua role internal.

Profil role Klien ditampilkan di `/dashboard`.

---

# Dynamic Route

Project

```
/clients/{clientId}/project
```

Client

```
/clients/{clientId}
```

Invoice

```
/payments/invoices/{invoiceId}
```

Payment

```
/payments/transactions/{paymentId}
```

User

```
/users/{userId}
```

---

# Route Guard

Setiap Route memiliki Middleware.

Contoh:

Auth

↓

Role

↓

Permission

↓

Render Page

Apabila User tidak memiliki izin, sistem melakukan redirect ke route aman yang dapat diakses User, dengan prioritas `/dashboard`.

Sistem tidak menampilkan halaman penolakan akses. Redirect dapat disertai pesan netral seperti:

`Halaman tidak tersedia untuk akun Anda.`

Menu, tombol, tab, shortcut, dan link menuju route tersebut tidak dirender sejak awal.

Khusus role Klien:

- `/dashboard` diizinkan
- seluruh protected route lain diarahkan kembali ke `/dashboard`
- dynamic route dengan ID Client, Project, Invoice, Payment, Document, atau Certificate tidak boleh dibuka

Route Guard tetap menjalankan authorization pada server. Penyembunyian navigation bukan mekanisme keamanan.

---

# Navigasi Sidebar

Sidebar mengikuti Role.

Contoh

Marketing

- Dashboard
- Leads
- Clients

Finance

- Dashboard
- Clients
- Pembayaran

Entry

- Dashboard
- Tugas
- Clients

Direktur

- Dashboard
- Clients
- Reports

Super Admin

- Dashboard Administrasi Sistem
- Semua Menu

Klien

- Dashboard

Admin Perusahaan, Entry, SPV Entry, Auditor, dan Pendamping Auditor wajib memiliki menu **Tugas** dengan badge jumlah tugas belum dikerjakan.

Seluruh role internal selain Super Admin melihat Dashboard Progres Operasional yang sama. Perbedaan role hanya memengaruhi action, dropdown yang dapat diubah, dan menu lain yang dirender.

Referensi

03-role-permission.md

---

# Breadcrumb

Seluruh halaman menggunakan Breadcrumb.

Contoh

Dashboard

↓

Klien

↓

CLIENT-2026-0001

---

# URL Convention

Gunakan:

- huruf kecil
- kebab-case
- plural

Contoh

```
/projects
/project-types
/document-types
```

Jangan gunakan

```
/Project

/getProjects

/createInvoice
```

---

# Query Parameter

Filtering

```
/projects?status=active
```

Searching

```
/projects?search=ayam
```

Sorting

```
/projects?sort_by=created_at
```

Pagination

```
/projects?page=2
```

---

# Referensi

Routing hanya menjelaskan URL.

Business Process:

01-business.md

Workflow:

02-workflow.md

Permission:

03-role-permission.md

API:

05-api.md

UI:

09-ui-ux.md
