# Database - Leads

## Tujuan

Entitas **Leads** menyimpan calon Client sebelum Deal.

Lead merupakan satu-satunya resource operasional yang dapat dibuat manual.

---

# Struktur Data

Minimal terdiri dari:

- `id` — UUID
- `company_name`
- `business_sector`
- `address`
- `pic_name`
- `pic_phone`
- `pic_email`
- `client_type` — `DIRECT` atau `PARTNER`
- `partner_id` — nullable untuk Partner yang sudah terdaftar
- `partner_name` — nullable untuk Partner baru
- `partner_pic_name` — nullable
- `partner_phone` — nullable
- `partner_email` — nullable
- `service_type`
- `client_nominal`
- `partner_nominal` — nullable
- `payment_scheme`
- `installment_count`
- `marketing_id`
- `status`
- `created_at`
- `updated_at`

---

# Business Rule

- `partner_id` dan `partner_nominal` wajib kosong untuk `DIRECT`.
- Untuk `PARTNER`, pilih `partner_id` yang tersedia atau isi data Partner baru.
- Jika data Partner baru diisi, proses Deal melakukan pencarian duplikat lalu membuat Partner secara otomatis bila belum tersedia.
- `partner_nominal` wajib tersedia untuk `PARTNER`.
- `client_nominal` wajib lebih dari nol.
- `partner_nominal` wajib lebih dari nol untuk `PARTNER`.
- Lead Deal dikonversi tepat satu kali dalam satu transaksi database menjadi Client, Project, Assignment awal, dan draft Invoice.
- Lead tidak menggunakan Delete; status Batal menghentikan proses.

---

# Index dan Constraint

- index `status`
- index `marketing_id`
- index `client_type`
- index `partner_id`
- index `company_name`
