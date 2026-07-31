# UI - Dashboard

## Tujuan

Dashboard adalah halaman pertama setelah login dan menggunakan route `/dashboard`.

Dashboard memiliki tiga mode:

1. **Dashboard Progres Operasional** untuk seluruh staf internal selain Super Admin.
2. **Dashboard Administrasi Sistem** khusus Super Admin.
3. **Dashboard Klien** sebagai portal read-only khusus role Klien.

Dashboard menggunakan komponen dan API yang kompatibel dengan Filament 5, PHP 8.4.23, dan Laravel 13.8.0. Kode atau pola implementasi dari Filament, PHP, atau Laravel versi lama tidak boleh digunakan.

Referensi:

- `03-role-permission.md`
- `05-api.md`
- `06-routing.md`
- `07-status.md`
- `08-notification.md`
- `database/workflows.md`

---

# Dashboard Progres Operasional

## Pengguna

Tampilan yang sama digunakan oleh:

- Direktur
- Manager Operasional
- Marketing
- Finance
- Admin
- Entry
- SPV Entry
- Pendamping Auditor
- Auditor
- Admin Perusahaan

Dashboard ini menampilkan kondisi seluruh Client dan Project secara organisasi, bukan hanya Assignment pengguna. Seluruh staf internal boleh membuka detail Client dan Project tunggalnya. Data keuangan sensitif, Nominal Mitra, Invoice Mitra, kredensial, dan catatan internal tetap mengikuti permission masing-masing.

Super Admin tidak menggunakan tampilan ini sebagai beranda.

## Header

Header mengikuti struktur visual referensi:

- Judul: **PHC Halal Progress Dashboard**
- Subjudul: **Pantau progres entry, pendampingan audit, auditor, dan penerbitan sertifikat dalam satu tampilan**
- tanggal pembaruan data terakhir
- filter periode, layanan, tipe Client, PIC, dan status

Filter hanya mengubah agregat dan daftar drill-down. Filter tidak mengubah data Project.

## KPI Baris Pertama

| KPI | Definisi |
|---|---|
| Total Klien | Jumlah Client unik pada scope filter |
| Proses Entry | Project dengan Progress Entry di atas 0% dan di bawah 100% |
| Menunggu Audit | Project pada tahap menunggu atau mempersiapkan audit |
| Sertifikat Terbit | Project dengan Status Auditor `HALAL_CERTIFICATE_ISSUED` |

## KPI Baris Kedua

| KPI | Definisi |
|---|---|
| Audit 7 Hari | Audit terjadwal dari hari ini sampai tujuh hari ke depan |
| Proses Revisi | Project yang sedang revisi Entry, perbaikan pendampingan, ketidaksesuaian, atau bukti perbaikan |
| Perlu Follow Up | Deadline mendekat/terlewati atau status sedang menunggu dokumen, jadwal, maupun bukti dari pihak lain |
| Kritis > 7 Hari | Project belum selesai dan tidak mengalami pembaruan progress lebih dari tujuh hari kalender |

Klik KPI membuka daftar Client yang membentuk angka tersebut. ID yang ditampilkan adalah **ID Klien**, bukan ID Project.

## Ringkasan Tahap

Tabel ringkas menggunakan kolom:

1. Tahap
2. Jumlah
3. Keterangan

Tahap agregat:

- Belum/Proses Entry
- Menunggu/Persiapan Audit
- Audit/Revisi
- Sidang Fatwa/BPJPH
- Sertifikat Terbit

Satu Project hanya dihitung pada satu tahap, yaitu tahap terjauh yang sudah dicapai:

1. Sertifikat Terbit jika status Auditor 100%.
2. Sidang Fatwa/BPJPH jika Progress Auditor 82%–95%.
3. Audit/Revisi jika Progress Pendamping minimal 75% atau Progress Auditor 10%–75%.
4. Menunggu/Persiapan Audit jika Progress Pendamping 15%–55%.
5. Belum/Proses Entry untuk kondisi lainnya.

