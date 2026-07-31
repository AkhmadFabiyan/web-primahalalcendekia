# 13. Roadmap

## Tujuan

Dokumen ini mendefinisikan urutan pengembangan PHC System dari fondasi hingga sistem siap dioperasikan, dipantau, dan diintegrasikan.

Roadmap merupakan acuan milestone produk, bukan daftar pekerjaan harian. Detail teknis dan task implementasi dibuat terpisah berdasarkan phase yang sedang berjalan.

Seluruh perubahan urutan, ruang lingkup, atau prioritas roadmap harus mendapat persetujuan Project Owner.

---

# Referensi

Seluruh implementasi phase wajib mengacu pada sumber resmi berikut.

- `00-overview.md`
- `00-glossary.md`
- `01-business.md`
- `02-workflow.md`
- `03-role-permission.md`
- `04-database.md`
- `05-api.md`
- `06-routing.md`
- `07-status.md`
- `08-notification.md`
- `09-ui-ux.md`
- `10-design-system.md`
- `11-folder-structure.md`
- `12-development-rules.md`
- `14-brand-guideline.md`

Roadmap tidak mendefinisikan ulang role, status, workflow, struktur database, atau kontrak API.

---

# Visi Produk

PHC System dikembangkan sebagai platform operasional terintegrasi untuk mengelola seluruh proses layanan sertifikasi halal.

Target utama:

- Mengurangi pekerjaan manual dan penggunaan spreadsheet terpisah.
- Mempercepat perpindahan pekerjaan antar divisi.
- Memudahkan monitoring Project dan SLA.
- Menyediakan histori aktivitas yang lengkap.
- Menjaga keamanan serta konsistensi data.
- Menjadi Single Source of Truth bagi seluruh divisi.

---

# Prinsip Pengembangan

- Business First
- Project-Centric
- Workflow Driven
- Incremental Development
- Security by Design
- Backward Compatible
- Observable
- User Feedback Driven

Fitur dengan dampak bisnis terbesar dan dependency paling mendasar dikerjakan lebih dahulu.

---

# Status Roadmap

| Status | Keterangan |
|---|---|
| Planned | Belum dimulai |
| Ready | Scope dan dependency siap dikerjakan |
| In Progress | Sedang dikembangkan |
| In Review | Sedang melalui review, QA, atau UAT |
| Completed | Selesai dan memenuhi exit criteria |
| On Hold | Ditunda sementara |
| Cancelled | Dibatalkan dengan persetujuan Project Owner |

Status awal seluruh phase pada dokumen ini adalah **Planned**.

Status Roadmap adalah status delivery pengembangan, bukan status data PHC System. Status bisnis dan resource aplikasi tetap mengikuti `07-status.md`.

---

# Aturan Eksekusi

- Satu phase menghasilkan output yang dapat diuji atau digunakan.
- Phase berikutnya hanya dimulai jika dependency wajib telah tersedia.
- Beberapa phase dapat berjalan paralel apabila tidak memiliki dependency langsung.
- Perubahan status, role, workflow, database, dan API harus memperbarui dokumen sumber resminya.
- Fitur di luar scope phase dipindahkan ke backlog, bukan disisipkan tanpa persetujuan.
- Setiap akhir milestone wajib dilakukan demo, evaluasi risiko, dan persetujuan Project Owner.

---

# Milestone A — Fondasi Produk

## Phase 1 — Product Discovery dan Scope Baseline

**Target:** Menyamakan kebutuhan bisnis, ruang lingkup MVP, terminologi, dan batasan sistem.

**Fitur/aktivitas:**

- Validasi business flow end-to-end.
- Validasi aktor, role, status, dan modul.
- Penetapan scope MVP dan backlog.
- Penetapan acceptance criteria tingkat produk.

**Output:** Product scope baseline disetujui.

**Dependency:** Tidak ada.

**File terkait:** `00-overview.md`, `00-glossary.md`, `01-business.md`, `02-workflow.md`, `13-roadmap.md`.

---

## Phase 2 — Arsitektur Aplikasi dan Environment

**Target:** Menyiapkan kerangka teknis yang konsisten untuk pengembangan.

**Fitur/aktivitas:**

- Struktur project dan modularisasi.
- Version gate PHP 8.4.23, Laravel 13.8.0, Filament 5.x, Livewire 4.x, dan Tailwind CSS 4.1+.
- Konfigurasi environment development, staging, dan production.
- Konfigurasi database, cache, queue, storage, mail, dan realtime.
- Standar migration, seeder, logging, serta error handling.

**Output:** Aplikasi dapat dijalankan pada environment development dan staging.

**Dependency:** Phase 1.

**File terkait:** `04-database.md`, `05-api.md`, `11-folder-structure.md`, `12-development-rules.md`; area source `src/app/`, `src/lib/`, `src/providers/`, dan konfigurasi environment.

---

## Phase 3 — Design System dan Application Shell

**Target:** Menyediakan fondasi UI yang konsisten dan responsif.

**Fitur/aktivitas:**

- Token warna, tipografi, spacing, dan icon.
- Komponen form, table, badge, modal, drawer, dan feedback state.
- Header, sidebar, breadcrumb, content area, dan navigation.
- Empty, loading, success, serta error state.

**Output:** Application shell dan reusable UI components siap digunakan.

**Dependency:** Phase 2.

**File terkait:** `09-ui-ux.md`, `10-design-system.md`, `14-brand-guideline.md`; area source `src/components/ui/`, `src/layouts/`, `src/styles/`, dan `src/assets/`.

---

## Phase 4 — Authentication dan Session

**Target:** Menyediakan akses sistem yang aman.

**Fitur/aktivitas:**

- Login dan logout.
- Lupa serta reset password.
- Session management.
- Rate limiting dan proteksi brute force.
- Pengelolaan profil serta perubahan password.

**Output:** User dapat mengakses sistem melalui autentikasi yang aman.

**Dependency:** Phase 2 dan Phase 3.

**File terkait:** `03-role-permission.md`, `05-api.md`, `06-routing.md`, `database/users.md`, `ui/settings.md`; area source `src/modules/auth/`, `src/services/auth.ts`, `src/providers/AuthProvider`, dan `src/middleware/auth.ts`.

---

## Phase 5 — Role dan Permission

**Target:** Menerapkan hak akses sesuai tanggung jawab pengguna.

**Fitur/aktivitas:**

- Role sesuai `03-role-permission.md`.
- Permission untuk menu, halaman, record, dan action.
- Policy dan authorization pada backend.
- Penyembunyian navigasi serta action berdasarkan akses.

