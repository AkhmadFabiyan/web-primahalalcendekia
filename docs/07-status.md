# 07. Status

## Tujuan

Dokumen ini mendefinisikan seluruh Status yang digunakan pada PHC System.

Seluruh Workflow, Database, API, UI, dan Business Rule wajib menggunakan status yang terdapat pada dokumen ini.

Dilarang membuat Status baru tanpa memperbarui dokumen ini.

---

# Referensi

Dokumen terkait:

- 00-overview.md
- 00-glossary.md
- 01-business.md
- 02-workflow.md
- 03-role-permission.md
- 04-database.md
- 05-api.md
- 06-routing.md
- 08-notification.md

---

# Prinsip Status

Status bisnis utama PHC System terdiri dari tiga jenis:

1. Status Lead
2. Status Project
3. Status Modul

Status Project merupakan status utama.

Status Modul hanya digunakan oleh masing-masing divisi.

Resource pendukung memiliki status lifecycle tersendiri untuk Invoice, Payment, Notification, User, record Dokumen, dan Tugas. Seluruhnya tetap didefinisikan pada dokumen ini.

---

# 1. Status Lead

Digunakan sebelum Project dibuat.

| Kode Enum | Label UI | Keterangan |
|----------|----------|----------------|
| `DRAFT` | Draft | Lead baru dibuat |
| `DEAL` | Deal | Lead berhasil menjadi Klien |
| `CANCELLED` | Batal | Lead dibatalkan |

Rule

Draft

↓

Deal

atau

↓

Batal

Lead yang sudah Deal tidak dapat kembali menjadi Draft.

---

# 2. Status Project

Merupakan status utama Project.

Status ini ditampilkan pada Dashboard.

## Daftar Status

| Kode Enum | Label UI | Keterangan |
|----------|----------|----------------|
| `WAITING_ACTIVATION` | Menunggu Aktivasi | Menunggu pembayaran pertama |
| `ACTIVE` | Aktif | Project dapat dikerjakan |
| `OPERATIONAL` | Operasional | Workflow sedang berjalan |
| `WAITING_GOVERNMENT_INVOICE` | Menunggu Invoice Negara | Menunggu proses BPJPH |
| `WAITING_CERTIFICATE` | Menunggu Sertifikat | Menunggu upload sertifikat |
| `CERTIFICATE_ISSUED` | Sertifikat Terbit | Sertifikat telah diunggah |
| `WAITING_SETTLEMENT` | Menunggu Pelunasan | Menunggu pembayaran akhir |
| `COMPLETED` | Selesai | Project selesai |
| `CANCELLED` | Dibatalkan | Project dihentikan |

---

# Perpindahan Status Project

Menunggu Aktivasi

↓

Aktif

↓

Operasional

↓

Menunggu Invoice Negara

↓

Menunggu Sertifikat

↓

Sertifikat Terbit

↓

Menunggu Pelunasan

↓

Selesai

atau

↓

Dibatalkan

---

# 3. Status Modul

Setiap divisi memiliki status sendiri.

Status Modul tidak mengubah Status Project secara langsung.

---

## Modul Dokumen

| Kode Enum | Label UI | Keterangan |
|----------|----------|----------------|
| `NOT_STARTED` | Belum Mulai | Belum ada dokumen |
| `IN_PROGRESS` | Proses | Sedang upload |
| `COMPLETE` | Lengkap | Semua dokumen wajib tersedia |
| `REVISION` | Revisi | Ada dokumen yang harus diperbaiki |

---

## Status Entry dan Progress Entry

Status dipilih manual oleh Entry atau SPV Entry yang berwenang. Progress tidak diinput dan selalu diturunkan dari status.

| Kode Enum | Label UI | Progress |
|----------|----------|:--------:|
| `ENTRY_NOT_STARTED` | Belum Dikerjakan | 0% |
| `WAITING_CLIENT_DOCUMENTS` | Menunggu Dokumen Klien | 10% |
| `DOCUMENTS_INCOMPLETE` | Dokumen Belum Lengkap | 20% |
| `CREATING_SIHALAL_ACCOUNT` | Pembuatan Akun SiHalal | 35% |
| `PREPARING_SJPH_MANUAL` | Penyusunan Manual SJPH | 50% |
| `INPUTTING_MATERIALS_PRODUCTS` | Input Bahan dan Produk | 65% |
| `SUBMITTED_TO_LPH` | Pengajuan ke LPH | 80% |
| `DOCUMENT_REVISION` | Revisi Dokumen | 90% |
| `ENTRY_COMPLETED` | Entry Selesai | 100% |

---

## Status Pendamping dan Progress Pendamping

Status dipilih manual oleh Pendamping Auditor yang ditugaskan. Progress tidak diinput dan selalu diturunkan dari status.

