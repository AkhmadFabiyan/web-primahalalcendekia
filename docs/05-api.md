# 05. API

## Tujuan

Dokumen ini mendefinisikan standar API yang digunakan pada PHC System.

Dokumen ini tidak menjelaskan implementasi controller maupun business logic.

Seluruh endpoint harus mengikuti aturan yang terdapat pada dokumen ini.

---

# Referensi

Dokumen terkait:

- 00-overview.md
- 00-glossary.md
- 02-workflow.md
- 03-role-permission.md
- 04-database.md
- 06-routing.md
- 07-status.md

---

# Arsitektur API

PHC System menggunakan REST API.

Setiap Resource memiliki endpoint sendiri.

Contoh Resource:

- Authentication
- Users
- Leads
- Clients
- Projects
- Documents
- Invoices
- Payments
- Notifications

Daftar di atas adalah resource API, bukan daftar modul aplikasi. Resource `Invoices` dan `Payments` sama-sama dimiliki oleh modul Payment.

---

# Base URL

Contoh:

/api/v1

Seluruh endpoint berada di bawah versi API.

Contoh:

/api/v1/projects

---

# Format Response

Seluruh API wajib menggunakan format response yang sama.

Success

```json
{
  "success": true,
  "message": "Project berhasil dibuat.",
  "data": {}
}
```

Error

```json
{
  "success": false,
  "message": "Data tidak ditemukan.",
  "errors": {}
}
```

---

# Authentication

Seluruh endpoint (kecuali Login) wajib menggunakan Authentication.

Contoh:

Authorization: Bearer {token}

---

# Authorization

Hak akses mengikuti Role.

Referensi:

03-role-permission.md

Contoh:

Marketing

✓ Create Lead

✗ Approve Audit

Finance

✓ Review dan Publish Invoice

✗ Upload Sertifikat

Role Klien:

- hanya menggunakan endpoint self-scoped Client Dashboard
- scope diambil dari `client_id` pada User yang sedang Login
- tidak boleh menentukan `client_id` atau `project_id` melalui request
- endpoint resource umum seperti `/clients`, `/projects`, dan `/payments` tidak diekspos pada navigasi atau request Klien
- request langsung ke endpoint di luar scope diperlakukan sebagai resource tidak tersedia dan mengembalikan 404

---

# Resource API

## Client Dashboard

Endpoint read-only khusus role Klien:

GET /client/dashboard

Response menggabungkan data milik Client yang terhubung dengan User:

- profil Client
- Project dan progress
- status workflow publik
- dokumen dengan `is_client_visible = true`
- Invoice dan Payment milik Project
- Sertifikat
- Timeline dengan `is_client_visible = true`
- notifikasi milik User

Endpoint detail yang diperlukan tetap self-scoped:

GET /client/dashboard/documents/{id}/download

GET /client/dashboard/certificates/{id}/download

Server wajib memverifikasi bahwa resource berelasi ke `users.client_id`. Resource milik Client lain harus dikembalikan sebagai 404 agar keberadaannya tidak terungkap.

## Internal Operational Dashboard

Endpoint read-only untuk staf internal selain Super Admin:

GET /dashboard/operational-progress

Response minimal:

- KPI Total Klien, Proses Entry, Menunggu Audit, dan Sertifikat Terbit
- KPI Audit 7 Hari, Proses Revisi, Perlu Follow Up, dan Kritis lebih dari tujuh hari
- ringkasan tahap sertifikasi
- distribusi tahap
- kondisi pembaruan data
- daftar prioritas
- `generated_at`

Query menggunakan Project unik sebagai basis hitung agar pasangan Invoice Mitra, banyak Assignment, atau banyak dokumen tidak menggandakan Client. Filter yang diizinkan: periode, layanan, tipe Client, PIC, dan status.

Super Admin menggunakan endpoint kesehatan/administrasi sistem untuk beranda. Role Klien tidak boleh mengakses endpoint ini.

## Manual Progress Status

Endpoint staf internal:

PATCH /clients/{clientId}/progress/entry

PATCH /clients/{clientId}/progress/companion

PATCH /clients/{clientId}/progress/auditor

Request:

```json
{
  "status": "DOCUMENT_REVISION",
  "note": "Dokumen bahan perlu diperbarui."
}
```

Rule:

