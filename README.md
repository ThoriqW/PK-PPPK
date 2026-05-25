# Perjanjian Kerja BKPSDMD Kota Palu

Internal admin system for **BKPSDMD Kota Palu** to manage PPPK work agreements: import employee data from Excel, edit HTML agreement templates, generate PDF agreements with QR codes, run batch extensions, and serve a public QR-verified status page.

Stack: Laravel 11 + Vue 3 + PostgreSQL + DomPDF + Maatwebsite\\Excel + Sanctum.

---

## 1. Quick start (local)

Prerequisites:
- PHP 8.2+, Composer 2
- Node 18+ and npm
- PostgreSQL 14+

```bash
# 1. Install backend deps and bootstrap env
composer install
cp .env.example .env
php artisan key:generate

# 2. Configure your DB in .env (DB_CONNECTION=pgsql)

# 3. Run migrations + seed reference data and the default admin
php artisan migrate --seed

# 4. Install frontend deps and start the Vite dev server
npm install
npm run dev   # or `npm run build` for production assets

# 5. Serve the app (separate terminal)
php artisan serve
```

Default admin (change immediately after first login):
- email: `admin@bkpsdmd.palu.go.id`
- password: `ChangeMe!2026`

The admin SPA is at `/admin`. The public QR verifier is at `/verify/{token}`.

---

## 2. Project layout

```
app/
  Exceptions/             Domain BusinessException (machine-readable error codes).
  Http/
    Controllers/Admin/    REST endpoints under /api/admin/*
    Controllers/Public/   /verify/{token}  (no auth, rate-limited)
    Middleware/           EnsureAdminAuthenticated
    Requests/Admin/       FormRequest validation
    Resources/            JsonResource transformers
  Models/                 Eloquent models, one per table
  Providers/              AppServiceProvider, RateLimitServiceProvider
  Services/
    AgreementCreationService   create new (BARU) agreement, snapshot, render PDF
    AgreementExtensionService  extend (single + batch by appointment_year)
    AgreementNumberGenerator   transactional counter + format render
    AuditLogger                single entry point for audit_logs
    EmployeeImportService      Excel ingestion with per-row outcomes
    NumberFormatRenderer       {seq}/{year}/... template engine
    PdfRenderingService        HTML -> PDF + QR injection
    QrTokenService             unguessable tokens + QR data URI
    RetirementCalculator       DOB + jabatan -> retirement date

resources/
  css/app.css
  js/                     Vue 3 + Vue Router + Pinia
    api/                  axios wrappers
    components/HtmlEditor TipTap with placeholder picker
    layouts/AdminLayout
    pages/                LoginPage, Dashboard, employees/, templates/,
                          numbering/, agreements/, archive/, audit/
    router/, stores/
  views/
    app.blade.php         SPA shell
    verify.blade.php      Public verifier (Nama, Nomor, Status)
    agreements/pdf-layout.blade.php   PDF template wrapper

database/migrations/      All schema (timestamped, ordered)
database/seeders/         Default admin, OPDs, jabatan categories,
                          numbering config, default template
routes/web.php            SPA shell + /verify/{token}
routes/api.php            Admin API (Sanctum SPA auth)
```

---

## 3. Domain model overview

```
opds                  jabatan_categories (drives retirement_age)
   |                          |
   v                          v
employees -- 1..* --> agreements -- self-FK --> agreements (extension chain)
                         |    |
                         |    +-> agreement_archives (tombstones)
                         |
                         +-> agreement_template_versions (snapshot)
                         |
                         +-> numbering_configs (counter + format)

import_batches 1..* import_batch_rows (per-row outcome)
audit_logs (append-only history)
```

Key invariants:
- An issued agreement carries an immutable `snapshot` of employee data and references the exact `agreement_template_version` used. The PDF is therefore reproducible.
- Extension never mutates the previous agreement: old row goes to `ARSIP`, new row links via `parent_agreement_id`.
- Numbering is incremented inside `SELECT ... FOR UPDATE` so concurrent admins cannot collide.
- `qr_token` is a 40-char URL-safe random string; the public route only exposes `nama, nomor, status`.

---

## 4. Excel import format

One canonical sheet, header row exact match (lowercase):