**Output:** Setiap role hanya dapat melihat dan melakukan aksi yang diizinkan.

**Dependency:** Phase 4.

**File terkait:** `03-role-permission.md`, `05-api.md`, `06-routing.md`, `07-status.md`, `database/users.md`; area source `src/modules/users/`, `src/constants/roles.ts`, `src/constants/permissions.ts`, `src/hooks/usePermission`, dan `src/middleware/permission.ts`.

---

## Phase 6 — User Management dan System Settings

**Target:** Memungkinkan administrator mengelola pengguna dan konfigurasi dasar.

**Fitur/aktivitas:**

- CRUD User.
- Aktivasi dan deaktivasi User.
- Assignment role.
- Aksi **Buat Akun** untuk membuat akun login Klien secara otomatis tanpa form manual.
- Email akun Klien otomatis dengan pola `{user}@primahalalcendekia.com`.
- Pengaturan profil perusahaan.
- Pengaturan nomor dokumen, nilai default, dan konfigurasi aplikasi.

**Output:** Administrasi pengguna dan pengaturan dasar dapat dilakukan dari sistem.

**Dependency:** Phase 5.

**File terkait:** `03-role-permission.md`, `05-api.md`, `06-routing.md`, `database/users.md`, `ui/settings.md`; area source `src/modules/users/`, `src/modules/settings/`, dan `src/app/settings/`.

---

# Milestone B — Akuisisi dan Data Utama

## Phase 7 — Master Client

**Target:** Membentuk sumber data utama untuk seluruh klien.

**Fitur/aktivitas:**

- Client dibuat otomatis dari Lead Deal; staf hanya melihat atau memperbarui sesuai permission.
- ID Client otomatis dan unik.
- Relasi tepat satu Client dengan satu Project.
- Tipe Client Langsung atau Mitra.
- Master Partner dan relasi satu Partner ke banyak Client.
- Data perusahaan dan kontak utama.
- Validasi data unik.
- Pencarian dan filter Client.
- Histori perubahan data penting.

**Output:** Data Client terpusat dan siap digunakan oleh modul lain.

**Dependency:** Phase 5 dan Phase 6.

**File terkait:** `04-database.md`, `05-api.md`, `06-routing.md`, `database/clients.md`, `database/partners.md`, `ui/klien.md`; area source `src/modules/clients/` dan `src/app/clients/`.

---

## Phase 8 — Lead Management

**Target:** Mendigitalisasi proses pencatatan dan tindak lanjut prospek.

**Fitur/aktivitas:**

- Create, view, dan update Lead tanpa Delete.
- Assignment Marketing.
- Tipe Client, Partner, Nominal Client, dan Nominal Mitra.
- Status Draft, Deal, dan Batal.
- Catatan follow-up.
- Filter, sorting, dan ringkasan Lead.

**Output:** Marketing dapat mengelola Lead hingga keputusan Deal atau Batal.

**Dependency:** Phase 7.

**File terkait:** `01-business.md`, `02-workflow.md`, `07-status.md`, `workflow/marketing.md`, `database/leads.md`, `database/clients.md`, `database/partners.md`, `ui/leads.md`; area source `src/modules/leads/` dan `src/app/leads/`.

---

## Phase 9 — Lead Conversion dan Project Initiation

**Target:** Mengubah Lead Deal menjadi Client dan Project tanpa duplikasi data.

**Fitur/aktivitas:**

- Konversi Lead menjadi Client.
- Pembuatan Project otomatis.
- Pembuatan ID Client otomatis sebagai identitas bisnis utama.
- Project hanya menggunakan UUID internal tanpa Business ID.
- Snapshot Tipe Client, Partner, Nominal Client, dan Nominal Mitra.
- Draft Invoice tunggal untuk Langsung atau pasangan Invoice untuk Mitra.
- Snapshot data komersial awal.
- Status awal Menunggu Aktivasi.

**Output:** Lead Deal menghasilkan Project yang siap masuk proses aktivasi.

**Dependency:** Phase 8.

**File terkait:** `01-business.md`, `02-workflow.md`, `07-status.md`, `workflow/marketing.md`, `database/clients.md`, `database/projects.md`; area source `src/modules/leads/`, `src/modules/clients/`, dan `src/modules/projects/`.

---

## Phase 10 — Client dan Project Workspace Dasar

**Target:** Menyediakan pusat informasi untuk setiap Project.

**Fitur/aktivitas:**

- Client list dan detail Project tunggalnya.
- Seluruh staf internal dapat melihat detail; action mengikuti permission dan Assignment.
- Ringkasan status utama serta status modul.
- Informasi Client, PIC, dan layanan.
- Tab workflow, pembayaran, dokumen, dan aktivitas.
- Pembatasan record berdasarkan role.

**Output:** Seluruh informasi Project dapat diakses melalui satu workspace.

**Dependency:** Phase 9.

**File terkait:** `02-workflow.md`, `03-role-permission.md`, `06-routing.md`, `07-status.md`, `database/projects.md`, `ui/klien.md`; area source `src/modules/projects/` dan halaman detail Project.

---

# Milestone C — Aktivasi dan Keuangan Awal

Seluruh fitur Invoice dan transaksi Payment pada milestone ini dibangun dalam satu source module `src/modules/payments/`. Pemisahan phase menunjukkan urutan delivery fitur, bukan pemisahan modul.

## Phase 11 — Invoice Aktivasi

**Target:** Mendigitalisasi penerbitan tagihan awal Project.

**Fitur/aktivitas:**

- Pembuatan draft Invoice Aktivasi otomatis dari event Lead Deal.
- Audience Invoice Client dan Partner.
- Billing group untuk memasangkan dua Invoice Mitra sebagai satu transaksi.
- Discount Mitra selalu nol.
- Nomor Invoice otomatis.
- Draft, penerbitan, dan pembatalan Invoice.
- Rincian nilai, pajak, jatuh tempo, dan catatan.
- Dokumen Invoice siap cetak.

**Output:** Finance dapat menerbitkan Invoice Aktivasi yang tervalidasi.

**Dependency:** Phase 9 dan Phase 10.

**File terkait:** `01-business.md`, `02-workflow.md`, `07-status.md`, `workflow/finance.md`, `database/invoices.md`, `ui/invoice.md`, `ui/pembayaran.md`; area source `src/modules/payments/`.

---

## Phase 12 — Penerimaan dan Verifikasi Pembayaran

**Target:** Mencatat pembayaran serta memastikan validitas bukti bayar.

**Fitur/aktivitas:**

