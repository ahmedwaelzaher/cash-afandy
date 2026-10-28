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

### Firebase preparation

Create a project in the [Firebase console](https://console.firebase.google.com/). Open **Project settings → Service accounts → Firebase Admin SDK → Generate new private key**, then save the downloaded JSON as `storage/app/firebase/service-account.json`. This private directory is excluded from Git. On production, keep the file in shared storage outside the public document root so it survives releases. Never put the JSON contents into frontend configuration. See the [Firebase service-account setup guide](https://firebase.google.com/docs/admin/setup#initialize_the_sdk_in_non-google_environments).

Point `FIREBASE_CREDENTIALS` in the local `.env` to that file (relative to the project root, or use an absolute path). Keep `FIREBASE_PUSH_ENABLED=false` until credentials, the subscriptions migration, and a test recipient are ready. After changing cached production configuration, rebuild that configuration before starting workers. Service-account configuration uses the Firebase package defaults; no extra `config/firebase.php` is required.

The current database queue already uses the `jobs` and `failed_jobs` tables. For local processing, use `php artisan queue:work --tries=3 --timeout=30` in a terminal after approving any queued work that will be processed. Starting a worker can execute existing application jobs. Push jobs have their own three-attempt policy and a 30-second timeout; the database queue's default `retry_after` is 90 seconds. Windows cannot enforce Laravel's PCNTL worker timeout, so network timeouts must still be bounded when configuring the Firebase HTTP client.

Production already schedules `queue:work --tries=3 --stop-when-empty` every minute in `routes/console.php`; the hosting cron documented in `DEPLOYMENT.md` must actually invoke the scheduler. This can add about a minute of queue waiting time before Firebase delivery. Do not start a second production worker as part of local preparation. Browser registration, a real device token, and an explicitly chosen test recipient are still needed for an end-to-end notification test.