| nip | nik | nama_lengkap | tempat_lahir | tanggal_lahir | jenis_kelamin | pendidikan | jabatan | kategori_jabatan_kode | golongan | opd_kode | unit_kerja | tahun_pengangkatan | telepon | email |

Allowed `kategori_jabatan_kode`: `FUNGSIONAL_GURU`, `FUNGSIONAL_AHLI_PERTAMA`, `FUNGSIONAL_AHLI_MUDA`, `FUNGSIONAL_KETERAMPILAN`, `PELAKSANA`.

Duplicate rule: reject if `nip` already exists. If `nip` is empty, reject if `nik` already exists. Per-row outcome (`INSERTED`, `DUPLICATE`, `INVALID`, `ERROR`) is stored in `import_batch_rows` so the rejection can be audited and reported.

---

## 5. Numbering format

Configurable in `numbering_configs.format`. Supported placeholders:

| Token         | Resolves to                       |
|---------------|-----------------------------------|
| `{seq}`       | Zero-padded counter (padding cfg) |
| `{year}`      | Year of issuance (4 digits)       |
| `{month}`     | Month (2 digits)                  |
| `{roman_month}` | Roman numeral month (I .. XII)  |
| `{opd}`       | OPD code                          |

Reset policies: `NEVER`, `YEARLY`, `MONTHLY`. Default seeded format:
`{seq}/PPPK/BKPSDMD/{roman_month}/{year}` with `padding=3`, `reset_policy=YEARLY`.

---

## 6. Retirement rules

Driven by `jabatan_categories.retirement_age`:
- Jabatan Fungsional Guru: 60
- All other PPPK categories: 58

`RetirementCalculator` returns the **last day of the month** the employee reaches the retirement age. `period_end` of any agreement is hard-capped to that date; `suggestExtensionEnd()` returns `capped: true` when 5 years would exceed it.

---

## 7. Public QR verification

- URL: `https://<host>/verify/{token}`
- Renders a Blade page with **only**: Nama Pegawai, Nomor Perjanjian, Status (`AKTIF`/`ARSIP`/`DIBATALKAN`).
- Rate limit: 60 req/min/IP (configured in `RateLimitServiceProvider`).
- Returns `Cache-Control: no-store`, `X-Frame-Options: DENY`, `Referrer-Policy: no-referrer`.
- No PII (NIP, DOB, OPD, etc.) is exposed.

---

## 8. Audit log actions

Written by `AuditLogger`, never mutated:
`LOGIN`, `LOGOUT`, `IMPORT`, `TEMPLATE_CREATE`, `TEMPLATE_UPDATE`, `AGREEMENT_CREATE`, `AGREEMENT_EXTEND`, `AGREEMENT_CANCEL`, `ARCHIVE`, `NUMBERING_CHANGE`, `EMPLOYEE_UPDATE`.

For stronger guarantees in production, add a Postgres trigger that blocks UPDATE/DELETE on `audit_logs`.

---

## 9. Phase status

Implemented in this scaffold:
- Phase 0: Foundation (Laravel + Vue + Sanctum + PG, base migrations, login, audit)
- Phase 1: Employee master + Excel import with duplicate rejection
- Phase 2: HTML templates (TipTap) with versioning + numbering configs
- Phase 3: Agreement creation, PDF render with QR, public verifier
- Phase 4: Single + batch extension, archive

Recommended next:
- Switch DomPDF to Browsershot if you need richer typography
- Add Postgres trigger that blocks UPDATE/DELETE on `audit_logs`
- Optional 2FA for admins (`pragmarx/google2fa`)
- IP allowlist middleware on `/admin/*` for office network only
- Daily encrypted DB backup automation (pg_dump + GPG)

---

## 10. Production hardening checklist

- [ ] Set `APP_ENV=production`, `APP_DEBUG=false`
- [ ] Generate fresh `APP_KEY`
- [ ] Force HTTPS, HSTS, modern TLS at the edge
- [ ] Set `SESSION_SECURE_COOKIE=true`, `SESSION_SAME_SITE=lax`
- [ ] Lock down `/admin/*` to office IPs / VPN
- [ ] Configure daily backups of PostgreSQL + `storage/app/agreements/`
- [ ] Enable Postgres role with least privilege for the app
- [ ] Rotate seeded admin password immediately