| Kode Enum | Label UI | Progress |
|----------|----------|:--------:|
| `COMPANION_NOT_PROCESSED` | Belum Diproses | 0% |
| `WAITING_AUDIT_SCHEDULE` | Menunggu Jadwal Audit | 15% |
| `AUDIT_PREPARATION` | Persiapan Audit | 30% |
| `FIELD_EVIDENCE_INCOMPLETE` | Bukti Lapangan Belum Lengkap | 40% |
| `AUDIT_SCHEDULED` | Jadwal Audit Ditentukan | 55% |
| `AUDIT_IN_PROGRESS` | Audit Berlangsung | 75% |
| `AUDIT_COMPLETED` | Audit Selesai | 90% |
| `WAITING_CLIENT_CORRECTION` | Menunggu Perbaikan Klien | 95% |
| `ASSISTANCE_COMPLETED` | Pendampingan Selesai | 100% |

---

## Status Auditor dan Progress Auditor

Status dipilih manual oleh Auditor yang ditugaskan. Empat milestone pasca-audit dapat dipilih oleh Admin Perusahaan. Progress tidak diinput dan selalu diturunkan dari status.

| Kode Enum | Label UI | Progress |
|----------|----------|:--------:|
| `AUDITOR_NOT_PROCESSED` | Belum Diproses | 0% |
| `DOCUMENT_REVIEW` | Pemeriksaan Dokumen | 10% |
| `WAITING_FIELD_AUDIT` | Menunggu Audit Lapangan | 20% |
| `FIELD_AUDIT_COMPLETED` | Audit Lapangan Selesai | 35% |
| `NONCONFORMITY_FOUND` | Ada Ketidaksesuaian | 45% |
| `WAITING_CORRECTIVE_EVIDENCE` | Menunggu Bukti Perbaikan | 55% |
| `CORRECTION_ACCEPTED` | Perbaikan Diterima | 65% |
| `AUDIT_REPORT_COMPLETED` | Laporan Audit Selesai | 75% |
| `WAITING_FATWA_SESSION` | Menunggu Sidang Fatwa | 82% |
| `FATWA_SESSION_COMPLETED` | Sidang Fatwa Selesai | 90% |
| `WAITING_BPJPH_ISSUANCE` | Menunggu Penerbitan BPJPH | 95% |
| `HALAL_CERTIFICATE_ISSUED` | Sertifikat Halal Terbit | 100% |

---

## Modul Sertifikat

| Kode Enum | Label UI | Keterangan |
|----------|----------|----------------|
| `NOT_STARTED` | Belum Diproses | Belum upload |
| `WAITING_UPLOAD` | Menunggu Upload | Menunggu Admin Perusahaan |
| `ISSUED` | Terbit | Sertifikat tersedia |

---

## Modul Pembayaran

Status dihitung berdasarkan seluruh Invoice.

| Kode Enum | Label UI | Keterangan |
|----------|----------|----------------|
| `NO_INVOICE` | Belum Ada Invoice | Belum membuat Invoice |
| `UNPAID` | Belum Bayar | Invoice belum dibayar |
| `PARTIAL` | Sebagian | Sebagian Invoice telah dibayar |
| `PAID` | Lunas | Seluruh Invoice telah lunas |

---

# Status Invoice

| Kode Enum | Label UI | Keterangan |
|----------|----------|----------------|
| `DRAFT` | Draft | Belum dikirim |
| `PUBLISHED` | Diterbitkan | Sudah dikirim |
| `PARTIAL` | Sebagian | Dibayar sebagian |
| `PAID` | Lunas | Sudah lunas |
| `CANCELLED` | Dibatalkan | Tidak berlaku |

---

# Status Payment

| Kode Enum | Label UI | Keterangan |
|----------|----------|----------------|
| `PENDING` | Menunggu Verifikasi | Bukti pembayaran diterima |
| `VERIFIED` | Terverifikasi | Pembayaran valid |
| `REJECTED` | Ditolak | Pembayaran ditolak |

---

# Status Notification

| Kode Enum | Label UI | Keterangan |
|----------|----------|----------------|
| `UNREAD` | Belum Dibaca | Belum dibuka |
| `READ` | Sudah Dibaca | Sudah dibuka |

Status Notification merupakan nilai turunan:

- `UNREAD` jika `read_at` kosong
- `READ` jika `read_at` tersedia

Database tidak menyimpan kolom status atau `is_read` terpisah.

---

# Status User

| Kode Enum | Label UI | Keterangan |
|----------|----------|----------------|
| `ACTIVE` | Aktif | Dapat Login |
| `INACTIVE` | Nonaktif | Tidak dapat Login |

---

# Status Record Dokumen

Status ini berlaku untuk satu record/file dokumen, bukan Status Modul Dokumen.

