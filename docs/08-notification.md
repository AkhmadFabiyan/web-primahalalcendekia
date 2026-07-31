# 08. Notification

## Tujuan

Dokumen ini mendefinisikan sistem Notifikasi Internal (In-App Notification) pada PHC System.

Notifikasi digunakan untuk memberi tahu User bahwa terdapat aktivitas, perubahan status, atau tugas baru yang memerlukan perhatian.

Dokumen ini hanya membahas notifikasi di dalam website.

---

# Referensi

Dokumen terkait:

- 02-workflow.md
- 03-role-permission.md
- 04-database.md
- 07-status.md

Database:

- database/notifications.md

UI:

- ui/dashboard.md

---

# Tujuan Notifikasi

Notifikasi digunakan untuk:

- Memberitahu adanya tugas baru.
- Memberitahu perubahan status.
- Memberitahu adanya revisi.
- Memberitahu pembayaran.
- Memberitahu proses selesai.

Notifikasi bukan media komunikasi.

---

# Jenis Notifikasi

PHC System memiliki satu jenis notifikasi.

## In-App Notification

Notifikasi muncul di dalam website.

Lokasi:

- Icon Bell pada Header
- Notification Dropdown
- Halaman Semua Notifikasi

---

# Struktur Notifikasi

Setiap notifikasi memiliki:

- Judul
- Pesan
- Jenis
- Project
- Tujuan User
- Status Dibaca
- Waktu

Contoh

Judul

```
Invoice Baru
```

Pesan

```
Invoice Aktivasi untuk CLIENT-2026-0008 telah diterbitkan.
```

---

# Status Notifikasi

| Kode Enum | Label UI | Keterangan |
|---------|------------|------------|
| `UNREAD` | Belum Dibaca | Belum dibuka |
| `READ` | Sudah Dibaca | Sudah dibuka |

Referensi:

07-status.md

Arsip bukan Status Notification. Notifikasi yang diarsipkan tetap memiliki status baca dan ditandai terpisah melalui `archived_at`.

---

# Trigger Notifikasi

Notifikasi dibuat berdasarkan Event Sistem.

User tidak dapat membuat notifikasi secara manual.

---

# Daftar Event

| Kode Event | Nama Event |
|---|---|
| `LEAD_DEAL` | Lead Deal |
| `INVOICE_CREATED` | Invoice Dibuat |
| `PAYMENT_VERIFIED` | Pembayaran Diverifikasi |
| `DOCUMENTS_COMPLETED` | Dokumen Lengkap |
| `ENTRY_COMPLETED` | Entry Selesai |
| `ENTRY_REVISION_REQUESTED` | Entry Direvisi |
| `ENTRY_APPROVED` | Entry Disetujui |
| `AUDIT_SCHEDULED` | Audit Dijadwalkan |
| `AUDIT_COMPLETED` | Audit Selesai |
| `AUDIT_REVISION_REQUESTED` | Audit Direvisi |
| `AUDIT_APPROVED` | Audit Disetujui |
| `WORKFLOW_COMPLETED` | Workflow Selesai |
| `CERTIFICATE_UPLOADED` | Sertifikat Diunggah |
| `PAYMENT_COMPLETED` | Pembayaran Lunas |

Kode Event digunakan oleh database, API, queue, dan kode program. Nama Event digunakan pada dokumentasi bisnis dan UI.

## Lead Deal

Trigger

Marketing mengubah Lead menjadi Deal.

Penerima

Finance

Pesan

```
Lead baru telah menjadi Project dan siap dibuatkan Invoice Aktivasi.
```

---

## Invoice Dibuat

Trigger

Finance menerbitkan Invoice.

Penerima

Marketing

Admin

Pesan

```
Invoice Aktivasi telah diterbitkan.
```

---

## Pembayaran Diverifikasi

Trigger

Finance memverifikasi pembayaran.

Penerima

Admin

Pesan

```
Project telah aktif dan siap diproses.
```

---

## Dokumen Lengkap

Trigger

Admin menyelesaikan upload dokumen wajib.

Penerima

Entry

Pesan

```
Dokumen Project telah lengkap dan siap di-entry ke SIHALAL.
```

---

## Entry Selesai

Trigger

Entry menekan tombol Selesai.

Penerima

SPV Entry

Pesan

```
Project menunggu Review Entry.
```

---

## Entry Direvisi

Trigger

SPV memilih Revisi.

Penerima

Entry

Pesan

```
Entry memerlukan revisi.
```

