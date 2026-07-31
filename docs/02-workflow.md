# 02. Workflow

## Tujuan

Dokumen ini menjelaskan alur kerja (workflow) PHC System dari awal hingga akhir.

Dokumen ini hanya menjelaskan hubungan antar proses.

Detail setiap proses dijelaskan pada folder `/workflow`.

---

# Referensi

Dokumen terkait:

- 00-overview.md
- 00-glossary.md
- 01-business.md
- 03-role-permission.md
- 07-status.md

Workflow Detail:

- workflow/marketing.md
- workflow/finance.md
- workflow/admin.md
- workflow/entry.md
- workflow/spv-entry.md
- workflow/audit.md
- workflow/sertifikat.md
- workflow/pembayaran.md

---

# Gambaran Workflow

Seluruh aktivitas sistem selalu berpusat pada satu Project.

Setiap Project akan melewati beberapa fase hingga selesai.

Lead

↓

Deal

↓

ID Klien dan Project tunggal dibuat otomatis

↓

Invoice Aktivasi

↓

Verifikasi Pembayaran

↓

Project Aktif

↓

Workflow Paralel

↓

Invoice Negara

↓

Upload Sertifikat

↓

Pelunasan

↓

Project Selesai

---

# Tracker Progress Operasional

Selama Workflow paralel dan tahap pasca-audit, perkembangan satu Project dipantau melalui tiga tracker:

- Status Entry dan Progress Entry
- Status Pendamping dan Progress Pendamping
- Status Auditor dan Progress Auditor

Status dipilih manual melalui dropdown oleh role yang berwenang. Persentase tidak diinput dan selalu mengikuti mapping `07-status.md`. Ketiga tracker ditampilkan pada Dashboard Progres Operasional untuk seluruh staf internal selain Super Admin serta secara read-only pada Dashboard Klien.

Tracker tidak mengganti Status Project. Status Project tetap merupakan agregat bisnis, sedangkan tracker menunjukkan posisi pekerjaan masing-masing jalur.

---

# Workflow Level 1

Tahapan utama sistem terdiri dari delapan proses.

## 1. Lead

Role:

Marketing

Output:

Lead

Referensi:

workflow/marketing.md

---

## 2. Aktivasi

Role:

Finance

Output:

Project Aktif

Referensi:

workflow/finance.md

---

## 3. Workflow A

Role:

Admin

↓

Entry

↓

SPV Entry

Output:

Workflow A Selesai

Referensi:

workflow/admin.md

workflow/entry.md

workflow/spv-entry.md

---

## 4. Workflow B

Role:

Pendamping Auditor

↓

Auditor

Output:

Workflow B Selesai

Referensi:

workflow/audit.md

---

## 5. Sinkronisasi Workflow

Tahap selanjutnya hanya dapat dimulai apabila:

Workflow A = Selesai

DAN

Workflow B = Selesai

Apabila salah satu workflow masih berjalan, Project tetap berada pada status "Operasional".

---

## 6. Invoice Negara

Role:

Admin Perusahaan

Output:

Invoice Negara

Referensi:

workflow/sertifikat.md

---

## 7. Upload Sertifikat

Role:

Admin Perusahaan

Output:

Nomor Sertifikat

File Sertifikat

Referensi:

workflow/sertifikat.md

---

## 8. Pelunasan

Role:

Finance

Output:

Project Selesai

Referensi:

workflow/pembayaran.md

---

# Diagram Workflow

Lead

↓

Deal

↓

Invoice Aktivasi

↓

Pembayaran Diverifikasi

↓

━━━━━━━━━━━━━━━━━━━

Project Aktif

━━━━━━━━━━━━━━━━━━━

↓

┌─────────────────────┐

│                     │

▼                     ▼

Workflow A        Workflow B

│                     │

▼                     ▼

Admin              Pendamping

↓

Entry              Auditor

↓

SPV

│                     │

└──────────┬──────────┘

↓

Invoice Negara

↓

Upload Sertifikat

↓

Pelunasan

↓

Project Selesai

---

# Workflow Paralel

Workflow A dan Workflow B berjalan secara bersamaan.

Sistem tidak boleh mengizinkan proses berikutnya berjalan apabila salah satu workflow belum selesai.

Rule:

IF

Workflow A = Done

AND

Workflow B = Done

THEN

Enable Tahap Selanjutnya

ELSE

Lock Tahap Berikutnya

---

# Perpindahan Antar Role

Marketing

↓

Finance

↓

Admin

↓

Entry

↓

SPV Entry

↓

(Admin menunggu Workflow B)

↓

Pendamping Auditor

↓

Auditor

↓

Admin Perusahaan

↓

Finance

↓

Project Close

---

# Workflow Tidak Dapat Mundur

Project tidak dapat kembali ke tahap sebelumnya.

Yang dapat kembali hanyalah modul.

Contoh:

Workflow A

Entry

↓

SPV

↓

Revisi

↓

Entry

Project tetap berada pada fase Operasional.

---

# Workflow Revisi

Revisi hanya terjadi di dalam modul masing-masing.

Contoh:

Entry

↓

SPV

↓

Revisi

↓

Entry

atau

Pendamping

↓

Auditor

↓

Revisi

↓

Pendamping

Workflow utama tidak berubah.

---

# Trigger Workflow

Setiap perpindahan workflow dipicu oleh Action.

Contoh:

Lead Deal

↓

Generate Project

↓

Generate ID Klien

↓

Generate Draft Invoice Aktivasi

↓

Verifikasi Pembayaran

↓

Project Aktif

↓

Enable Workflow A

↓

Enable Workflow B

↓

Workflow Done

↓

Enable Sertifikat

---

# Status Workflow

Workflow menggunakan Status Modul.

Referensi:

07-status.md

---

# Hak Akses

Setiap workflow hanya dapat dijalankan oleh Role yang memiliki izin.

Referensi:

03-role-permission.md

---

# Activity Log

Setiap perubahan workflow wajib menghasilkan Activity Log.

Contoh:

Marketing membuat Lead

↓

Finance memeriksa dan menerbitkan Invoice

↓

Entry menyelesaikan SIHALAL

↓

SPV melakukan Approve

↓

Auditor melakukan Review

↓

Admin mengunggah Sertifikat

Referensi:

database/logs.md

---

# Notifikasi

Perubahan workflow dapat menghasilkan Notifikasi.

Contoh:

Lead Deal

↓

Notifikasi ke Finance

Invoice Dibayar

↓

Notifikasi ke Admin

Entry Selesai

↓

Notifikasi ke SPV

Audit Selesai

↓

Notifikasi ke Admin Perusahaan

Referensi:

08-notification.md

---

# Dokumen Selanjutnya

Workflow ini menjadi dasar bagi:

- 03-role-permission.md
- 04-database.md
- 05-api.md
- 06-routing.md
