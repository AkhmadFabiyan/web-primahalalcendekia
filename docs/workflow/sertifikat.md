# Workflow - Sertifikat

## Tujuan

Dokumen ini menjelaskan proses penyelesaian sertifikasi halal pada PHC System.

Workflow ini dimulai setelah seluruh proses operasional selesai, yaitu:

- Workflow A (Entry) telah selesai.
- Workflow B (Audit) telah selesai.

Tahap ini mencakup pembayaran biaya negara, unggah sertifikat halal, pencatatan nomor sertifikat, hingga penyelesaian administrasi Project.

---

# Referensi

Dokumen terkait:

- 01-business.md
- 02-workflow.md
- 03-role-permission.md
- 07-status.md
- 08-notification.md

Database:

- database/projects.md
- database/certificates.md
- database/invoices.md

UI:

- ui/klien.md
- ui/invoice.md

---

# Aktor

Role utama:

- Admin Perusahaan

Role pendukung:

- Finance

Role yang dapat melihat:

- Manager Operasional
- Direktur
- Super Admin

---

# Tujuan Bisnis

Tahap ini bertujuan untuk:

- Menyelesaikan kewajiban pembayaran negara.
- Mendokumentasikan sertifikat halal.
- Menyimpan nomor sertifikat.
- Menutup proses sertifikasi.

---

# Trigger

Workflow dimulai apabila:

Workflow A

↓

Selesai

DAN

Workflow B

↓

Selesai

Sistem secara otomatis membuka proses Invoice Negara.

---

# Workflow

Workflow A Selesai

+

Workflow B Selesai

↓

Admin Perusahaan mengunggah Invoice Negara

↓

Pembayaran Negara

↓

Verifikasi Finance

↓

Admin Perusahaan Upload Sertifikat

↓

Input Nomor Sertifikat

↓

Workflow Sertifikat selesai

↓

Pelunasan Project

↓

Project Selesai

---

# Detail Workflow

Admin Perusahaan memperbarui milestone pasca-audit secara manual melalui dropdown Status Auditor:

1. Menunggu Sidang Fatwa — 82%
2. Sidang Fatwa Selesai — 90%
3. Menunggu Penerbitan BPJPH — 95%
4. Sertifikat Halal Terbit — 100%

Persentase tidak dapat diedit. Status **Sertifikat Halal Terbit** hanya dapat dipilih setelah file, nomor, dan tanggal Sertifikat valid.

## 1. Sinkronisasi Workflow

Sistem memeriksa:

- Workflow Entry selesai.
- Workflow Audit selesai.

Apabila salah satu belum selesai.

↓

Workflow Sertifikat belum dapat dimulai.

---

## 2. Invoice Negara

Admin Perusahaan mengunggah Invoice Negara resmi dari BPJPH.

Invoice berisi:

- Nominal
- Jatuh tempo
- Keterangan

Setelah diterbitkan.

↓

Menunggu pembayaran.

Finance mengelola verifikasi pembayarannya.

---

## 3. Pembayaran Negara

Pembayaran dilakukan.

Finance melakukan verifikasi.

Apabila berhasil.

↓

Status Invoice Negara

Lunas

---

## 4. Upload Sertifikat

Admin Perusahaan mengunggah:

- File Sertifikat Halal.

Format yang didukung mengikuti kebijakan sistem.

---

## 5. Input Nomor Sertifikat

Admin Perusahaan mengisi:

- Nomor Sertifikat.
- Tanggal Terbit.
- Tanggal Berlaku (apabila diperlukan).

---

## 6. Validasi

Sistem memastikan:

- Sertifikat telah diunggah.
- Nomor sertifikat telah diisi.

Workflow Sertifikat dinyatakan selesai.

Admin Perusahaan kemudian memilih Status Auditor **Sertifikat Halal Terbit**. Sistem menghitung Progress Auditor 100%, mencatat histori, dan memperbarui Dashboard.

---

# Output

Invoice Negara selesai.

↓

Sertifikat tersimpan.

↓

Nomor Sertifikat tercatat.

↓

Project siap ditutup.

---

# Business Rule

Workflow ini hanya dapat dimulai apabila:

- Workflow A selesai.
- Workflow B selesai.

Invoice Negara harus lunas sebelum sertifikat dapat disimpan sebagai final.

Nomor Sertifikat wajib diisi.

File Sertifikat wajib tersedia.

Admin Perusahaan tidak dapat mengubah Invoice.

Finance tidak dapat mengubah data sertifikat.

---

# Validasi

Pastikan:

- Invoice Negara telah diverifikasi.
- File berhasil diunggah.
- Nomor Sertifikat unik.
- Project belum ditutup.

---

# Exception

Workflow A belum selesai.

↓

Tidak dapat memulai proses.

---

Workflow B belum selesai.

↓

Tidak dapat memulai proses.

---

Invoice Negara belum lunas.

↓

Workflow tidak dapat diselesaikan.

---

Upload gagal.

↓

Status tetap Proses.

---

# Status Modul

Belum Diproses

↓

Menunggu Upload

↓

Terbit

Referensi:

07-status.md

---

# Activity Log

Catat aktivitas berikut.

- Membuat Invoice Negara.
- Verifikasi Pembayaran Negara.
- Upload Sertifikat.
- Mengubah Sertifikat.
- Input Nomor Sertifikat.
- Menyelesaikan Workflow Sertifikat.

Referensi:

database/logs.md

---

# Notification

Workflow A dan B selesai

↓

Finance menerima notifikasi.

---

Invoice Negara diterbitkan

↓

Admin Perusahaan menerima notifikasi.

---

Pembayaran Negara diverifikasi

↓

Admin Perusahaan menerima notifikasi.

---

Sertifikat berhasil diunggah

↓

Finance menerima notifikasi.

↓

Manager Operasional menerima notifikasi.

Referensi:

08-notification.md

---

# KPI

Workflow Sertifikat dapat dimonitor berdasarkan:

- Jumlah Sertifikat diterbitkan.
- Waktu penyelesaian setelah audit.
- Jumlah Upload Sertifikat.
- Jumlah Project selesai.
- Lama penyelesaian Invoice Negara.

---

# Hubungan Workflow

Workflow ini menerima hasil dari:

- workflow/spv-entry.md
- workflow/audit.md

Workflow berikutnya:

- workflow/pembayaran.md

Dokumen ini hanya menjelaskan proses penyelesaian sertifikasi.
