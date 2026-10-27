# Cash Afandy

Cash Afandy helps users save money through cashbacks and offers, and manage their expenses. Built on [Redot](https://redot.dev) (Laravel, Livewire, Tabler UI)—depends on [`redot/core`](https://github.com/redot-src/core) and other Redot packages, so use Composer with access to those sources.

## Requirements

- PHP 8.3+
- Composer
- MariaDB or MySQL (see `DB_*` in `.env.example`)
- Node.js (only for `composer dev`, which runs `npx concurrently`)

## Local setup

```bash
cd cash-afandy
composer install
```

`composer install` copies `.env` from `.env.example` when missing and generates `APP_KEY` (see `post-install-cmd` in `composer.json`).

Set database (and anything else you need) in `.env`, then:

```bash
php artisan migrate --seed
composer dev
```

App: [http://127.0.0.1:8000](http://127.0.0.1:8000)

First-time bootstrap without seeding: `composer setup` (runs install, env/key, `migrate --force`).

## Composer scripts

| Script           | What it runs                                                        |
| ---------------- | ------------------------------------------------------------------- |
| `composer dev`   | `php artisan serve`, queue worker, `pail` logs (via `concurrently`) |
| `composer test`  | `php artisan test` (parallel)                                       |
| `composer lint`  | `php artisan lint --with-js`                                        |
| `composer setup` | Install, env/key, migrations                                        |

## Production deployment

See [DEPLOYMENT.md](DEPLOYMENT.md) for the Hostinger SSH setup and the GitHub Actions pipeline that tests and deploys pushes to `main`.
