# Database - Partners

## Tujuan

Entitas **Partners** menyimpan identitas Mitra yang membawa atau menangani Client bertipe Mitra.

Satu Partner dapat berelasi dengan banyak Client. Satu Client bertipe Mitra wajib terhubung ke tepat satu Partner.

---

# Struktur Data

Minimal terdiri dari:

- `id` — UUID
- `partner_code` — Business ID unik
- `name`
- `pic_name`
- `phone`
- `email`
- `address` (opsional)
- `created_at`
- `updated_at`
- `deleted_at`

Format Business ID:

`PARTNER-YYYY-XXXX`

---

# Relasi

- Partner 1 → N Client
- Partner 1 → N Invoice dengan audience Mitra

Invoice tetap terhubung langsung ke Project. `partner_id` menunjukkan master Partner, sedangkan identitas penerima saat penerbitan dibekukan dalam `invoices.billing_snapshot`.

---

# Business Rule

- Partner tidak sama dengan Client dan tidak memakai ID Client.
- Partner tidak memiliki Project.
- Partner tidak dihitung sebagai transaksi.
- Partner dibuat atau digunakan kembali secara otomatis pada proses Lead Deal; tidak tersedia Create generik di menu lain.
- Sebelum membuat Partner baru, sistem mencari kecocokan berdasarkan nama ternormalisasi dan kontak untuk mencegah duplikasi.
- Partner tidak dapat dihapus permanen apabila telah digunakan oleh Client atau Invoice.
- Nama dan kontak Partner tidak disalin ke Client.

---

# Index dan Constraint

- unique `partner_code`
- index `name`
- index `email`
- index `deleted_at`
