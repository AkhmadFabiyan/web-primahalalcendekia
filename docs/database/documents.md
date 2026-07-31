# Database - Documents

## Tujuan

Dokumen ini menjelaskan entitas **Documents** pada PHC System.

Documents digunakan untuk menyimpan metadata bisnis dokumen persyaratan dan hasil audit Project.

Bukti pembayaran terhubung langsung ke Payment melalui Media Library. File Sertifikat terhubung langsung ke Certificate. Keduanya tidak diduplikasi sebagai Document.

Dokumen ini hanya menjelaskan struktur dan aturan data Documents.

Arsitektur database dijelaskan pada:

- 04-database.md

---

# Referensi

Dokumen terkait

- 04-database.md
- 07-status.md

Workflow

- workflow/admin.md
- workflow/entry.md
- workflow/audit.md
- workflow/sertifikat.md

UI

- ui/klien.md

---

# Tujuan Entitas

Documents digunakan untuk:

- menyimpan file Project
- mengelompokkan jenis dokumen
- menyimpan versi dokumen
- menyimpan histori upload
- menjadi referensi workflow

Documents tidak menyimpan status Project.

Documents tidak menyimpan status Workflow.

---

# Tanggung Jawab

Entitas Documents bertanggung jawab menyimpan:

- jenis dokumen
- versi dokumen
- informasi uploader
- visibility dan lifecycle bisnis

File fisik disimpan pada media penyimpanan melalui Spatie Media Library.

Metadata fisik file disimpan oleh tabel `media`. Model Document hanya menyimpan metadata bisnis dan memiliki satu media aktif pada collection `document-file`.

---

# Struktur Data

## Primary Key

id

UUID

---

## Business Identifier

Tidak diperlukan.

Documents menggunakan UUID.

---

## Informasi Dasar

Minimal terdiri dari:

- project_id
- document_type_id
- version
- is_client_visible

---

## Informasi Upload

Minimal terdiri dari:

- uploaded_by
- uploaded_at

---

## Status

Status mengikuti:

07-status.md

Nilai enum:

- UPLOADED
- REPLACED
- ARCHIVED

Status ini adalah lifecycle satu record dokumen, bukan Status Modul Dokumen.

---

# Jenis Dokumen

Jenis dokumen tidak disimpan sebagai enum.

Gunakan tabel referensi:

document_types

Contoh:

- NIB
- NPWP
- KTP PIC
- Daftar Produk
- Daftar Bahan
- Manual SJPH
- Surat Pernyataan
- Foto Fasilitas
- Berita Acara Audit
- Dokumen Lainnya

---

# Relasi

Document

N

↓

1

Project

---

Document

N

↓

1

Document Type

---

Document

N

↓

1

User

(Uploader)

---

Document

1

↓

N

Activity Log

---

# Cardinality

Project

1

↓

N

Document

---

Document Type

1

↓

N

Document

---

User

1

↓

N

Document

---

# Versioning

Setiap dokumen dapat memiliki lebih dari satu versi.

Contoh

NIB

↓

Versi 1

↓

Versi 2

↓

Versi 3

Versi terbaru menjadi versi aktif.

Versi sebelumnya tetap disimpan.

---

# Soft Delete

Documents menggunakan Soft Delete.

Dokumen yang pernah digunakan pada workflow tidak boleh dihapus permanen.

---

# Audit Trail

Catat aktivitas berikut.

- upload
- replace
- download
- archive
- restore
- delete

---

# Business Rule

Project wajib tersedia.

Jenis dokumen wajib tersedia.

Uploader wajib tersedia.

Versi dimulai dari 1.

Mengganti file tidak menghapus versi sebelumnya.

Dokumen wajib mengikuti kebutuhan workflow.

Dokumen hanya dapat ditampilkan pada Dashboard Klien apabila `is_client_visible = true` dan `project_id` berelasi dengan `users.client_id`.

---

# Validasi

Project harus aktif.

Ukuran file mengikuti konfigurasi sistem.

Tipe file mengikuti whitelist.

Nama file tidak boleh kosong.

`is_client_visible` wajib berupa boolean dan default `false`.

---

# Index

Direkomendasikan

- project_id
- document_type_id
- uploaded_by
- is_client_visible
- uploaded_at
- deleted_at

---

# Integritas Data

Menghapus Document tidak menghapus:

- Activity Log

Versi lama tetap tersedia sebagai histori.

---

# Migration Recommendation

Contoh struktur minimal.

documents

- id
- project_id
- document_type_id
- version
- is_client_visible
- uploaded_by
- uploaded_at
- status
- created_at
- updated_at
- deleted_at

Media collection:

- `document-file`

---

# Tabel Referensi

document_types

- id
- code
- name
- category
- is_required
- sort_order
- is_active

---

# Future Enhancement

Mendukung:

- Version Control
- Preview PDF
- Preview Gambar
- OCR
- Digital Signature
- Virus Scan
- Expired Document Monitoring
- Watermark
- File Encryption

---

# Hubungan Dokumen

Workflow

- workflow/admin.md
- workflow/entry.md
- workflow/audit.md
- workflow/sertifikat.md

Status

- 07-status.md

Database

- 04-database.md
