# Database - Workflows

## Tujuan

Dokumen ini mendefinisikan penyimpanan keadaan workflow, status progress manual, dan histori transisi Project.

---

# Workflow Steps

`workflow_steps` menyimpan keadaan aktif setiap langkah atau jalur. Satu record merupakan sumber kebenaran keadaan saat ini.

Minimal terdiri dari:

- `id` — UUID
- `project_id`
- `step_code`
- `workflow_lane` — `A`, `B`, atau `FINAL`
- `track_code` — nullable; `ENTRY`, `COMPANION`, atau `AUDITOR`
- `status`
- `is_required`
- `started_at`
- `completed_at`
- `last_changed_by`
- `created_at`
- `updated_at`

Constraint:

- unique (`project_id`, `step_code`)
- unique (`project_id`, `track_code`) untuk tiga record tracker utama
- `track_code` nullable untuk langkah biasa
- `status` harus valid terhadap `track_code` dan mapping `07-status.md`

Tiga record tracker utama:

| `track_code` | `step_code` | Nilai awal |
|---|---|---|
| `ENTRY` | `ENTRY_PROGRESS` | `ENTRY_NOT_STARTED` |
| `COMPANION` | `COMPANION_PROGRESS` | `COMPANION_NOT_PROCESSED` |
| `AUDITOR` | `AUDITOR_PROGRESS` | `AUDITOR_NOT_PROCESSED` |

Database tidak perlu menyimpan `progress_percent` pada tracker. Persentase berasal dari mapping enum di domain/service. Jika angka disimpan untuk cache laporan, nilai itu bukan sumber kebenaran, harus dapat dibangun ulang, dan diperbarui atomik bersama transisi.

---

# Workflow Histories

`workflow_histories` merupakan histori append-only.

Minimal terdiri dari:

- `id` — UUID
- `project_id`
- `workflow_step_id`
- `from_status`
- `to_status`
- `actor_id`
- `reason` (nullable)
- `metadata` (JSON, opsional)
- `created_at`

Metadata status progress minimal dapat memuat:

- `source` — `CLIENT_DETAIL`, `DASHBOARD`, `TASK`, atau `SYSTEM`
- `from_progress`
- `to_progress`
- `assignment_id`
- `override` — boolean

Histori tidak menyimpan nilai sensitif yang tidak diperlukan. Histori tidak dapat diedit atau dihapus dari UI.

---

# Transaksi Perubahan Status

Perubahan status harus dilakukan dalam satu database transaction:

1. lock record `workflow_steps` terkait
2. validasi role, permission, Assignment, dan prasyarat bisnis
3. validasi enum sesuai jalur
4. minta alasan apabila progress mundur atau Super Admin melakukan override
5. ubah status aktif dan `last_changed_by`
6. append `workflow_histories`
7. append Activity Log
8. perbarui cache/agregat Dashboard
9. dispatch notification setelah commit jika diperlukan

Persentase dari request diabaikan dengan error validasi; server selalu menghitungnya dari status.

---

# Business Rule

- `workflow_steps` adalah sumber status langkah dan tracker saat ini.
- `workflow_histories` adalah sumber histori transisi.
- `projects.status` merupakan agregat bisnis tingkat Project, bukan salinan status tracker.
- status tracker dipilih manual oleh pemilik jalur sesuai `03-role-permission.md`
- progress tracker diturunkan otomatis dari `07-status.md`
- Progress Project keseluruhan dihitung dari milestone wajib dan dapat disimpan hanya sebagai cache
- `last_progress_updated_at` untuk Dashboard berasal dari histori tracker terbaru
- satu Client/Project hanya memiliki satu tracker aktif per jalur
- perubahan Status Auditor ke `HALAL_CERTIFICATE_ISSUED` harus berelasi dengan Sertifikat yang valid
- setiap transisi menghasilkan Activity Log bisnis

---

# Query Dashboard

Query Dashboard harus:

- menggunakan Project unik sebagai basis hitung
- join ke satu tracker per `track_code`
- menggunakan `MAX(workflow_histories.created_at)` dari tiga tracker untuk pembaruan progress terakhir
- menghindari duplikasi akibat banyak Assignment, Invoice pasangan Mitra, atau banyak dokumen
- menghitung chart dan KPI dari dataset dasar yang sama

Index minimum:

- (`project_id`, `track_code`)
- (`project_id`, `step_code`)
- (`workflow_step_id`, `created_at`)
- (`project_id`, `created_at`)
