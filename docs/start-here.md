# 🚀 START HERE - PHC System Development Guide

> **WAJIB dibaca sebelum membaca file dokumentasi lainnya.**

Dokumen ini menjadi panduan awal untuk Developer, AI Assistant, maupun Contributor agar memahami cara membaca dokumentasi Prima Halal Cendekia System.

---

# Tujuan Dokumentasi

Dokumentasi ini dibuat sebagai **Single Source of Truth** untuk seluruh pengembangan PHC System.

Seluruh keputusan mengenai:

- Business Process
- Database
- Workflow
- UI
- API
- Status
- Permission
- Development Rules

harus mengacu pada dokumentasi ini.

Apabila implementasi berbeda dengan dokumentasi, maka dokumentasi harus diperbarui terlebih dahulu.

---

# Prinsip Utama

## 1. Documentation First

Seluruh fitur baru harus dimulai dari dokumentasi.

Bukan langsung membuat kode.

Urutan yang benar:

Requirement

↓

Dokumentasi

↓

Review

↓

Implementasi

↓

Testing

↓

Deployment

---

## 2. Single Source of Truth

Tidak boleh ada penjelasan yang sama di dua file berbeda.

Contoh:

Status hanya dijelaskan pada:

```
07-status.md
```

Role hanya dijelaskan pada:

```
03-role-permission.md
```

Workflow hanya dijelaskan pada folder:

```
workflow/
```

Database hanya dijelaskan pada folder:

```
database/
```

Peta sumber kebenaran:

| Konsep | Sumber Resmi |
|---|---|
| Istilah bisnis dan penamaan | `00-glossary.md` |
| Aturan bisnis | `01-business.md` |
| Alur lintas modul | `02-workflow.md` |
| Alur operasional rinci | `workflow/` |
| Role dan permission | `03-role-permission.md` |
| Model dan relasi data | `04-database.md` dan `database/` |
| Kontrak endpoint | `05-api.md` |
| URL aplikasi | `06-routing.md` |
| Status, kode enum, dan label UI | `07-status.md` |
| Event dan penerima notifikasi | `08-notification.md` |
| Pola UI/UX | `09-ui-ux.md` dan `ui/` |
| Komponen UI | `10-design-system.md` |
| Token visual dan identitas brand | `14-brand-guideline.md` |

Jika dokumen turunan memerlukan detail yang sama, dokumen tersebut harus merujuk ke sumber resmi dan tidak membuat nilai baru.

---

## 3. Modular

Setiap modul berdiri sendiri.

Misalnya:

Lead

Client

Project

Payment (termasuk Invoice)

Workflow

Notification

Masing-masing memiliki dokumentasi sendiri.

Invoice bukan modul terpisah. Invoice merupakan fitur dan resource di dalam modul Payment.

---

# Cara Membaca Dokumentasi

Urutan membaca yang disarankan:

```
start-here.md
        │
        ▼
00-overview.md
        │
        ▼
00-glossary.md
        │
        ▼
01-business.md
        │
        ▼
02-workflow.md
        │
        ▼
03-role-permission.md
        │
        ▼
04-database.md
        │
        ▼
05-api.md
        │
        ▼
06-routing.md
        │
        ▼
07-status.md
        │
        ▼
08-notification.md
        │
        ▼
09-ui-ux.md
        │
        ▼
10-design-system.md
        │
        ▼
11-folder-structure.md
        │
        ▼
12-development-rules.md
        │
        ▼
13-roadmap.md
        │
        ▼
14-brand-guideline.md
        │
        ▼
Folder workflow/
        │
        ▼
Folder database/
        │
        ▼
Folder ui/
```

---

# Struktur Dokumentasi

```
docs/

Root
│
├── Overview
├── Glossary
├── Business
├── Workflow
├── Database
├── API
├── Routing
├── Status
├── Notification
├── UI
├── Design System
├── Folder Structure
├── Development Rules
├── Roadmap
└── Brand Guideline

workflow/

database/

ui/

assets/
```

