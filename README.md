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

## Website notification foundation

`App\Notifications\WebsiteNotification` reuses the existing database notification payload and adds a `body`. It uses Laravel's normal `notify()` interface, queues database delivery after the surrounding transaction commits, and queues a separate `App\Jobs\SendPushNotification` for each active device only after storage succeeds. No product events, routes, browser subscription flow, or UI are connected yet.

The pending `2026_10_28_000001_create_push_subscriptions_table.php` migration adds polymorphic device subscriptions (`notifiable_type` and `notifiable_id`) with a unique SHA-256 token fingerprint, activity time, and optional disable time. It must be reviewed and explicitly approved before being run. Any Eloquent model using Laravel's `Notifiable` trait can receive notifications; no recipient class allowlist or extra relationship on that model is needed. Associate a subscription through `$subscription->notifiable()->associate($recipient)`; the model derives the fingerprint and hides both token fields from serialization. Future browser endpoints must authenticate the owner, validate tokens, handle token rotation and account switching, and detach subscriptions on logout. Polymorphic ownership has no database foreign-key cascade; deleting a recipient makes pending jobs skip delivery, and subscription cleanup belongs to the integration lifecycle.

Push defaults to off (`FIREBASE_PUSH_ENABLED=false`) and is configured through the existing `cash-afandy.php` feature overrides. When enabling it later, provide `FIREBASE_CREDENTIALS` using a private service-account file outside `public`, ensure the locked Firebase packages are installed, and run the existing queue worker. Database-only notifications do not require Firebase credentials. User-level marketing consent and browser permission handling remain integration work; do not use this foundation for marketing until consent checks are implemented.

Each push job rechecks the token fingerprint, subscription status, surviving recipient (respecting that model's global scopes), and notification ownership by both recipient type and ID before resolving Firebase. It sends a title/body preview and the stored notification ID; keep sensitive information out of previews. Only an explicit Firebase `UNREGISTERED` response disables a subscription. Other failures remain visible to the queue retry/failed-job mechanism. Delivery is at least once: a worker crash after Firebase accepts a message can cause a retry. The stable notification ID and Web Push tag support client-side deduplication when the frontend is added; they do not guarantee exactly-once delivery or action-level deduplication.

The current scaffold unregisters service workers when asset caching is disabled or outside production. Browser integration must separate Push registration from asset caching and reuse the existing service worker before enabling Web Push. The current frontend and service worker are intentionally unchanged.
