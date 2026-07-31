# Database - Project Assignments

## Tujuan

Entitas **Project Assignments** menyimpan staf internal yang terlibat pada satu Project.

Client dan akun Klien tidak disimpan sebagai Assignment karena sudah diperoleh melalui `projects.client_id`.

---

# Struktur Data

Minimal terdiri dari:

- `id` — UUID
- `project_id`
- `user_id`
- `assignment_role`
- `assigned_by`
- `assigned_at`
- `ended_at` (nullable)
- `created_at`
- `updated_at`

---

# Business Rule

- Hanya User internal yang dapat menjadi Assignment.
- Satu Project memiliki maksimal satu Assignment aktif per role, kecuali Auditor apabila kebijakan mengizinkan lebih dari satu.
- Reassignment menutup record lama melalui `ended_at` dan membuat record baru.
- Assignment tidak dihapus agar histori PIC tetap tersedia.
- PIC Client diturunkan dari Client dan User Klien, bukan diduplikasi ke tabel ini.

---

# Index dan Constraint

- index `project_id`
- index `user_id`
- index `assignment_role`
- composite index (`project_id`, `assignment_role`, `ended_at`)