---

# Arsitektur Sistem

PHC menggunakan arsitektur berikut:

Lead

↓

Deal

↓

Klien

↓

Project

↓

Workflow Operasional

↓

Pembayaran

(Invoice → Payment)

↓

Sertifikat

↓

Selesai

Project menjadi pusat seluruh sistem.

Bukan Client.

Bukan Lead.

Khusus Role Klien, seluruh data miliknya ditampilkan secara read-only melalui `/dashboard`. Role Klien tidak mengakses route operasional internal.

---

# Tech Stack

Backend

- Laravel 13.8.0
- PHP 8.4.23

Admin Panel

- Filament 5.x

Frontend

- Livewire 4.x
- Volt
- Tailwind CSS 4.1+
- Alpine.js
- shadcn/ui

Realtime

- Laravel Reverb

Database

- MySQL

Cache

- Redis

Queue

- Redis Queue

---

# Standard Libraries

Library berikut merupakan standar project.

## Core

- filament/filament `^5.0`
- livewire/livewire `^4.0`

## Permission

- spatie/laravel-permission
- bezhansalleh/filament-shield

## Activity

- spatie/laravel-activitylog

## Media

- spatie/laravel-medialibrary
- spatie/image

## Settings

- spatie/laravel-settings

## State Machine

- spatie/laravel-model-states

## Cache

- spatie/laravel-responsecache

## Queue

- romanzipp/laravel-queue-monitor

## Monitoring

- spatie/laravel-health

## Backup

- spatie/laravel-backup

## Export

- maatwebsite/excel

## PDF

- barryvdh/laravel-dompdf

## Slug

- spatie/laravel-sluggable

## Tags

- spatie/laravel-tags

## Sitemap

- spatie/laravel-sitemap

## Realtime

- Laravel Reverb

## Log

- opcodesio/log-viewer

## Icons

- Heroicons
- Lucide

## UI

- shadcn/ui

## Filament Plugins

Minimal menggunakan:

- Filament Shield
- Filament Apex Charts

Opsional:

- Filament Breezy
- Filament Logger
- Filament FullCalendar

Seluruh plugin hanya boleh dipasang jika release yang dipilih secara eksplisit kompatibel dengan Filament 5.x, Laravel 13.8.0, Livewire 4.x, dan PHP 8.4.23.

---

# Version Lock

Baseline teknologi wajib:

| Komponen | Versi |
|---|---|
| PHP | `8.4.23` |
| Laravel | `13.8.0` |
| Filament | `5.x` |
| Livewire | `4.x` |
| Tailwind CSS | `4.1+` dalam major `4.x` |

Constraint wajib:

```json
{
  "require": {
    "php": "8.4.23",
    "laravel/framework": "13.8.0",
    "filament/filament": "^5.0",
    "livewire/livewire": "^4.0"
  }
}
```

Resolved patch version wajib dikunci melalui `composer.lock` dan lockfile package manager frontend.

Aturan:

- Dilarang menggunakan dokumentasi, generator, contoh, namespace, API, komponen, atau pola kode Filament 2, 3, atau 4.
- Referensi Filament hanya menggunakan dokumentasi resmi jalur `/docs/5.x/`.
- Dilarang menggunakan syntax atau API yang membutuhkan PHP di atas 8.4.23.
- Dilarang menulis compatibility layer untuk PHP di bawah 8.4.23.
- Seluruh environment development, CI, staging, queue worker, scheduler, dan production wajib menggunakan PHP 8.4.23.
- Laravel wajib berada tepat pada versi 13.8.0 sampai Project Owner menyetujui perubahan versi.
- `composer install` wajib menggunakan lockfile; `composer update` tidak dilakukan tanpa review dependency dan pembaruan lockfile.
- Plugin yang belum terverifikasi kompatibel tidak boleh dipasang.

---

# Aturan Pengembangan

Developer **tidak diperbolehkan**:

❌ membuat role sendiri