- Upload bukti pembayaran.
- Pencatatan nilai dan tanggal pembayaran.
- Verifikasi atau penolakan oleh Finance.
- Dukungan pembayaran sebagian.
- Payment history dan rekonsiliasi terhadap Invoice.

**Output:** Pembayaran terlacak dan status Invoice dihitung secara konsisten.

**Dependency:** Phase 11.

**File terkait:** `02-workflow.md`, `07-status.md`, `workflow/finance.md`, `workflow/pembayaran.md`, `database/invoices.md`, `database/payments.md`, `ui/pembayaran.md`; area source `src/modules/payments/`.

---

## Phase 13 — Aktivasi Project

**Target:** Mengaktifkan Project secara otomatis setelah syarat pembayaran terpenuhi.

**Fitur/aktivitas:**

- Validasi rule aktivasi.
- Transisi Menunggu Aktivasi menjadi Aktif.
- Pencegahan transisi status ilegal.
- Pencatatan waktu dan pelaku aktivasi.
- Trigger tugas awal operasional.

**Output:** Project terverifikasi siap memasuki proses operasional.

**Dependency:** Phase 12.

**File terkait:** `02-workflow.md`, `07-status.md`, `08-notification.md`, `workflow/finance.md`, `database/projects.md`, `database/logs.md`; area source `src/modules/projects/`, `src/modules/workflow/`, dan service aktivasi.

---

## Phase 14 — Assignment dan Work Queue

**Target:** Mendistribusikan pekerjaan ke divisi dan pengguna yang tepat.

**Fitur/aktivitas:**

- Assignment Admin, Entry, SPV Entry, Pendamping Auditor, dan Auditor.
- Daftar tugas per User.
- Menu Tugas untuk Admin Perusahaan, Entry, SPV Entry, Auditor, dan Pendamping Auditor.
- Kolom No., ID Klien, Timestamp Masuk, Deadline, dan seluruh PIC.
- Badge jumlah tugas belum selesai pada navigation menu.
- Tanpa Create, Delete, dan Bulk Action.
- Prioritas dan due date.
- Reassignment dengan histori.
- Ringkasan beban kerja.

**Output:** Setiap Project aktif memiliki penanggung jawab dan antrean kerja yang jelas.

**Dependency:** Phase 13.

**File terkait:** `02-workflow.md`, `03-role-permission.md`, `07-status.md`, `database/projects.md`, `database/project-assignments.md`, `database/tasks.md`, `ui/tugas.md`; area source `src/modules/projects/`, `src/modules/workflow/`, dan halaman tugas.

---

# Milestone D — Workflow Operasional Paralel

## Phase 15 — Administrasi Dokumen

**Target:** Mengelola kelengkapan dokumen Project.

**Fitur/aktivitas:**

- Checklist dokumen wajib.
- Upload, preview, download, dan versi file.
- Status Belum Mulai, Proses, Lengkap, dan Revisi.
- Catatan kekurangan serta permintaan revisi.
- Validasi jenis, ukuran, dan keamanan file.

**Output:** Dokumen Project lengkap dan siap digunakan oleh proses Entry.

**Dependency:** Phase 14.

**File terkait:** `02-workflow.md`, `07-status.md`, `workflow/admin.md`, `database/documents.md`, `ui/klien.md`; area source `src/modules/documents/` dan service storage.

---

## Phase 16 — Akun SIHALAL dan Persiapan Entry

**Target:** Menyiapkan data akses serta kebutuhan awal Entry SIHALAL.

**Fitur/aktivitas:**

- Pencatatan akun SIHALAL secara aman.
- Checklist kesiapan Entry.
- Serah terima Admin ke Entry.
- Validasi kelengkapan dokumen.
- Pencatatan tanggal mulai.

**Output:** Project siap dikerjakan oleh role Entry.

**Dependency:** Phase 15.

**File terkait:** `02-workflow.md`, `03-role-permission.md`, `workflow/admin.md`, `workflow/entry.md`, `database/documents.md`, `database/projects.md`, `database/sihalal-credentials.md`, `ui/tugas.md`; area source `src/modules/documents/` dan `src/modules/workflow/`.

---

## Phase 17 — Workflow Entry SIHALAL

**Target:** Mendigitalisasi progres pekerjaan Entry.

**Fitur/aktivitas:**

- Status Belum Mulai, Proses, Review, Revisi, dan Selesai.
- Checklist pekerjaan Entry.
- Catatan pekerjaan dan kendala.
- Submit hasil ke SPV Entry.
- Riwayat perubahan dan durasi proses.

**Output:** Hasil Entry dapat diajukan untuk review.

**Dependency:** Phase 16.

**File terkait:** `02-workflow.md`, `07-status.md`, `workflow/entry.md`, `database/documents.md`, `database/projects.md`, `ui/tugas.md`; area source `src/modules/workflow/` dan modul tugas Entry.

---

## Phase 18 — Review SPV Entry

**Target:** Menjamin hasil Entry melalui proses review dan approval.

**Fitur/aktivitas:**

- Review hasil Entry.
- Approve atau revisi.
- Catatan revisi wajib.
- Siklus resubmit.
- Finalisasi Workflow A.

**Output:** Workflow A selesai dan memiliki bukti persetujuan SPV Entry.

**Dependency:** Phase 17.

**File terkait:** `02-workflow.md`, `03-role-permission.md`, `07-status.md`, `workflow/spv-entry.md`, `database/projects.md`, `database/logs.md`, `ui/tugas.md`; area source `src/modules/workflow/` dan modul review SPV Entry.

---

## Phase 19 — Perencanaan Audit

**Target:** Menyiapkan jadwal dan kebutuhan audit.

**Fitur/aktivitas:**

- Assignment Pendamping Auditor dan Auditor.
- Jadwal, lokasi, serta metode audit.
- Checklist persiapan audit.
- Perubahan jadwal dengan histori.
- Status Belum Dijadwalkan dan Terjadwal.

**Output:** Audit terjadwal dengan personel serta informasi yang lengkap.

**Dependency:** Phase 14.

**File terkait:** `02-workflow.md`, `03-role-permission.md`, `07-status.md`, `workflow/audit.md`, `database/projects.md`, `ui/tugas.md`; area source `src/modules/workflow/` dan modul audit.

---

## Phase 20 — Pelaksanaan Pendampingan Audit

**Target:** Mencatat proses pendampingan dan hasil audit lapangan.

**Fitur/aktivitas:**

- Status Audit Berjalan.
- Checklist audit.
- Catatan temuan.
- Upload bukti pendukung.
- Submit hasil ke Auditor.

