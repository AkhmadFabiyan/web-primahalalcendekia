# 10. Design System

## Tujuan

Dokumen ini mendefinisikan Design System yang digunakan pada PHC System.

Tujuan utama Design System adalah menjaga konsistensi tampilan, meningkatkan efisiensi pengembangan, dan memudahkan pemeliharaan antarmuka.

Seluruh halaman pada PHC System wajib menggunakan komponen yang didefinisikan pada dokumen ini.

---

# Referensi

Dokumen terkait:

- 09-ui-ux.md
- 14-brand-guideline.md

Nilai brand dasar seperti warna, font, radius, dan shadow bersumber dari `14-brand-guideline.md`. Dokumen ini menerapkan nilai tersebut pada komponen UI.

UI Detail:

- ui/dashboard.md
- ui/leads.md
- ui/klien.md
- ui/invoice.md
- ui/pembayaran.md
- ui/tugas.md
- ui/laporan.md
- ui/settings.md

---

# Prinsip Design

Design System mengikuti prinsip berikut.

- Consistent
- Simple
- Readable
- Reusable
- Accessible

---

# Filosofi

PHC System merupakan aplikasi operasional.

Prioritas utama:

- Kejelasan informasi
- Kecepatan bekerja
- Konsistensi
- Kemudahan membaca data

Visual dekoratif bukan prioritas.

---

# Grid System

Gunakan Layout 12 Column.

Content maksimal:

```
1440px
```

Container mengikuti ukuran layar.

---

# Spacing

Gunakan skala spacing.

| Token | Ukuran |
|---------|---------|
| xs | 4px |
| sm | 8px |
| md | 16px |
| lg | 24px |
| xl | 32px |
| 2xl | 48px |
| 3xl | 64px |

Jangan menggunakan nilai spacing acak.

---

# Border Radius

Gunakan radius yang konsisten.

| Token | Ukuran |
|---------|---------|
| sm | 4px |
| md | 8px |
| lg | 12px |
| xl | 16px |

---

# Shadow

Gunakan maksimal tiga tingkat Shadow.

| Level | Penggunaan |
|---------|------------|
| Small | Card |
| Medium | Dropdown |
| Large | Modal |

---

# Typography

Gunakan maksimal tiga ukuran Heading.

| Style | Penggunaan |
|---------|------------|
| H1 | Halaman |
| H2 | Section |
| H3 | Card |

Body:

- Body Large
- Body
- Small

---

# Font Weight

| Weight | Penggunaan |
|----------|------------|
| Regular | Isi |
| Medium | Label |
| Semibold | Subjudul |
| Bold | Judul |

---

# Color Palette

## Primary

Digunakan untuk:

- Button Primary
- Link
- Active State

---

## Success

Digunakan untuk:

- Berhasil
- Approved
- Lunas

---

## Warning

Digunakan untuk:

- Menunggu
- Review
- Proses

---

## Danger

Digunakan untuk:

- Error
- Revisi
- Delete

---

## Neutral

Digunakan untuk:

- Background
- Border
- Text
- Divider

---

# Icon

Gunakan satu library icon.

Contoh:

- Lucide
- Heroicons

Jangan mencampur beberapa library icon.

---

# Logo

Logo hanya digunakan pada:

- Login
- Sidebar
- Header

Logo tidak boleh dimodifikasi.

---

# Button

Jenis Button.

## Primary

Aksi utama.

Contoh:

- Simpan
- Terbitkan Invoice

---

## Secondary

Aksi tambahan.

---

## Outline

Aksi alternatif.

---

## Ghost

Aksi ringan.

---

## Danger

Aksi berbahaya.

Contoh:

Delete

Cancel

---

# Input

Seluruh Input memiliki:

- Label
- Placeholder
- Helper Text (opsional)
- Error Message

---

# Select

Gunakan Searchable Select apabila data lebih dari 20 item.

---

# Checkbox

Digunakan untuk Multi Selection.

---

# Radio

Digunakan untuk pilihan tunggal.

---

# Switch

Digunakan untuk nilai:

Aktif

atau

Nonaktif

---

# Badge

Badge digunakan untuk Status.

Contoh:

Aktif

Review

Revisi

Lunas

Referensi:

07-status.md

---

# Alert

Jenis Alert.

- Success
- Warning
- Error
- Info

---

# Toast

Toast digunakan untuk Feedback.

Contoh:

```
Data berhasil disimpan.
```

---

# Card

Card digunakan untuk:

- Statistik
- Ringkasan
- Dashboard

## Dashboard Progres Operasional

Dashboard internal non–Super Admin mengikuti hierarki visual referensi:

- header Primary Dark
- KPI Total Klien menggunakan Primary
- Proses Entry menggunakan Gold
- Menunggu Audit menggunakan Primary Light
- Sertifikat Terbit menggunakan Success
- Audit 7 Hari menggunakan Info
- Proses Revisi menggunakan Gold Dark
- Perlu Follow Up menggunakan Warning
- Kritis menggunakan Danger

Gunakan token warna resmi dari `14-brand-guideline.md`, bukan hex baru pada komponen. Semua angka KPI wajib memiliki label teks. Chart tidak boleh membedakan kategori hanya dengan warna.

Dropdown status menampilkan label, progress otomatis, dan konfirmasi saat nilai turun. Pengguna tanpa permission melihat badge, bukan kontrol disabled yang mengisyaratkan action tersembunyi.

---

# Table

Table merupakan komponen utama aplikasi.

Mendukung:

- Search
- Filter
- Sort
- Pagination
- Loading
- Empty State

---

# Drawer

Drawer digunakan untuk:

- Detail Data
- Timeline
- Workflow
- Activity Log

Drawer lebih diprioritaskan dibanding halaman detail.

---

# Modal

Modal digunakan untuk:

- Konfirmasi
- Approval
- Revisi
- Upload
- Form Singkat

---

# Tabs

Tabs digunakan untuk mengelompokkan informasi.

Contoh:

Informasi

Dokumen

Pembayaran

Timeline

Activity Log

---

# Breadcrumb

Gunakan pada seluruh halaman kecuali Login.

---

# Notification

Gunakan Bell Icon.

Badge menunjukkan jumlah notifikasi yang belum dibaca.

Referensi:

08-notification.md

---

# Loading

Gunakan:

- Skeleton
- Spinner

Hindari halaman kosong saat Loading.

---

# Empty State

Gunakan:

- Icon
- Judul
- Deskripsi
- Tombol (opsional)

---

# Error State

Tampilkan:

- Pesan Error
- Tombol Coba Lagi

---

# Responsive

Prioritas:

1. Desktop
2. Tablet
3. Mobile

Desktop merupakan target utama aplikasi.

---

# Accessibility

Seluruh komponen wajib:

- Mendukung Keyboard Navigation
- Memiliki Focus State
- Memiliki Contrast yang baik
- Memiliki Label yang jelas

---

# Penamaan Komponen

Gunakan PascalCase.

Contoh:

Button

DataTable

WorkflowCard

NotificationDropdown

ProjectDrawer

InvoiceCard

---

# Reusability

Komponen harus dapat digunakan kembali.

Hindari membuat komponen baru apabila fungsi yang sama sudah tersedia.

---

# Konsistensi

Semua halaman wajib menggunakan Design System ini.

Tidak diperbolehkan membuat variasi komponen tanpa alasan yang jelas.

Perubahan Design System harus diperbarui pada dokumen ini terlebih dahulu.
