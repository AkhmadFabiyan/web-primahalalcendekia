# Database - Certificates

## Tujuan

Entitas **Certificates** menyimpan metadata Sertifikat Halal milik Project.

Satu Project memiliki maksimal satu Sertifikat aktif.

---

# Struktur Data

Minimal terdiri dari:

- `id` — UUID
- `project_id`
- `certificate_number`
- `issued_at`
- `valid_until` (nullable)
- `uploaded_by`
- `created_at`
- `updated_at`

File Sertifikat disimpan melalui Spatie Media Library pada collection `certificate`. Tabel Certificates tidak menyimpan path, MIME type, atau ukuran file.

---

# Business Rule

- unique `project_id`
- unique `certificate_number`
- Sertifikat hanya dapat diterbitkan setelah syarat workflow dan Invoice Negara terpenuhi.
- File aktif wajib tersedia sebelum Status Modul Sertifikat menjadi Terbit.
- Metadata dan file tidak disalin ke Projects atau Documents.