---

## Entry Disetujui

Trigger

SPV memilih Approve.

Penerima

Pendamping Auditor

Pesan

```
Workflow Entry telah selesai.
```

---

## Audit Dijadwalkan

Trigger

Pendamping membuat jadwal audit.

Penerima

Auditor

Pesan

```
Audit telah dijadwalkan.
```

---

## Audit Selesai

Trigger

Pendamping mengirim hasil audit.

Penerima

Auditor

Pesan

```
Hasil audit menunggu review.
```

---

## Audit Direvisi

Trigger

Auditor memilih Revisi.

Penerima

Pendamping Auditor

Pesan

```
Audit memerlukan revisi.
```

---

## Audit Disetujui

Trigger

Auditor memilih Approve.

Penerima

Admin Perusahaan

Pesan

```
Workflow Audit telah selesai.
```

---

## Workflow Selesai

Trigger

Workflow A dan Workflow B selesai.

Penerima

Admin Perusahaan

Pesan

```
Project siap memasuki proses BPJPH.
```

---

## Sertifikat Diunggah

Trigger

Admin Perusahaan mengunggah sertifikat.

Penerima

Finance

Marketing

Pesan

```
Sertifikat Halal telah tersedia.
```

---

## Pembayaran Lunas

Trigger

Seluruh Invoice telah lunas.

Penerima

Marketing

Manager Operasional

Direktur

Pesan

```
Project telah selesai.
```

---

# Prioritas

| Kode Enum | Label UI | Keterangan |
|---------|------------|------------|
| `HIGH` | Tinggi | Memerlukan tindakan segera |
| `MEDIUM` | Sedang | Memerlukan tindakan |
| `LOW` | Rendah | Informasi |

---

# Tampilan

Notification Bell

↓

Dropdown

↓

Halaman Semua Notifikasi

Notifikasi terbaru berada di urutan paling atas.

---

# Badge

Icon Bell menampilkan jumlah notifikasi yang belum dibaca.

Contoh

```
🔔 7
```

Badge otomatis berkurang ketika User membaca notifikasi.

Badge pada navigation menu **Tugas** merupakan indikator terpisah. Nilainya berasal dari jumlah Task milik User yang belum berstatus Selesai, bukan dari jumlah Notification yang belum dibaca.

---

# Mark as Read

User dapat:

- Membaca satu notifikasi.
- Menandai semua sebagai sudah dibaca.

---

# Redirect

Setiap notifikasi memiliki tujuan.

Contoh

Invoice Baru

↓

Klik

↓

Membuka halaman Invoice.

Entry Direvisi

↓

Klik

↓

Membuka Project terkait.

Audit Direvisi

↓

Klik

↓

Membuka Project terkait.

Untuk role Klien, seluruh redirect tetap berada di `/dashboard` dan membuka section terkait. Notifikasi tidak boleh mengarahkan Klien ke `/clients`, `/projects`, `/payments`, `/notifications`, atau dynamic route operasional.

---

# Masa Simpan

Notifikasi tidak dihapus otomatis.

Notifikasi tetap disimpan sebagai histori.

Penghapusan hanya dapat dilakukan oleh sistem apabila terdapat kebijakan retensi data.

---

# Activity Log

Pembuatan notifikasi tidak perlu membuat Activity Log.

Namun Event yang memicu notifikasi wajib tercatat pada Activity Log.

Referensi:

database/logs.md

---

# Business Rules

- Notifikasi hanya dikirim kepada Role yang berkepentingan.
- Satu Event dapat menghasilkan lebih dari satu Notifikasi.
- Notifikasi tidak boleh dikirim dua kali untuk Event yang sama.
- Pasangan Invoice Client dan Invoice Partner dalam satu billing group menghasilkan satu event transaksi; notifikasi dapat menjelaskan kedua dokumen tanpa menggandakan hitungan event.
- Membaca notifikasi tidak mengubah Workflow.
- Menghapus notifikasi tidak menghapus Event.

---

# Contoh Alur

Marketing Deal

↓

Finance menerima Notifikasi

↓

Finance memeriksa dan menerbitkan Invoice

↓

Admin menerima Notifikasi

↓

Admin upload Dokumen

↓

Entry menerima Notifikasi

↓

SPV menerima Notifikasi

↓

Pendamping menerima Notifikasi

↓

Auditor menerima Notifikasi

↓

Admin Perusahaan menerima Notifikasi

↓

Finance menerima Notifikasi

↓

Project Selesai