**Output:** Hasil pendampingan audit siap direview oleh Auditor.

**Dependency:** Phase 19.

**File terkait:** `02-workflow.md`, `07-status.md`, `workflow/audit.md`, `database/documents.md`, `database/projects.md`, `ui/tugas.md`; area source `src/modules/workflow/`, `src/modules/documents/`, dan modul audit.

---

## Phase 21 — Review Auditor

**Target:** Menjamin hasil audit melalui review, revisi, dan approval.

**Fitur/aktivitas:**

- Review hasil audit.
- Approve atau revisi.
- Catatan temuan serta tindak lanjut.
- Siklus resubmit.
- Finalisasi Workflow B.

**Output:** Workflow B selesai dan memiliki bukti persetujuan Auditor.

**Dependency:** Phase 20.

**File terkait:** `02-workflow.md`, `03-role-permission.md`, `07-status.md`, `workflow/audit.md`, `database/projects.md`, `database/logs.md`, `ui/tugas.md`; area source `src/modules/workflow/` dan modul review Auditor.

---

## Phase 22 — Sinkronisasi Workflow A dan B

**Target:** Mengontrol kelanjutan Project berdasarkan dua workflow paralel.

**Fitur/aktivitas:**

- Evaluasi otomatis status Workflow A dan Workflow B.
- Pencegahan proses lanjutan jika salah satu workflow belum selesai.
- Progress gabungan.
- Transisi ke Menunggu Invoice Negara.
- Penanganan revisi setelah sinkronisasi.

**Output:** Project hanya berlanjut ketika Workflow A dan Workflow B telah selesai.

**Dependency:** Phase 18 dan Phase 21.

**File terkait:** `01-business.md`, `02-workflow.md`, `07-status.md`, `08-notification.md`, `database/projects.md`, `database/logs.md`; area source `src/modules/workflow/`, service sinkronisasi status, dan event workflow.

---

# Milestone E — Sertifikat, Pelunasan, dan Penutupan

## Phase 23 — Invoice Negara

**Target:** Mencatat proses biaya negara setelah workflow operasional selesai.

**Fitur/aktivitas:**

- Pencatatan Invoice Negara.
- Nomor, tanggal, nilai, dan jatuh tempo.
- Upload dokumen Invoice Negara.
- Tracking status proses.
- Transisi Project menuju Menunggu Sertifikat.

**Output:** Invoice Negara tercatat dan dapat dipantau.

**Dependency:** Phase 22.

**File terkait:** `01-business.md`, `02-workflow.md`, `07-status.md`, `workflow/sertifikat.md`, `database/invoices.md`, `ui/invoice.md`, `ui/pembayaran.md`; area source `src/modules/payments/` dan `src/modules/workflow/`.

---

## Phase 24 — Penerbitan Sertifikat

**Target:** Mencatat sertifikat halal yang telah terbit.

**Fitur/aktivitas:**

- Nomor dan tanggal Sertifikat.
- Masa berlaku.
- Upload dan preview file Sertifikat.
- Validasi kelengkapan metadata.
- Transisi menjadi Sertifikat Terbit.

**Output:** Sertifikat tersimpan aman dan terhubung dengan Project.

**Dependency:** Phase 23.

**File terkait:** `02-workflow.md`, `07-status.md`, `workflow/sertifikat.md`, `database/certificates.md`, `database/projects.md`, `ui/klien.md`; area source `src/modules/projects/`, `src/modules/certificates/`, dan modul Sertifikat.

---

## Phase 25 — Invoice Termin dan Pelunasan

**Target:** Mengelola seluruh tagihan lanjutan hingga pembayaran akhir.

**Fitur/aktivitas:**

- Invoice Termin.
- Invoice Pelunasan.
- Jadwal termin.
- Verifikasi pembayaran lanjutan.
- Payment summary dan sisa tagihan.

**Output:** Seluruh kewajiban pembayaran Project dapat dihitung dan ditelusuri.

**Dependency:** Phase 12 dan Phase 24.

**File terkait:** `01-business.md`, `02-workflow.md`, `07-status.md`, `workflow/finance.md`, `workflow/pembayaran.md`, `database/invoices.md`, `database/payments.md`, `ui/invoice.md`, `ui/pembayaran.md`; area source `src/modules/payments/`.

---

## Phase 26 — Penyelesaian dan Penutupan Project

**Target:** Menutup Project yang telah memenuhi seluruh kewajiban.

**Fitur/aktivitas:**

- Checklist penyelesaian.
- Validasi Sertifikat dan pelunasan.
- Transisi Menunggu Pelunasan menjadi Selesai.
- Penguncian data kritis setelah selesai.
- Prosedur pembatalan Project dengan alasan dan approval.

**Output:** Project selesai atau dibatalkan dengan jejak audit yang lengkap.

**Dependency:** Phase 25.

**File terkait:** `01-business.md`, `02-workflow.md`, `07-status.md`, `workflow/pembayaran.md`, `database/projects.md`, `database/logs.md`; area source `src/modules/projects/`, `src/modules/workflow/`, dan service penutupan Project.

---

## Phase 27 — Dokumen Final dan Arsip Project

**Target:** Menyediakan arsip akhir Project yang mudah ditemukan.

**Fitur/aktivitas:**

- Pengelompokan dokumen final.
- Paket arsip Project.
- Kebijakan retensi file.
- Hak akses arsip.
- Pencarian dokumen berdasarkan Project dan Client.

**Output:** Seluruh artefak akhir Project tersimpan terstruktur.

**Dependency:** Phase 26.

**File terkait:** `04-database.md`, `05-api.md`, `12-development-rules.md`, `database/documents.md`, `database/projects.md`, `ui/klien.md`; area source `src/modules/documents/`, `src/modules/projects/`, dan service storage.

---

# Milestone F — Kontrol, Monitoring, dan Produktivitas

## Phase 28 — Activity Log dan Audit Trail

**Target:** Mencatat aktivitas penting dan perubahan data secara menyeluruh.

**Fitur/aktivitas:**

- Actor, waktu, action, dan subject.
- Before/after untuk perubahan kritis.
- Filter berdasarkan User, Project, modul, dan waktu.
- Proteksi log dari perubahan pengguna biasa.
- Audit trail untuk status, assignment, pembayaran, dan approval.

**Output:** Aktivitas sistem dapat ditelusuri untuk operasional dan audit.

**Dependency:** Phase 10; diterapkan bertahap ke semua phase berikutnya.

