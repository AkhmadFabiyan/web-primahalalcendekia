# Database - Activity Log

## Tujuan

Activity Log mencatat seluruh aktivitas bisnis penting sebagai audit trail append-only.

Implementasi menggunakan `spatie/laravel-activitylog` dan tabel standar `activity_log`. Sistem tidak membuat tabel `logs` kedua dan tidak menjalankan package audit lain untuk event yang sama.

---

# Referensi

- `03-role-permission.md`
- `04-database.md`
- `05-api.md`
- `12-development-rules.md`
- `workflow/*`

---

# Struktur Data

Gunakan struktur package dengan penambahan kolom query bisnis yang diperlukan:

- `id`
- `log_name`
- `description`
- `event`
- `subject_type`
- `subject_id`
- `causer_type`
- `causer_id`
- `project_id` (nullable)
- `is_client_visible` (default `false`)
- `properties` (JSON)
- `batch_uuid` (nullable)
- `created_at`
- `updated_at`

Perubahan nilai disimpan satu kali di dalam `properties` menggunakan key `old` dan `attributes`. Tidak ada kolom `old_value` atau `new_value` terpisah.

---

# Business Rule

- Log dibuat otomatis oleh service atau event domain.
- Log tidak dapat diedit atau dihapus.
- Satu event bisnis menghasilkan satu Activity Log kanonik.
- Login, perubahan status, assignment, approval, revisi, upload, penerbitan Invoice, verifikasi Payment, dan penerbitan Sertifikat wajib dicatat.
- Aksi yang memproses pasangan Invoice Mitra dapat menggunakan `batch_uuid` yang sama tetapi tetap mencatat setiap subject Invoice secara jelas.
- Kredensial, password, token, isi file, dan data sensitif tidak boleh masuk ke `properties`.
- Timeline Klien hanya membaca log dengan `is_client_visible = true` dari Project miliknya.

---

# Pemisahan Tanggung Jawab

- Activity Log: histori bisnis dan perubahan data.
- Application Log: error, warning, debugging, serta observability teknis.
- Notification: pesan kepada User.
- Workflow History: histori transisi status langkah.

Keempatnya tidak saling menggantikan dan tidak boleh menyimpan payload yang sama tanpa kebutuhan eksplisit.

---

# Index

- `log_name`
- `event`
- (`subject_type`, `subject_id`)
- (`causer_type`, `causer_id`)
- `project_id`
- `is_client_visible`
- `created_at`
- `batch_uuid`