- `clientId` adalah ID teknis route yang di-resolve ke satu Client dan satu Project; response menampilkan Business ID sebagai ID Klien
- status wajib berupa enum resmi sesuai jalur pada `07-status.md`
- `progress_percent` tidak diterima
- server menghitung label dan persentase dari status
- perubahan mundur wajib memiliki `note`
- Super Admin override wajib memiliki `note`
- Entry/SPV Entry, Pendamping Auditor, Auditor, dan Admin Perusahaan hanya dapat mengubah rentang status yang diizinkan `03-role-permission.md`
- Assignment aktif wajib diverifikasi untuk pengubah utama
- endpoint idempotent untuk status yang sama dan tidak membuat histori duplikat tanpa perubahan
- perubahan memakai transaction, row lock, histori append-only, dan Activity Log
- `HALAL_CERTIFICATE_ISSUED` wajib memiliki Sertifikat valid

Response minimal:

```json
{
  "track": "ENTRY",
  "status": "DOCUMENT_REVISION",
  "label": "Revisi Dokumen",
  "progress_percent": 90,
  "changed_at": "2026-07-31T10:00:00+07:00",
  "changed_by": {
    "id": "uuid",
    "name": "Nama User"
  }
}
```

Pengguna web yang tidak berwenang tidak melihat dropdown/action. Request API langsung di luar scope diperlakukan sebagai resource tidak tersedia dan mengembalikan 404, bukan halaman 403.

## Authentication

Digunakan untuk Login, Logout, dan Refresh Session.

Endpoint:

POST /auth/login

POST /auth/logout

GET /auth/me

---

## Users

Mengelola User.

Contoh:

GET /users

POST /users

PATCH /users/{id}

DELETE /users/{id}

---

## Leads

Mengelola Lead.

Contoh:

GET /leads

POST /leads

PATCH /leads/{id}

PATCH /leads/{id}/deal

PATCH /leads/{id}/cancel

Field komersial:

- `client_type`: `DIRECT` atau `PARTNER`
- `partner_id` untuk Partner existing atau data Partner baru untuk `PARTNER`
- `client_nominal`
- `partner_nominal`: wajib untuk `PARTNER`

Untuk `DIRECT`, `partner_id` dan `partner_nominal` harus kosong.

---

## Clients

Mengelola Client.

Contoh:

GET /clients

GET /clients/{id}

PATCH /clients/{id}

GET /clients/{id}/project

POST /clients/{id}/login-account

Rule:

- Client dan Project tidak dibuat melalui endpoint Create umum; keduanya dibuat otomatis oleh aksi Lead Deal.
- `POST /clients/{id}/login-account` hanya dapat dijalankan Super Admin melalui aksi **Buat Akun**.
- Endpoint pembuatan akun tidak menerima nama, email, role, atau `client_id` dari request.
- Email dibuat otomatis dengan pola `{user}@primahalalcendekia.com`.
- Kredensial awal atau token aktivasi dibuat otomatis secara aman.
- Endpoint harus idempotent dan mengembalikan konflik terkontrol jika akun sudah tersedia.

Detail Client internal menampilkan `client_type`, Partner, Nominal Client, dan Nominal Partner sesuai permission.

---

## Partners

Master Partner untuk Client bertipe Mitra.

Contoh:

GET /partners

GET /partners/{id}

Partner dipilih ketika Lead atau Client bertipe Mitra. Partner bukan transaksi dan tidak memiliki Project.

---

## Projects

Mengelola Project internal yang berelasi 1:1 dengan Client.

Contoh:

GET /projects

GET /clients/{clientId}/project

PATCH /clients/{clientId}/project

Project tetap memiliki UUID internal, tetapi API bisnis tidak mengekspos Business ID Project. Identitas bisnis pada request dan response menggunakan ID Client.

---

## Tasks

Mengelola antrean tugas hasil workflow.

Contoh:

GET /tasks

GET /tasks/{id}

PATCH /tasks/{id}/start

PATCH /tasks/{id}/complete

GET /tasks/uncompleted-count

Task tidak menyediakan endpoint Create, Delete, atau Bulk Action. Task dibuat otomatis oleh event workflow.

---

## Documents

Mengelola Dokumen.

Contoh:

GET /projects/{id}/documents

POST /projects/{id}/documents

DELETE /documents/{id}

---

## Payment Module

Invoice dan Payment dikelola oleh satu modul Payment. Pemisahan endpoint tetap digunakan karena keduanya merupakan resource yang berbeda.

### Invoices

Mengelola Invoice.

Contoh:

GET /payments/invoices

PATCH /payments/invoices/{id}

GET /payments/invoices/{id}

PATCH /payments/invoices/{id}/publish

Field wajib:

- `billing_group_id`
- `audience`: `CLIENT` atau `PARTNER`
- `partner_id` untuk audience Partner
- `subtotal`
- `discount_total`
- `total`