**File terkait:** `03-role-permission.md`, `04-database.md`, `05-api.md`, `12-development-rules.md`, `database/logs.md`; area source modul activity log, audit service, dan logging middleware.

---

## Phase 29 — Notification Center

**Target:** Memberi tahu pengguna mengenai tugas dan event penting.

**Fitur/aktivitas:**

- In-app notification sesuai `08-notification.md`.
- Notification dropdown dan halaman semua notifikasi.
- Read, unread, mark as read, dan deep link.
- Trigger berbasis event workflow.
- Preferensi notifikasi yang diperbolehkan.

**Output:** Pengguna menerima notifikasi relevan pada waktu yang tepat.

**Dependency:** Phase 14 dan event dari modul terkait.

**File terkait:** `05-api.md`, `06-routing.md`, `08-notification.md`, `database/notifications.md`, `ui/dashboard.md`; area source `src/modules/notifications/`, `src/services/notification.ts`, dan notification components.

---

## Phase 30 — Dashboard Personal dan Workload

**Target:** Memberi Klien portal read-only, menyediakan workload, dan membuat beranda administrasi khusus Super Admin.

**Fitur/aktivitas:**

- My Tasks.
- Tugas terlambat dan mendekati due date.
- Ringkasan Project per status.
- Workload per User.
- Quick action sesuai role.
- Dashboard Klien sebagai satu-satunya protected route untuk role Klien.
- Detail Client, progress Project, dokumen publik, Invoice, Payment, Sertifikat, Timeline publik, dan notifikasi milik Klien.
- Satu Project per Client tanpa selector Project atau ID Project.
- Ownership scope berdasarkan `users.client_id`.
- Dashboard Administrasi Sistem khusus Super Admin: User, permission, queue, scheduler, storage, backup, health, Activity Log, dan versi runtime.

**Output:** Klien dapat memantau seluruh data miliknya dan Super Admin dapat memantau kesehatan sistem dari `/dashboard`.

**Dependency:** Phase 27, Phase 28, dan Phase 29.

**File terkait:** `03-role-permission.md`, `05-api.md`, `06-routing.md`, `09-ui-ux.md`, `database/users.md`, `database/clients.md`, `database/projects.md`, `database/documents.md`, `database/logs.md`, `ui/dashboard.md`, `ui/tugas.md`; area source halaman dashboard, modul tugas, Client Dashboard query/service, policy ownership, dan dashboard components.

---

## Phase 31 — Dashboard Operasional

**Target:** Memberi seluruh staf internal selain Super Admin satu Dashboard Progres Operasional lintas divisi.

**Fitur/aktivitas:**

- Project per status dan tahap.
- KPI Total Klien, Proses Entry, Menunggu Audit, Sertifikat Terbit, Audit 7 Hari, Proses Revisi, Perlu Follow Up, dan Kritis lebih dari tujuh hari.
- Status dan progress terpisah untuk Entry, Pendamping, dan Auditor.
- Dropdown status manual sesuai role; persentase otomatis dari mapping resmi.
- Ringkasan tahap dan Panduan Cepat.
- Bar chart distribusi tahap sertifikasi dan donut chart kondisi pembaruan data.
- Bottleneck per divisi.
- Aging Project.
- Drill-down ke daftar Project.

**Output:** Seluruh staf internal non–Super Admin melihat kondisi progres organisasi yang konsisten; perubahan status dapat diaudit dan angka progress tidak dapat menjadi nilai liar.

**Dependency:** Phase 22 dan Phase 28.

**File terkait:** `02-workflow.md`, `03-role-permission.md`, `07-status.md`, `09-ui-ux.md`, `database/projects.md`, `database/logs.md`, `ui/dashboard.md`; area source halaman dashboard dan modul reporting operasional.

---

## Phase 32 — Dashboard Finance

**Target:** Menyediakan visibilitas terhadap Invoice dan pembayaran.

**Fitur/aktivitas:**

- Invoice diterbitkan, jatuh tempo, sebagian, dan lunas.
- Pemisahan Invoice Client dan Invoice Partner.
- Transaction count berdasarkan Project unik.
- Nominal Client, Nominal Mitra, dan selisih tanpa discount.
- Pembayaran menunggu verifikasi.
- Outstanding dan aging receivable.
- Ringkasan pendapatan per periode.
- Drill-down ke Invoice dan Project.

**Output:** Finance dapat memonitor arus tagihan serta pembayaran.

**Dependency:** Phase 25 dan Phase 28.

**File terkait:** `03-role-permission.md`, `07-status.md`, `workflow/finance.md`, `database/invoices.md`, `database/payments.md`, `ui/dashboard.md`, `ui/invoice.md`, `ui/pembayaran.md`; area source halaman dashboard dan modul reporting Finance.

---

## Phase 33 — Dashboard Direksi dan Management Reporting

**Target:** Menyediakan ringkasan eksekutif untuk pengambilan keputusan.

**Fitur/aktivitas:**

- KPI Lead, Project, operasional, dan keuangan.
- Conversion rate.
- Cycle time dan completion rate.
- Tren periodik.
- Filter periode, layanan, Marketing, dan status.

**Output:** Direksi memperoleh ringkasan performa bisnis yang konsisten.

**Dependency:** Phase 31 dan Phase 32.

**File terkait:** `01-business.md`, `03-role-permission.md`, `09-ui-ux.md`, `database/clients.md`, `database/projects.md`, `database/invoices.md`, `database/payments.md`, `ui/dashboard.md`, `ui/laporan.md`; area source `src/modules/reports/` dan dashboard Direksi.

---

## Phase 34 — Reporting dan Export

**Target:** Menyediakan laporan operasional yang dapat didistribusikan.

**Fitur/aktivitas:**

- Laporan Lead, Project, workflow, Invoice, dan pembayaran.
- Filter Tipe Client serta Partner.
- Pasangan Invoice Mitra ditampilkan sebagai satu transaction group.
- Export Excel.
- Export PDF.
- Filter dan kolom laporan.
- Pencatatan waktu serta pembuat export.

**Output:** Data utama dapat diekspor sesuai hak akses.

**Dependency:** Phase 31, Phase 32, dan Phase 33.

**File terkait:** `03-role-permission.md`, `05-api.md`, `09-ui-ux.md`, `ui/laporan.md`, serta seluruh file `database/*.md` sesuai jenis laporan; area source `src/modules/reports/`, `src/utils/download.ts`, dan export service.

---

## Phase 35 — Search dan Productivity Tools

**Target:** Mempercepat pekerjaan rutin pengguna.

**Fitur/aktivitas:**

