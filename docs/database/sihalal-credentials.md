# Database - SIHALAL Credentials

## Tujuan

Entitas **SIHALAL Credentials** menyimpan kredensial eksternal milik Client secara terpisah dan terenkripsi.

Kredensial SIHALAL bukan akun login PHC System.

---

# Struktur Data

Minimal terdiri dari:

- `id` — UUID
- `project_id`
- `email_encrypted`
- `password_encrypted`
- `created_by`
- `updated_by`
- `last_used_at` (nullable)
- `created_at`
- `updated_at`

---

# Business Rule

- unique `project_id`
- Nilai email dan password wajib dienkripsi menggunakan application encryption.
- Kredensial tidak boleh ditulis ke Activity Log, audit properties, Notification, exception, atau response umum.
- Hanya Role yang berwenang dapat mendekripsi.
- Akses baca wajib dicatat tanpa merekam nilai rahasia.