## Panduan Cepat

Panel **Panduan Cepat** berisi:

1. Marketing membuat Lead dan mengubahnya menjadi Deal; sistem otomatis membuat Client, ID Klien, dan satu Project.
2. Entry memperbarui Status Entry; persentase mengikuti status secara otomatis.
3. Pendamping Auditor memperbarui Status Pendamping, jadwal audit, dan catatan.
4. Auditor memperbarui Status Auditor; Admin Perusahaan memperbarui milestone pasca-audit yang menjadi tanggung jawabnya.
5. Manajemen memantau Dashboard; data merah atau Kritis wajib segera ditindaklanjuti.

## Visualisasi

Dashboard menampilkan:

- bar chart **Distribusi Tahap Sertifikasi**
- donut chart **Kondisi Pembaruan Data**

Kondisi pembaruan dibagi menjadi:

- **Terkini**: pembaruan terakhir maksimal tujuh hari
- **Perlu Follow Up**: deadline atau status memerlukan tindak lanjut
- **Kritis**: tidak ada pembaruan lebih dari tujuh hari dan Project belum selesai
- **Selesai**: sertifikat telah terbit atau Project telah selesai

Klik batang, bagian donut, atau legenda membuka daftar Client yang sesuai. Nilai chart dan KPI harus berasal dari query agregat yang sama agar tidak berbeda.

## Daftar Prioritas

Di bawah chart ditampilkan daftar ringkas Project Kritis dan Perlu Follow Up:

- ID Klien
- Nama Client
- Tahap
- Status Entry
- Status Pendamping
- Status Auditor
- pembaruan terakhir
- PIC
- deadline
- aksi **Buka Detail**

Dashboard tidak menyediakan Bulk Action, Create, atau Delete.

---

# Dropdown Progress Manual

## Lokasi

Tiga dropdown ditampilkan:

- pada detail Client internal, tab **Workflow**
- pada drawer detail dari KPI/chart Dashboard
- pada detail Tugas apabila pengguna memiliki permission perubahan

Dropdown yang tidak boleh diubah oleh pengguna ditampilkan sebagai badge/read-only. Action yang tidak berizin tidak dirender. Request langsung tanpa izin diarahkan ke `/dashboard` untuk web atau diperlakukan sebagai resource tidak tersedia untuk API sesuai `05-api.md`; pengguna tidak melihat halaman 403.

## Aturan

- Pengguna memilih **status**, bukan mengetik persentase.
- Persentase bersifat read-only dan diturunkan dari mapping resmi di `07-status.md`.
- Request API tidak menerima `progress_percent`.
- Perubahan boleh dilakukan manual selama sesuai pemilik jalur dan permission.
- Perubahan mundur atau penurunan progress wajib meminta catatan alasan dan konfirmasi.
- Setiap perubahan menyimpan status sebelum/sesudah, aktor, waktu, catatan, dan metadata sumber.
- Perubahan menulis `workflow_histories`, Activity Log, waktu pembaruan terakhir, dan memperbarui cache Dashboard.
- Status final yang memiliki prasyarat bisnis tetap divalidasi server; dropdown tidak boleh melewati prasyarat sertifikat atau workflow.

## Pemilik Perubahan

| Jalur | Pengubah Utama | Pengubah Tambahan |
|---|---|---|
| Entry | Entry yang ditugaskan | SPV Entry untuk review/koreksi |
| Pendamping | Pendamping Auditor yang ditugaskan | Tidak ada |
| Auditor | Auditor yang ditugaskan | Admin Perusahaan hanya untuk milestone pasca-audit |
| Seluruh jalur | — | Super Admin dapat override dengan alasan wajib |

Milestone pasca-audit milik Admin Perusahaan:

- Menunggu Sidang Fatwa
- Sidang Fatwa Selesai
- Menunggu Penerbitan BPJPH
- Sertifikat Halal Terbit

Role Direktur, Manager Operasional, Marketing, Finance, dan Admin hanya membaca ketiga status kecuali mendapat permission eksplisit tambahan.

## Tampilan Nilai

Setiap jalur menampilkan:

- label status
- persentase otomatis
- progress bar
- waktu pembaruan terakhir
- nama pengubah terakhir
- catatan terakhir
- tombol histori

---

# Dashboard Administrasi Sistem

Dashboard khusus Super Admin berfokus pada kesehatan dan administrasi sistem:

- jumlah User aktif/nonaktif
- Role dan permission
- queue, failed job, dan scheduler
- storage dan backup terakhir
- application health
- Activity Log terbaru
- versi runtime: PHP 8.4.23, Laravel 13.8.0, Filament 5.x
- shortcut User Management dan Settings

Super Admin tetap dapat membuka laporan atau halaman Client untuk melihat dashboard operasional dan melakukan override, tetapi beranda `/dashboard` miliknya bukan Dashboard Progres Operasional.

---

# Dashboard Klien

Role Klien hanya mengakses `/dashboard`. Semua informasi bersifat read-only dan dibatasi oleh `users.client_id`.

Dashboard Klien menampilkan:

- detail Client
- ID Klien dan Project tunggal tanpa ID Project
- status Project
- Status dan Progress Entry, Pendamping, serta Auditor
- tahap saat ini dan pembaruan terakhir
- dokumen dengan `is_client_visible = true`
- Invoice audience Client dan histori Payment
- Sertifikat
- Timeline dengan `is_client_visible = true`
- notifikasi milik User

Status publik menggunakan label yang sama, tetapi catatan internal, Assignment internal, alasan revisi internal, Nominal Mitra, dan Invoice Partner tidak ditampilkan.

Jika akun belum terhubung ke Client, tampilkan empty state aman tanpa data. Request role Klien tidak boleh menerima `client_id` atau `project_id` sebagai penentu scope.

---

# Loading, Empty, dan Error State

- Gunakan skeleton loader, bukan spinner satu halaman.
- Widget tanpa data menampilkan empty state yang menjelaskan scope filter.
- Kegagalan query menampilkan pesan ringkas dan tombol **Muat Ulang**.
- Data terakhir yang berhasil dimuat boleh ditampilkan dengan penanda bahwa pembaruan gagal.

---

# Responsive dan Aksesibilitas

- Desktop: KPI empat kolom, ringkasan dan panduan dua kolom, chart dua kolom.
- Tablet: dua kolom.
- Mobile: satu kolom dan tabel menjadi card list.
- Warna bukan satu-satunya pembeda status; selalu sertakan label dan angka.
- Chart harus memiliki legenda, tooltip, dan ringkasan tekstual.
- Dropdown dapat dioperasikan dengan keyboard dan menampilkan konfirmasi perubahan mundur.

---

# Business Rule

- Satu Client sama dengan satu Project.
- Dashboard dan seluruh drill-down menggunakan ID Klien sebagai identitas tampilan.
- Seluruh staf internal non–Super Admin melihat dataset progres organisasi yang sama; action tetap mengikuti permission dan Assignment.
- Dashboard tidak menampilkan action yang tidak diizinkan.
- Penyembunyian UI bukan pengganti policy, middleware, dan query scope.
- Dashboard tidak menyediakan Create, Delete, atau Bulk Action.
- Tanggal pembaruan progress berasal dari histori transisi terbaru, bukan dari perubahan profil Client.
- Query agregat harus menghindari penghitungan ganda akibat Invoice pasangan Mitra atau banyak Assignment.

---

# Future Enhancement

- real-time refresh
- saved filter
- export dashboard sesuai permission
- SLA trend
- comparison period
- dashboard builder setelah struktur utama stabil