- Global Search.
- Advanced filter dan saved filter.
- Sorting serta configurable columns.
- Context action yang aman tanpa Bulk Action.
- Shortcut keyboard dan recent items.

**Output:** Waktu pencarian dan pengelolaan data berulang berkurang.

**Dependency:** Modul utama telah stabil.

**File terkait:** `05-api.md`, `06-routing.md`, `09-ui-ux.md`, `10-design-system.md`, `ui/leads.md`, `ui/klien.md`, `ui/invoice.md`, `ui/tugas.md`; area source shared table, search, filter, pagination, dan shortcut components.

---

## Phase 36 — SLA, Reminder, dan Escalation

**Target:** Mengurangi keterlambatan proses operasional.

**Fitur/aktivitas:**

- Definisi SLA per tahap.
- Due date otomatis.
- Reminder tugas.
- Escalation untuk pekerjaan terlambat.
- Laporan kepatuhan SLA.

**Output:** Keterlambatan dapat dideteksi dan ditindaklanjuti lebih awal.

**Dependency:** Phase 29, Phase 30, dan Phase 31.

**File terkait:** `02-workflow.md`, `07-status.md`, `08-notification.md`, `database/notifications.md`, `database/projects.md`, `ui/dashboard.md`, `ui/tugas.md`; area source `src/modules/workflow/`, `src/modules/notifications/`, scheduled jobs, dan SLA service.

---

# Milestone G — Interoperabilitas dan Otomasi

## Phase 37 — Template, Numbering, dan Automation

**Target:** Mengurangi input manual dan menjaga konsistensi dokumen.

**Fitur/aktivitas:**

- Template dokumen.
- Auto-generate ID Client dan nomor Invoice.
- Rule nilai default.
- Scheduled job untuk reminder dan maintenance.
- Idempotency untuk proses otomatis.

**Output:** Proses administratif berulang berjalan lebih cepat dan konsisten.

**Dependency:** Modul yang akan diotomasi telah stabil.

**File terkait:** `04-database.md`, `05-api.md`, `11-folder-structure.md`, `12-development-rules.md`, `database/projects.md`, `database/invoices.md`; area source `src/modules/settings/`, template service, numbering service, dan scheduled jobs.

---

## Phase 38 — Import, Export Data, dan Public API

**Target:** Menyediakan pertukaran data yang aman serta terkontrol.

**Fitur/aktivitas:**

- Import dengan template, preview, validasi, dan error report.
- Export terstruktur.
- REST API berversi.
- API authentication, authorization, rate limit, dan audit log.
- Dokumentasi kontrak API.

**Output:** Data dapat dipertukarkan tanpa merusak integritas sistem.

**Dependency:** Phase 34, Phase 35, dan data model stabil.

**File terkait:** `04-database.md`, `05-api.md`, `06-routing.md`, `11-folder-structure.md`, `12-development-rules.md`, serta seluruh file `database/*.md` yang datanya dibuka; area source module API, import service, export service, dan API middleware.

---

## Phase 39 — Webhook dan Integrasi Eksternal

**Target:** Menghubungkan PHC System dengan layanan eksternal yang disetujui.

**Fitur/aktivitas:**

- Webhook dengan signature, retry, dan delivery log.
- Integrasi BPJPH jika API resmi tersedia.
- Integrasi Payment Gateway jika disetujui.
- Mapping data dan penanganan kegagalan.
- Monitoring status integrasi.

**Output:** Integrasi eksternal berjalan aman, dapat diulang, dan dapat diaudit.

**Dependency:** Phase 38 dan ketersediaan API pihak ketiga.

**File terkait:** `02-workflow.md`, `05-api.md`, `07-status.md`, `12-development-rules.md`, `workflow/sertifikat.md`, `workflow/pembayaran.md`; area source integration module, webhook service, queue jobs, dan delivery log.

---

# Milestone H — Quality, Release, dan Scale

## Phase 40 — Security Hardening, QA, Release, dan Optimization

**Target:** Menjadikan sistem layak produksi, stabil, terpantau, dan siap ditingkatkan.

**Fitur/aktivitas:**

- Security review, dependency audit, secret management, dan backup-restore test.
- Unit, feature, integration, authorization, dan end-to-end test.
- Performance test untuk halaman serta query kritis.
- UAT lintas role dan perbaikan defect.
- CI/CD, migration strategy, rollback plan, health check, queue monitor, serta log viewer.
- Production release, hypercare, evaluasi KPI, dan backlog optimization.

**Output:** PHC System dirilis ke production dengan bukti pengujian, monitoring, dan prosedur pemulihan.

**Dependency:** Seluruh phase yang masuk scope release.

**File terkait:** `03-role-permission.md`, `04-database.md`, `05-api.md`, `06-routing.md`, `11-folder-structure.md`, `12-development-rules.md`, `13-roadmap.md`; area source seluruh module dalam scope release, `__tests__/`, konfigurasi CI/CD, environment, monitoring, backup, dan deployment.

---

# Ringkasan Urutan Prioritas

| Milestone | Phase | Fokus | Exit Milestone |
|---|---:|---|---|
| A | 1–6 | Fondasi Produk | Sistem dasar, UI, autentikasi, RBAC, dan User Management siap |
| B | 7–10 | Akuisisi dan Data Utama | Lead dapat menjadi Project dan dikelola dalam Project Workspace |
| C | 11–14 | Aktivasi dan Keuangan Awal | Project berbayar dapat aktif dan ditugaskan |
| D | 15–22 | Workflow Operasional Paralel | Workflow A dan B selesai serta tersinkronisasi |
| E | 23–27 | Sertifikat, Pelunasan, dan Penutupan | Sertifikat, pelunasan, penutupan, dan arsip selesai |
| F | 28–36 | Kontrol, Monitoring, dan Produktivitas | Aktivitas, notifikasi, dashboard, laporan, dan SLA tersedia |
| G | 37–39 | Interoperabilitas dan Otomasi | Otomasi, API, import/export, dan integrasi tersedia |
| H | 40 | Quality, Release, dan Scale | Sistem tervalidasi dan siap production |

---

# Daftar Dependency

Dependency pada roadmap dibagi menjadi dua jenis:

1. **Dependency antar-phase** — phase atau kondisi yang harus tersedia sebelum phase berikutnya dapat dimulai.
2. **Dependency teknis** — platform, service, atau package yang diperlukan untuk mengimplementasikan fitur.

## Dependency Antar-Phase

