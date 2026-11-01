# KEA — Knowledge Ecosystem for Advancement

*Transforming Knowledge into Sustainable Competence.* — by Jendela Cakra Digital

Implementasi MVP Phase 1 sesuai dokumen di [`preparation/docs`](preparation/docs) (PRD, desain UI/UX, technical blueprint).

```text
kea/
├── api/      # Laravel (modular monolith) — REST /api/v1 + panel admin Filament di /admin
├── web/      # Nuxt — web publik (SSR/ISR) + dashboard learner & ruang kelas (SPA)
├── deploy/   # Nginx, Supervisor, PM2 (VPS tanpa Docker)
└── preparation/  # dokumen perencanaan, wireframe, dan aset brand
```

## Menjalankan lokal

Prasyarat: PHP 8.3+, Composer, PostgreSQL, Redis, Node 24 + pnpm.

```bash
# API (port 8000)
cd api
cp .env.example .env && php artisan key:generate   # sesuaikan DB_USERNAME
createdb kea
php artisan migrate --seed
php artisan serve

# Web (port 3000) — terminal lain
cd web
pnpm install
pnpm dev
```

Buka http://localhost:3000. Panel admin: http://localhost:8000/admin.
Nuxt mem-proxy `/api` dan `/sanctum` ke Laravel, jadi cookie sesi Sanctum bekerja same-origin seperti di produksi.

### Akun demo (password: `password`)

| Email | Role | Isi |
| --- | --- | --- |
| `learner@kea.test` | learner | Bootcamp Fullstack berjalan + video course SEO |
| `alumni@kea.test` | learner | Sudah lulus, sertifikat `KEA-FSWD-2026-000123` |
| `mentor@kea.test` | mentor | Review tugas di `/mentor` |
| `admin@kea.test` | admin | Panel Filament |

Voucher demo: `KEAHEMAT` (10%, maks. Rp500.000), `KEA250K`.
Pembayaran lokal memakai gateway **simulator**: setelah memilih metode bayar, tombol "Simulasikan pembayaran berhasil"
mengirim notifikasi lewat jalur webhook yang sama dengan Midtrans. Untuk Midtrans sandbox, isi `MIDTRANS_SERVER_KEY` dan
`PAYMENT_GATEWAY=midtrans`, lalu arahkan notifikasi ke `POST /api/v1/webhooks/midtrans`.

## Tes

```bash
cd api && php artisan test     # PostgreSQL database `kea_test`
cd web && pnpm typecheck
```

## Penyesuaian dari blueprint

- **Laravel 13 + Filament 5 + Nuxt 4** (bukan Laravel 11 / Filament 3 / Nuxt 3): semua rilis Laravel 11 diblokir advisory
  keamanan Composer. Arsitektur, modul, skema DB, dan endpoint tetap sesuai blueprint.
- Endpoint LMS di-scope ke enrollment, mis. `PUT /enrollments/{id}/lessons/{lesson}/progress`
  (blueprint: `/lessons/{id}/progress`), supaya otorisasi dan cohort jelas.
- Queue worker memakai `queue:work` (Horizon belum dipasang); lokal memakai `QUEUE_CONNECTION=sync`.

## Belum dikerjakan (perlu kredensial / di luar MVP)

Login Google (Socialite), pengiriman email invoice PDF & WhatsApp (Qontak), integrasi Zoom API, resume posisi video Bunny
(player.js), dan fitur Phase 2+ (job connector, MBL, short class/event lanjutan).