| Kode Enum | Label UI | Keterangan |
|----------|----------|----------------|
| `UPLOADED` | Diunggah | Versi aktif telah diunggah |
| `REPLACED` | Diganti | Versi lama telah digantikan |
| `ARCHIVED` | Diarsipkan | Dokumen tidak lagi aktif |

---

# Status Tugas

| Kode Enum | Label UI | Keterangan |
|----------|----------|----------------|
| `TODO` | Belum Dikerjakan | Tugas siap dikerjakan |
| `IN_PROGRESS` | Sedang Dikerjakan | Pekerjaan sedang berlangsung |
| `WAITING_REVIEW` | Menunggu Review | Menunggu pemeriksaan role reviewer |
| `REVISION` | Revisi | Dikembalikan untuk diperbaiki |
| `COMPLETED` | Selesai | Tugas telah selesai |

Prioritas Tugas menggunakan `LOW`, `MEDIUM`, `HIGH`, dan `CRITICAL`.

Prioritas Notification menggunakan `LOW`, `MEDIUM`, dan `HIGH` sesuai `08-notification.md`.

---

# Trigger Perubahan Status

| Trigger | Status Baru |
|-----------|----------------|
| Lead Deal | Menunggu Aktivasi |
| Pembayaran Aktivasi Diverifikasi | Aktif untuk Client Langsung; Client Mitra menunggu kedua Invoice Aktivasi dalam billing group Lunas |
| Workflow Dimulai | Operasional |
| Workflow A & B Selesai | Menunggu Invoice Negara |
| Invoice Negara Diunggah | Menunggu Sertifikat |
| Sertifikat Diunggah dan masih ada Invoice belum lunas | Menunggu Pelunasan |
| Sertifikat Diunggah dan semua Invoice lunas | Selesai |
| Semua Invoice Lunas setelah Sertifikat Terbit | Selesai |

---

# Progress Project

Progress Project keseluruhan bersifat read-only dan dihitung server dari milestone wajib pada jalur Entry, Pendamping, Auditor, Sertifikat, dan pembayaran. Status jalur dipilih manual oleh role berwenang, tetapi persentase jalur selalu diturunkan dari mapping status resmi.

Rumus:

`progress_percent = jumlah langkah wajib selesai / jumlah seluruh langkah wajib × 100`

Aturan:

- langkah opsional tidak masuk denominator
- Workflow A dan Workflow B dihitung sebagai jalur paralel
- revisi mengembalikan langkah terkait ke status belum selesai
- progress 100% hanya ketika Status Project = Selesai
- nilai progress tidak boleh diedit manual; API hanya menerima kode status dan catatan

Rumus langkah wajib berlaku untuk progress Project keseluruhan, bukan untuk tiga angka progress jalur. Nilai keseluruhan tidak boleh dihitung sebagai rata-rata sederhana karena jalur dapat berjalan paralel. Perubahan status mundur wajib memiliki alasan; setiap transisi menyimpan aktor, waktu, status sebelum/sesudah, dan histori append-only. Status final tetap tunduk pada prasyarat workflow, Sertifikat, dan pembayaran.

Dashboard Klien menampilkan status dan persentase ketiga jalur, tahap saat ini, langkah selesai, dan status publik. Catatan internal, Assignment, serta alasan revisi internal tidak ditampilkan.

## Pengubah Status Jalur

| Jalur | Role yang Dapat Mengubah |
|---|---|
| Entry | Entry yang ditugaskan; SPV Entry untuk review dan koreksi |
| Pendamping | Pendamping Auditor yang ditugaskan |
| Auditor 0%–75% | Auditor yang ditugaskan |
| Auditor 82%–100% | Admin Perusahaan |
| Semua jalur | Super Admin sebagai override dengan alasan wajib |

Role lain hanya melihat kecuali permission eksplisit baru ditetapkan pada `03-role-permission.md`.

---

# Rule Workflow

Workflow A selesai

DAN

Workflow B selesai

↓

Status Project berubah menjadi

Menunggu Invoice Negara

---

# Rule Validasi

Project tidak boleh menjadi:

Sertifikat Terbit

apabila

Workflow A belum selesai.

Project tidak boleh menjadi:

Selesai

apabila

masih terdapat Invoice yang belum lunas.

Project juga tidak boleh menjadi Selesai apabila Sertifikat belum Terbit.

---

# Aturan Kode Enum dan Label

Seluruh Status wajib menggunakan Enum.

Tidak boleh menggunakan String bebas.

Database, API, query parameter, dan kode program menggunakan nilai pada kolom **Kode Enum**.

UI dan narasi bisnis menggunakan nilai pada kolom **Label UI**.

Contoh

✓

ACTIVE

COMPLETED

REJECTED

REVISION

×

aktif

Aktif

Sedang Aktif

done

selesai

---

# Referensi

Workflow

02-workflow.md

Database

04-database.md

API

05-api.md

Notification

08-notification.md