| Phase | Dependency Wajib | Alasan |
|---:|---|---|
| 1 | Tidak ada | Titik awal penetapan scope produk |
| 2 | Phase 1 | Arsitektur mengikuti scope yang telah disetujui |
| 3 | Phase 2 | Design System dibangun pada application foundation yang tersedia |
| 4 | Phase 2 dan 3 | Authentication memerlukan environment dan UI dasar |
| 5 | Phase 4 | Authorization diterapkan setelah identitas User tersedia |
| 6 | Phase 5 | Pengelolaan User memerlukan role dan permission |
| 7 | Phase 5 dan 6 | Master Client memerlukan administrator serta kontrol akses |
| 8 | Phase 7 | Lead menggunakan referensi data Client |
| 9 | Phase 8 | Hanya Lead Deal yang dapat dikonversi |
| 10 | Phase 9 | Project Workspace memerlukan Project hasil konversi |
| 11 | Phase 9 dan 10 | Invoice Aktivasi harus terhubung ke Project |
| 12 | Phase 11 | Pembayaran harus direkonsiliasi terhadap Invoice |
| 13 | Phase 12 | Aktivasi hanya terjadi setelah pembayaran valid |
| 14 | Phase 13 | Assignment hanya dilakukan pada Project aktif |
| 15 | Phase 14 | Administrasi dokumen memerlukan penanggung jawab |
| 16 | Phase 15 | Persiapan Entry memerlukan dokumen lengkap |
| 17 | Phase 16 | Entry dimulai setelah data dan akun SIHALAL siap |
| 18 | Phase 17 | SPV hanya mereview hasil yang telah disubmit Entry |
| 19 | Phase 14 | Perencanaan Audit memerlukan Project aktif dan assignment |
| 20 | Phase 19 | Pelaksanaan Audit memerlukan jadwal dan personel |
| 21 | Phase 20 | Auditor mereview hasil pelaksanaan Audit |
| 22 | Phase 18 dan 21 | Sinkronisasi memerlukan Workflow A dan B selesai |
| 23 | Phase 22 | Invoice Negara diproses setelah workflow paralel selesai |
| 24 | Phase 23 | Sertifikat diproses setelah tahap Invoice Negara |
| 25 | Phase 12 dan 24 | Pelunasan menggunakan mesin pembayaran dan data Sertifikat |
| 26 | Phase 25 | Project ditutup setelah kewajiban pembayaran selesai |
| 27 | Phase 26 | Arsip final dibuat setelah Project ditutup |
| 28 | Phase 10 | Audit trail memerlukan entity Project; diterapkan bertahap pada phase selanjutnya |
| 29 | Phase 14 dan event modul terkait | Notifikasi memerlukan penerima, assignment, serta event workflow |
| 30 | Phase 27, 28, dan 29 | Dashboard personal dan Client Dashboard menggunakan data Project final, log, serta notifikasi |
| 31 | Phase 22 dan 28 | Dashboard operasional menggunakan data workflow serta log |
| 32 | Phase 25 dan 28 | Dashboard Finance menggunakan Invoice, Payment, serta log |
| 33 | Phase 31 dan 32 | Dashboard Direksi menggabungkan KPI operasional dan Finance |
| 34 | Phase 31, 32, dan 33 | Reporting menggunakan dataset dashboard yang telah tervalidasi |
| 35 | Modul utama yang diberi search/filter telah stabil | Productivity tools tidak boleh mengubah kontrak modul yang masih bergerak |
| 36 | Phase 29, 30, dan 31 | SLA menggunakan notification, work queue, dan data operasional |
| 37 | Modul yang akan diotomasi telah stabil | Automation bergantung pada business rule final |
| 38 | Phase 34, 35, dan data model stabil | Import/API memerlukan kontrak data dan validasi yang stabil |
| 39 | Phase 38 dan API pihak ketiga tersedia | Webhook serta integrasi memerlukan Public API dan kontrak eksternal |
| 40 | Seluruh phase dalam scope release | QA dan release memvalidasi satu paket release lengkap |

## Dependency Teknis

| Dependency | Digunakan Pada | Phase Utama | Keterangan |
|---|---|---|---|
| PHP 8.4.23 | Seluruh runtime, CLI, queue, scheduler, test, dan deployment | 2–40 | Runtime wajib tepat PHP 8.4.23 |
| Laravel 13.8.0 | Application framework | 2–40 | Framework wajib tepat Laravel 13.8.0 dan dikunci oleh `composer.lock` |
| Database | Seluruh data transaksional dan konfigurasi | 2, 6–40 | Schema wajib mengacu pada `04-database.md` dan `database/*.md` |
| Cache | Session, permission, query berat, dan dashboard | 2, 4–6, 30–40 | Strategi invalidation harus ditetapkan sebelum digunakan |
| Queue | Notifikasi, export, reminder, webhook, dan pekerjaan berat | 2, 29, 34, 36–40 | Job harus idempotent, memiliki retry, timeout, dan failure log |
| File Storage | Bukti bayar, dokumen Project, Sertifikat, dan arsip | 2, 12, 15, 20, 23–27 | Wajib memiliki validation, authorization, backup, dan retention rule |
| Mail Service | Reset password serta komunikasi sistem yang disetujui | 2 dan 4 | Notifikasi operasional utama tetap mengikuti `08-notification.md` |
| Scheduler | Reminder, SLA, maintenance, dan automation | 2, 36, 37, dan 40 | Scheduler membutuhkan monitoring serta pencegahan proses ganda |
| Realtime Service | Notification Center dan dashboard live | 29–33 | Gunakan hanya jika kebutuhan realtime telah disetujui |
| Filament 5.x | Application/admin foundation | 2–6 | Wajib menggunakan API dan generator Filament 5; versi sebelumnya dilarang |
| Livewire 4.x | Reactive UI Filament 5 dan komponen aplikasi | 3–40 | Major dikunci melalui `^4.0` |
| Tailwind CSS 4.1+ | Styling dan token UI | 3–40 | Wajib tetap pada major 4 dan dikunci oleh lockfile frontend |
| Shield | Permission integration pada Filament 5 | 5 dan 6 | Melengkapi policy; bukan pengganti business authorization |
| Breezy | Authentication dan profile flow | 4 dan 6 | Digunakan jika sesuai kebutuhan autentikasi final |
| Spatie Permission | Role dan permission | 5, 6, dan seluruh protected module | Role tetap mengacu pada `03-role-permission.md` |
| Spatie Media Library | Upload dan pengelolaan media | 12, 15, 20, 23, 24, dan 27 | Digunakan untuk file yang membutuhkan collection dan metadata |
| Spatie Activity Log | Activity log aplikasi | 9–40, terutama 28 | Event penting harus memiliki actor, subject, dan timestamp |
| Spatie Settings | System settings | 6 dan 37 | Nilai konfigurasi tidak boleh di-hardcode |
| Model States | State machine Lead, Project, dan modul | 8–26 dan 36 | State serta transisi tetap mengacu pada `07-status.md` |
| Queue Monitor | Monitoring background job | 29, 34, 36–40 | Memantau retry, failure, durasi, dan throughput job |
| Health | Application health check | 2 dan 40 | Mencakup service kritis yang dibutuhkan production |
| Log Viewer | Pemeriksaan application log | 2, 28, 39, dan 40 | Akses log dibatasi untuk role berwenang |
| Laravel Reverb | Realtime notification/dashboard | 29–33 | Dipasang hanya jika realtime diaktifkan |
| DomPDF | Dokumen dan laporan PDF | 11, 23, 24, dan 34 | Template output wajib melalui visual QA |
| Laravel Excel | Import dan export spreadsheet | 34 dan 38 | Import wajib memiliki preview, validation, dan error report |
| Apex Charts | Visualisasi dashboard | 30–33 dan 36 | Data agregat harus sama dengan sumber laporan |
| FullCalendar | Jadwal Audit dan calendar view | 19 dan Future Enhancement | Penggunaan lanjutan tetap mengikuti prioritas roadmap |

