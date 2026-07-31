# Workflow - Pendamping Auditor

## Tujuan

Dokumen ini menjelaskan proses kerja Pendamping Auditor pada PHC System.

Pendamping Auditor bertanggung jawab mengoordinasikan proses audit halal, mulai dari penjadwalan audit, penunjukan auditor, pencatatan hasil audit, hingga penyelesaian temuan.

Workflow ini merupakan bagian dari Workflow B dan berjalan paralel dengan Workflow Entry.

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
- database/documents.md
- database/logs.md

UI:

- ui/klien.md
- ui/tugas.md

---

# Aktor

Role utama:

- Pendamping Auditor

Role lain yang terlibat:

- Auditor

Role yang dapat melihat:

- Manager Operasional
- Direktur
- Super Admin

---

# Tujuan Bisnis

Pendamping Auditor bertugas:

- Menjadwalkan audit.
- Menentukan auditor.
- Mengelola pelaksanaan audit.
- Mencatat temuan audit.
- Mengunggah dokumen audit.
- Menutup proses audit.
- Memperbarui Status Pendamping secara manual sesuai perkembangan nyata.

---

# Trigger

Workflow dimulai ketika:

Project

↓

Aktif

↓

Pendamping Auditor menerima tugas.

Workflow ini tidak bergantung pada Workflow Entry.

---

# Workflow

Project Aktif

↓

Penjadwalan Audit

↓

Penunjukan Auditor

↓

Audit Lapangan

↓

Input Temuan

↓

Upload Lampiran

↓

Review Auditor

↓

Approve

↓

Workflow Audit selesai

---

# Detail Workflow

## 1. Penjadwalan Audit

Pendamping Auditor menentukan:

- Tanggal audit.
- Lokasi audit.
- Catatan.

Sistem menyimpan jadwal audit sebagai bagian dari Timeline Project.

Pendamping mengubah Status Pendamping menggunakan mapping berikut:

- Belum Diproses — 0%
- Menunggu Jadwal Audit — 15%
- Persiapan Audit — 30%
- Bukti Lapangan Belum Lengkap — 40%
- Jadwal Audit Ditentukan — 55%
- Audit Berlangsung — 75%
- Audit Selesai — 90%
- Menunggu Perbaikan Klien — 95%
- Pendampingan Selesai — 100%

---

## 2. Penunjukan Auditor

Pendamping Auditor memilih auditor yang akan melakukan pemeriksaan.

Setiap Project minimal memiliki satu Auditor.

Apabila diperlukan, Project dapat memiliki lebih dari satu Auditor.

---

## 3. Audit

Audit dilakukan oleh Auditor sesuai jadwal.

PHC System mencatat:

- Tanggal audit.
- Status pelaksanaan.
- Catatan.

Dokumen hasil audit dapat diunggah setelah audit selesai.

---

## 4. Temuan Audit

Pendamping Auditor mencatat hasil audit.

Contoh:

- Ketidaksesuaian dokumen.
- Ketidaksesuaian proses produksi.
- Kekurangan fasilitas.
- Temuan lainnya.

Setiap temuan dapat memiliki status penyelesaian.

---

## 5. Upload Lampiran

Dokumen pendukung dapat diunggah.

Contoh:

- Foto audit.
- Berita acara.
- Form audit.
- Dokumen tambahan.

---

## 6. Review Auditor

Auditor memeriksa hasil audit.

Pilihan:

- Approve
- Revisi

Auditor memperbarui Status Auditor menggunakan mapping berikut:

- Belum Diproses — 0%
- Pemeriksaan Dokumen — 10%
- Menunggu Audit Lapangan — 20%
- Audit Lapangan Selesai — 35%
- Ada Ketidaksesuaian — 45%
- Menunggu Bukti Perbaikan — 55%
- Perbaikan Diterima — 65%
- Laporan Audit Selesai — 75%
- Menunggu Sidang Fatwa — 82%
- Sidang Fatwa Selesai — 90%
- Menunggu Penerbitan BPJPH — 95%
- Sertifikat Halal Terbit — 100%

