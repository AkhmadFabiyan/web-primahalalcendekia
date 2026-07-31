# Database - Tasks

## Tujuan

Dokumen ini menjelaskan entitas **Tasks** pada PHC System.

Task merupakan antrean pekerjaan yang dibuat otomatis oleh event workflow. User tidak dapat membuat, menghapus, atau memproses Task secara massal.

---

# Referensi

- `02-workflow.md`
- `03-role-permission.md`
- `04-database.md`
- `07-status.md`
- `06-routing.md`
- `ui/tugas.md`

---

# Struktur Data

## Primary Key

`id`

UUID.

## Informasi Dasar

Minimal terdiri dari:

- `project_id`
- `assigned_to`
- `task_type`
- `title`
- `description`
- `priority`
- `status`
- `entered_at`
- `deadline`
- `started_at`
- `completed_at`
- `created_at`
- `updated_at`

`entered_at` merupakan Timestamp Masuk, yaitu waktu Task masuk ke antrean role atau User yang ditugaskan.

Task tidak menyimpan ID Klien secara duplikat. ID Klien diperoleh melalui relasi `tasks.project_id → projects.client_id → clients.business_id`.

---

# Status dan Prioritas

Status mengikuti `07-status.md`:

- `TODO`
- `IN_PROGRESS`
- `WAITING_REVIEW`
- `REVISION`
- `COMPLETED`

Prioritas:

- `LOW`
- `MEDIUM`
- `HIGH`
- `CRITICAL`

---

# Relasi

- Satu Project memiliki banyak Task.
- Satu Task ditugaskan kepada satu User aktif pada satu waktu.
- PIC internal diperoleh dari `project_assignments`, bukan disalin ke Task.
- PIC Client diperoleh dari `projects.client_id` dan akun Klien terkait.
- Histori pergantian PIC disimpan agar Timestamp Masuk setiap antrean dapat diaudit.

PIC yang ditampilkan minimal mencakup Klien dari relasi Client, serta Entry, Admin Perusahaan, Finance, Auditor, Pendamping Auditor, SPV Entry, Admin, dan role internal lain dari Assignment.

---

# Business Rule

- Task hanya dibuat oleh event workflow.
- Task tidak menyediakan Create manual.
- Task tidak menyediakan Delete.
- Task tidak menyediakan Bulk Action.
- Tugas selesai tidak dapat diedit.
- Reassignment wajib menyimpan actor, PIC sebelumnya, PIC baru, alasan, dan timestamp.
- Satu Client memiliki satu Project; UI menampilkan `clients.business_id`, bukan ID Project.
- Badge menu Tugas menghitung Task milik User yang statusnya bukan `COMPLETED`.
- Badge diperbarui setelah pembuatan, reassignment, perubahan status, atau penyelesaian Task.

---

# Index

Direkomendasikan:

- `project_id`
- `assigned_to`
- `status`
- `priority`
- `entered_at`
- `deadline`
- composite (`assigned_to`, `status`)

---

# Audit Trail

Catat aktivitas berikut:

- Task dibuat otomatis
- Task masuk antrean
- Task dimulai
- Task dipindahkan
- Task menunggu review
- Task direvisi
- Task diselesaikan

Task tidak menggunakan penghapusan permanen. Histori harus tetap tersedia untuk audit dan perhitungan SLA.

Histori PIC disimpan pada tabel `task_assignment_histories`.

Minimal terdiri dari:

- `id`
- `task_id`
- `from_user_id` (nullable)
- `to_user_id`
- `changed_by`
- `reason` (nullable)
- `entered_at`
- `ended_at` (nullable)
- `created_at`

---

# Migration Recommendation

```text
tasks

- id
- project_id
- assigned_to
- task_type
- title
- description
- priority
- status
- entered_at
- deadline
- started_at
- completed_at
- created_at
- updated_at
```

```text
task_assignment_histories

- id
- task_id
- from_user_id
- to_user_id
- changed_by
- reason
- entered_at
- ended_at
- created_at
```