`billing_snapshot` dibuat server dari Client atau Partner saat Invoice diterbitkan dan tidak diterima sebagai input bebas.

Untuk penagihan komersial Client Mitra, API mengembalikan pasangan Invoice Client dan Invoice Partner dalam satu `billing_group_id`. Keduanya memiliki `project_id` dan ID Client yang sama. Invoice Government tidak dipasangkan.

`discount_total` wajib `0` untuk seluruh Invoice pada skema Mitra.

Status billing group Mitra hanya Lunas apabila kedua Invoice berstatus `PAID`. Transisi aktivasi atau pelunasan menggunakan status billing group, bukan satu Payment individual.

---

### Payments

Mengelola Pembayaran.

Contoh:

GET /payments/transactions

POST /payments/transactions

PATCH /payments/transactions/{id}/verify

---

## Notifications

Mengelola Notifikasi.

Contoh:

GET /notifications

PATCH /notifications/read

---

# HTTP Method

Gunakan HTTP Method sesuai standar.

| Method | Fungsi |
|---------|--------|
| GET | Mengambil data |
| POST | Membuat data |
| PATCH | Mengubah sebagian data |
| PUT | Mengganti seluruh data |
| DELETE | Menghapus data |

---

# Penamaan Endpoint

Gunakan:

- huruf kecil
- plural
- kebab-case

Contoh:

/projects

/project-assignments

/document-types

Jangan gunakan:

/Project

/GetProject

/createInvoice

---

# Filtering

Gunakan Query Parameter.

Contoh:

GET /projects?status=active

GET /projects?marketing_id=25

GET /projects?search=ayam

GET /payments/invoices?audience=PARTNER

---

# Sorting

Gunakan:

sort_by

sort_order

Contoh:

GET /projects?sort_by=created_at&sort_order=desc

---

# Pagination

Gunakan:

page

per_page

Contoh:

GET /projects?page=1&per_page=20

---

# Upload File

Upload menggunakan multipart/form-data.

Contoh:

POST /projects/{id}/documents

---

# Status Code

| Status | Arti |
|---------|------|
| 200 | Success |
| 201 | Created |
| 400 | Bad Request |
| 401 | Unauthorized |
| 404 | Not Found |
| 409 | Conflict |
| 422 | Validation Error |
| 500 | Internal Server Error |

---

# Validation

Semua input wajib divalidasi.

Contoh:

- Required
- Email
- Nomor HP
- Nominal
- Tanggal
- Enum Status
- Tipe Client
- Partner
- Nominal Client dan Nominal Partner
- Audience Invoice

Validasi lintas-field:

- Client Langsung tidak menerima Partner atau Nominal Partner.
- Client Mitra wajib memiliki Partner existing atau data Partner baru, Nominal Client, dan Nominal Partner.
- Pasangan Invoice Mitra wajib menggunakan satu billing group.
- Discount Invoice Mitra wajib nol.

---

# Business Rule

API tidak boleh mengizinkan proses yang melanggar Workflow.

Contoh:

Tidak boleh membuat Sertifikat apabila Workflow belum selesai.

Tidak boleh membuat Payment apabila Invoice belum ada.

Tidak boleh menghasilkan Invoice apabila Lead belum Deal.

Data operasional tidak menyediakan Create generik, Delete, atau Bulk Action. Create manual hanya tersedia untuk Leads. Pembuatan resource setelah Lead Deal dilakukan oleh workflow atau context action yang terdokumentasi, termasuk aksi **Buat Akun** oleh Super Admin.

Referensi:

02-workflow.md

07-status.md

---

# Activity Log

Seluruh endpoint yang mengubah data wajib membuat Activity Log.

Contoh:

POST

PATCH

DELETE

APPROVE

REJECT

UPLOAD

DOWNLOAD

Referensi:

database/logs.md

---

# Notification

Endpoint tertentu menghasilkan Notifikasi.

Contoh:

Lead Deal

↓

Notifikasi Finance

Invoice Dibuat

↓

Notifikasi Klien

Pembayaran Diverifikasi

↓

Notifikasi Admin

Referensi:

08-notification.md

---

# Versioning

Gunakan Versioning.

Contoh:

/api/v1

Apabila terdapat perubahan besar:

/api/v2

---

# Prinsip API

- RESTful
- Stateless
- Resource Based
- Versioned
- Secure
- Consistent Response
- Role Based Authorization
- Activity Logged

API tidak mengembalikan response penolakan kewenangan kepada pengguna aplikasi. Resource dan action di luar scope disembunyikan dari client; request langsung diperlakukan sebagai resource tidak tersedia dengan response 404. Endpoint tetap wajib melakukan authorization di backend.