Auditor yang ditugaskan mengubah status sampai **Laporan Audit Selesai**. Milestone mulai **Menunggu Sidang Fatwa** sampai **Sertifikat Halal Terbit** diubah oleh Admin Perusahaan.

---

## 7. Approve

Apabila audit selesai.

Sistem akan:

- Memvalidasi bahwa Pendamping telah memilih **Pendampingan Selesai**.
- Menyimpan Status Auditor **Laporan Audit Selesai** yang dipilih Auditor saat Approve.
- Menutup Workflow Audit.
- Mengevaluasi apakah Workflow B telah selesai.
- Mengecek apakah Workflow A juga telah selesai.

Apabila Workflow A dan Workflow B telah selesai.

↓

Project masuk ke tahap:

**Invoice Negara**

Seluruh status dipilih manual melalui dropdown oleh role pemilik jalur. Persentase tidak dapat diketik dan selalu mengikuti `07-status.md`. Perubahan mundur meminta alasan dan seluruh transisi dicatat pada histori.

---

# Output

Audit selesai.

↓

Workflow B selesai.

↓

Menunggu sinkronisasi dengan Workflow A.

---

# Business Rule

Audit hanya dapat dilakukan pada Project Aktif.

Pendamping Auditor tidak dapat mengubah hasil Entry.

Auditor hanya dapat:

- Approve
- Revisi

Workflow Audit dapat berjalan bersamaan dengan Workflow Entry.

Project tidak dapat menuju tahap berikutnya apabila Workflow Audit belum selesai.

---

# Validasi

Sebelum Approve.

Pastikan:

- Audit telah dilaksanakan.
- Temuan telah dicatat.
- Dokumen pendukung telah tersedia (jika ada).

---

# Revisi

Apabila Auditor memilih Revisi.

Status Pendamping atau Status Auditor sesuai pemilik perbaikan

↓

Revisi

Pendamping Auditor melakukan perbaikan.

↓

Submit ulang.

↓

Review kembali.

---

# Status Progress

Pilihan resmi adalah Status Pendamping dan Status Auditor beserta persentase pada `07-status.md`. Alur di bawah hanya ringkasan lifecycle audit/review, bukan nilai enum atau daftar dropdown:

Belum Dijadwalkan

↓

Terjadwal

↓

Audit Berjalan

↓

Review

↓

Selesai

atau

↓

Revisi

↓

Audit Berjalan

↓

Review

Referensi:

07-status.md

---

# Exception

Auditor belum ditentukan.

↓

Audit tidak dapat dimulai.

---

Audit dibatalkan.

↓

Status kembali ke Proses.

---

Project dibatalkan.

↓

Workflow Audit dihentikan.

---

# Activity Log

Catat aktivitas berikut.

- Membuat jadwal audit.
- Mengubah jadwal.
- Menunjuk Auditor.
- Memulai Audit.
- Menambah Temuan.
- Upload Lampiran.
- Submit Audit.
- Approve.
- Revisi.

Referensi:

database/logs.md

---

# Notification

Project Aktif

↓

Pendamping Auditor menerima notifikasi.

---

Audit Dijadwalkan

↓

Auditor menerima notifikasi.

---

Submit Audit

↓

Auditor menerima notifikasi.

---

Audit Direvisi

↓

Pendamping Auditor menerima notifikasi.

---

Audit Disetujui

↓

Manager Operasional menerima notifikasi.

Referensi:

08-notification.md

---

# KPI

Pendamping Auditor dapat dimonitor berdasarkan:

- Jumlah Audit.
- Audit selesai.
- Audit tertunda.
- Jumlah Revisi.
- Rata-rata waktu Audit.
- Temuan per Project.

Auditor dapat dimonitor berdasarkan:

- Jumlah Audit.
- Jumlah Review.
- Jumlah Approve.
- Jumlah Revisi.
- Rata-rata waktu Review.

---

# Hubungan Workflow

Workflow ini dimulai setelah:

workflow/finance.md

Workflow ini berjalan paralel dengan:

workflow/entry.md

Workflow ini harus selesai bersama:

workflow/spv-entry.md

Sebelum proses berlanjut ke:

workflow/sertifikat.md

Dokumen ini hanya menjelaskan proses Audit.
