# 11. Folder Structure

## Tujuan

Dokumen ini mendefinisikan struktur folder source code PHC System.

Tujuannya adalah menjaga konsistensi struktur proyek, mempermudah pengembangan, dan memudahkan maintenance.

Seluruh developer wajib mengikuti struktur folder yang telah ditetapkan.

---

# Referensi

Dokumen terkait:

- 04-database.md
- 05-api.md
- 06-routing.md
- 09-ui-ux.md
- 10-design-system.md
- 12-development-rules.md

---

# Prinsip

Struktur project mengikuti prinsip:

- Feature Based
- Modular
- Scalable
- Reusable
- Easy to Navigate

Hindari struktur berdasarkan jenis file apabila menyebabkan modul bisnis tersebar.

---

# Struktur Root

Contoh struktur proyek.

```text
src/

├── app/
├── modules/
├── components/
├── hooks/
├── services/
├── lib/
├── utils/
├── types/
├── constants/
├── layouts/
├── providers/
├── styles/
├── assets/
└── middleware/
```

---

# App

Folder app berisi:

- Routing
- Layout
- Entry Point
- Global Error
- Loading

Contoh

```text
app/

dashboard/
clients/
leads/
login/
settings/
```

---

# Modules

Setiap domain bisnis memiliki module sendiri. Fitur yang berada dalam domain yang sama dikelompokkan pada satu module; Invoice berada di dalam module `payments`.

```text
modules/

auth/
users/
leads/
clients/
partners/
projects/
documents/
workflow/
tasks/
payments/
certificates/
notifications/
reports/
settings/
```

Setiap module berdiri sendiri.

---

# Struktur Module

Contoh:

```text
modules/projects/

api/
components/
hooks/
services/
schemas/
types/
utils/
constants/
```

---

# Components

Berisi komponen yang digunakan lintas module.

```text
components/

ui/
common/
business/
```

Referensi:

10-design-system.md

---

# Hooks

Custom Hook global.

Contoh

```text
hooks/

useAuth
useDebounce
usePermission
usePagination
```

---

# Services

Berisi service yang digunakan secara global.

Contoh

```text
services/

api.ts
auth.ts
storage.ts
notification.ts
```

---

# Lib

Library dan konfigurasi.

Contoh

```text
lib/

axios.ts
dayjs.ts
query.ts
zod.ts
```

---

# Utils

Berisi helper.

Contoh

```text
utils/

currency.ts
date.ts
download.ts
formatter.ts
validator.ts
```

---

# Types

Seluruh tipe global.

```text
types/

api.ts
auth.ts
project.ts
invoice.ts
```

---

# Constants

Berisi konstanta aplikasi.

```text
constants/

routes.ts
roles.ts
status.ts
permissions.ts
```

Seluruh Enum mengacu pada:

07-status.md

03-role-permission.md

---

# Layouts

Layout aplikasi.

Contoh

```text
layouts/

MainLayout
AuthLayout
```

---

# Providers

Global Provider.

Contoh

```text
providers/

AuthProvider
ThemeProvider
QueryProvider
```

---

# Styles

Global CSS.

Contoh

```text
styles/

globals.css
variables.css
```

---

# Assets

Berisi aset statis.

```text
assets/

images/
icons/
logo/
illustrations/
```

---

# Middleware

Berisi middleware aplikasi.

Contoh

```text
middleware/

auth.ts
permission.ts
```

---

# Naming Convention

Folder

Gunakan:

kebab-case

Contoh

```text
project-detail
invoice-summary
```

---

File

Gunakan:

kebab-case

Contoh

```text
project-table.tsx

invoice-card.tsx
```

---

Component

Gunakan PascalCase.

Contoh

```text
ProjectTable

InvoiceCard

WorkflowStepper
```

---

Hook

Gunakan prefix:

use

Contoh

```text
useProject

useInvoice

useWorkflow
```

---

Type

Gunakan PascalCase.

Contoh

```text
Project

Invoice

Payment
```

---

Constant

Gunakan UPPER_SNAKE_CASE.

Contoh

```text
PROJECT_STATUS

ROLE_ADMIN

DEFAULT_PAGE_SIZE
```

---

Import

Gunakan Absolute Import.

Contoh

```text
@/modules/projects

@/components/ui

@/utils/date
```

Hindari Relative Import yang terlalu panjang.

---

Dependency

Module tidak boleh saling bergantung secara langsung.

Gunakan:

- Services
- API
- Shared Component

sebagai media komunikasi.

---

Shared Component

Komponen umum tidak boleh diletakkan di dalam Module.

Gunakan:

components/

---

Business Logic

Business Logic tidak boleh berada di Component UI.

Business Logic berada pada:

- Service
- Hook
- Module

---

API

Setiap Module memiliki API sendiri.

Contoh

```text
modules/projects/api

modules/payments/api
```

---

Testing

Apabila menggunakan Testing.

Gunakan:

```text
__tests__/
```

di dalam masing-masing module.

---

Scalability

Setiap fitur baru harus dibuat sebagai Module baru.

Tidak diperbolehkan mencampur fitur baru ke Module lain apabila tidak berkaitan.

---

Dokumentasi

Perubahan struktur folder harus diperbarui pada dokumen ini terlebih dahulu.

Seluruh developer wajib mengikuti struktur yang telah ditetapkan.