Dependency teknis pada tabel ini adalah kandidat yang telah tercatat dalam roadmap. Package hanya boleh dipasang ketika phase terkait berstatus **Ready**, kebutuhannya telah terkonfirmasi, dan evaluasi keamanan serta lisensinya telah selesai.

---

# Dependency Kritis

Alur dependency utama:

`Lead → Project → Invoice Aktivasi → Verifikasi Pembayaran → Project Aktif → Assignment`

Setelah Assignment, pekerjaan berjalan paralel:

- `Dokumen → Entry → Review SPV Entry → Workflow A Selesai`
- `Perencanaan Audit → Pendampingan Audit → Review Auditor → Workflow B Selesai`

Kedua jalur kemudian bergabung:

`Workflow A + Workflow B → Invoice Negara → Sertifikat → Pelunasan → Project Selesai`

---

# Release Strategy

Roadmap dapat dikemas ke beberapa release tanpa mengubah nomor phase.

| Release | Cakupan Minimum | Tujuan |
|---|---|---|
| Internal Alpha | Phase 1–14 dan Phase 28 dasar | Validasi fondasi, Lead, aktivasi, dan assignment |
| Operational Beta | Phase 15–29 | Uji workflow end-to-end dengan pengguna internal |
| MVP Production | Phase 1–34 dan Phase 40 | Menjalankan proses bisnis utama di production |
| Productivity Release | Phase 35–37 | Meningkatkan kecepatan serta kontrol operasional |
| Integration Release | Phase 38–39 | Menghubungkan sistem dengan sumber eksternal |

Isi release dapat berubah berdasarkan hasil evaluasi dan persetujuan Project Owner.

---

# Future Enhancement

Fitur berikut berada di backlog setelah kebutuhan inti stabil dan tidak otomatis menjadi bagian MVP:

- Progressive Web App (PWA).
- Mobile Application.
- Multi Company.
- Multi Branch.
- Multi Language.
- Dark Mode.
- AI Assistant.
- OCR Dokumen.
- Digital Signature.
- Calendar View lanjutan.
- Advanced Data Warehouse dan Business Intelligence.

Setiap Future Enhancement harus dibuat menjadi phase baru apabila telah disetujui.

---

# Breaking Change

Perubahan besar harus mempertimbangkan dampaknya terhadap:

- Database dan data migration.
- Workflow serta status.
- Role dan permission.
- API dan integrasi.
- UI dan pengalaman pengguna.
- Laporan.
- Dokumentasi.

Breaking Change wajib memiliki impact analysis, migration plan, backward compatibility plan, test plan, rollback plan, dan persetujuan Project Owner.

---

# Evaluasi Roadmap

Roadmap dievaluasi pada akhir setiap milestone berdasarkan:

- Kebutuhan dan dampak bisnis.
- Feedback pengguna.
- Perubahan regulasi.
- Ketersediaan resource.
- Risiko keamanan dan teknis.
- Hasil implementasi serta metrik penggunaan.
- Dependency pihak ketiga.

Hasil evaluasi dapat mengubah scope atau jadwal, tetapi perubahan nomor dan dependency phase harus tetap terdokumentasi.

---

# Definition of Ready

Sebuah phase dapat berstatus **Ready** apabila:

- Target dan scope telah disetujui.
- Acceptance criteria tersedia.
- Dependency wajib telah selesai atau tersedia.
- Desain UI/UX tersedia jika diperlukan.
- Dampak database, API, role, status, dan notifikasi telah dianalisis.
- Risiko serta test scenario utama telah diidentifikasi.

---

# Definition of Done

Sebuah phase dianggap **Completed** apabila:

- Seluruh scope dan acceptance criteria telah terpenuhi.
- Authorization dan validation telah diterapkan.
- Unit/feature test yang relevan lulus.
- Integration atau end-to-end test lulus jika diperlukan.
- Tidak terdapat bug Critical atau High yang belum diterima risikonya.
- Activity log, notification, dan monitoring diterapkan jika relevan.
- Dokumentasi sumber resmi diperbarui.
- Perubahan telah direview dan didemonstrasikan.
- Disetujui oleh Project Owner.

---

# Third Party Packages

Package dipilih berdasarkan kebutuhan phase dan wajib melalui evaluasi keamanan, lisensi, kompatibilitas, serta maintenance.

| Kategori | Package |
|---|---|
| Authentication | Filament 5, Shield, Breezy |
| Authorization | Spatie Permission |
| Media | Spatie Media Library |
| Activity | Spatie Activity Log |
| Audit | Spatie Activity Log |
| Settings | Spatie Settings |
| State | Model States |
| Monitoring | Queue Monitor, Health, Log Viewer |
| Realtime | Laravel Reverb |
| Reporting | DomPDF, Laravel Excel |
| UI | Apex Charts, FullCalendar |

Package bukan scope otomatis. Package hanya dipasang ketika phase terkait membutuhkannya.

---

# Catatan

Roadmap merupakan dokumen yang terus berkembang.

Penambahan, pengurangan, perubahan urutan, atau perubahan prioritas harus dicatat pada dokumen ini agar Project Owner, developer, dan seluruh pengguna memiliki acuan yang sama.