❌ membuat status baru tanpa dokumentasi

❌ membuat tabel duplicate

❌ membuat upload file manual

❌ membuat permission manual

❌ membuat dashboard tanpa Filament 5

❌ membuat admin panel custom

❌ menggunakan package lain tanpa review

---

# AI Development Rules

AI wajib mengikuti aturan berikut.

## Jangan Mengubah

- Business Process
- Workflow
- Database Relationship
- Status
- Role
- Permission

tanpa instruksi eksplisit.

---

## Selalu Gunakan

- Repository Pattern (jika digunakan)
- Service Layer
- Form Request
- Policy
- Resource
- Enum
- DTO (bila ada)
- Queue
- Event
- Notification

---

## Jangan Mengulang Logic

Business Rule hanya berada di satu tempat.

Hindari duplicate logic.

---

# Development Phases

Roadmap pengembangan resmi berada pada:

`13-roadmap.md`

Roadmap menetapkan tepat **40 Development Phases** dalam delapan milestone:

| Milestone | Phase | Fokus |
|---|---:|---|
| A | 1–6 | Fondasi Produk |
| B | 7–10 | Akuisisi dan Data Utama |
| C | 11–14 | Aktivasi dan Keuangan Awal |
| D | 15–22 | Workflow Operasional Paralel |
| E | 23–27 | Sertifikat, Pelunasan, dan Penutupan |
| F | 28–36 | Kontrol, Monitoring, dan Produktivitas |
| G | 37–39 | Interoperabilitas dan Otomasi |
| H | 40 | Quality, Release, dan Scale |

Judul, target, fitur, output, dependency, file terkait, status, Definition of Ready, dan Definition of Done setiap phase hanya didefinisikan pada `13-roadmap.md`.

Aturan eksekusi:

- Phase hanya boleh dimulai apabila dependency wajibnya telah tersedia.
- Phase dapat berjalan paralel apabila tidak memiliki dependency langsung.
- Urutan atau scope tidak boleh diubah tanpa persetujuan Project Owner dan pembaruan `13-roadmap.md`.
- Fitur di luar scope phase dipindahkan ke backlog.
- Status phase menggunakan Planned, Ready, In Progress, In Review, Completed, On Hold, atau Cancelled sesuai `13-roadmap.md`.
- Future Enhancement bukan bagian dari 40 phase dan harus dibuat menjadi phase baru setelah disetujui.

---

# Checklist Sebelum Coding

Pastikan sudah membaca:

- [ ] start-here.md
- [ ] 00-overview.md
- [ ] 00-glossary.md
- [ ] 01-business.md
- [ ] 02-workflow.md
- [ ] 03-role-permission.md
- [ ] 04-database.md
- [ ] 05-api.md
- [ ] 06-routing.md
- [ ] 07-status.md
- [ ] 08-notification.md
- [ ] 09-ui-ux.md
- [ ] 10-design-system.md
- [ ] 11-folder-structure.md
- [ ] 12-development-rules.md
- [ ] 13-roadmap.md
- [ ] 14-brand-guideline.md
- [ ] Dokumen detail pada folder `workflow/`, `database/`, dan `ui/` yang terkait dengan phase aktif

Pastikan memahami:

- [ ] Business Process
- [ ] Workflow
- [ ] Database
- [ ] Status
- [ ] Dependency dan scope phase aktif
- [ ] Definition of Ready dan Definition of Done
- [ ] Permission
- [ ] UI
- [ ] Development Rules

---

# Goal

Target utama project ini adalah menghasilkan sistem yang:

- Mudah dikembangkan
- Mudah dipelihara
- Konsisten
- Modular
- Scalable
- Testable
- AI Friendly
- Enterprise Ready

Seluruh implementasi harus mengacu pada dokumentasi ini.

---

> **Jika Anda adalah AI Assistant atau Developer baru, hentikan proses coding dan baca dokumentasi sesuai urutan di atas sebelum membuat perubahan apa pun pada project.**
