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

`App\Notifications\WebsiteNotification` reuses the existing database notification payload and adds a `body`. It uses Laravel's normal `notify()` interface, queues database delivery after the surrounding transaction commits, and queues a separate `App\Jobs\SendPushNotification` for each active device only after storage succeeds. Browser registration is available from the website invitation for guests and users, with account controls on the profile; product events, the notification inbox, and dashboard sending are separate integration work.

The `2026_10_28_000001_create_push_subscriptions_table.php` migration adds polymorphic device subscriptions (`notifiable_type` and `notifiable_id`) with a unique SHA-256 token fingerprint, activity time, and optional disable time. It must be reviewed and explicitly approved before being run. Any Eloquent model using Laravel's `Notifiable` trait can receive notifications; no recipient class allowlist or extra relationship on that model is needed. Associate a subscription through `$subscription->notifiable()->associate($recipient)`; the model derives the fingerprint and hides both token fields from serialization. Polymorphic ownership has no database foreign-key cascade; deleting a recipient makes pending jobs skip delivery, and subscription cleanup belongs to the integration lifecycle.

Guests and website users can enable notifications from the same website invitation. The POST/DELETE `push-subscriptions` routes retain CSRF protection and throttling without requiring login. The `2026_10_29_000001_allow_guest_push_subscriptions.php` migration makes both recipient columns nullable; rollback refuses to discard guest subscriptions. Registration atomically assigns the browser token to the current account, or leaves both recipient fields null for guests, and disables the previous token tracked by that session after rotation. Login attaches an active guest subscription; logout clears only the current browser's account association while preserving public notifications. Explicit opt-out disables that session's subscription, checking recipient type and ID; other devices remain active. Registration refreshes on page visits and when a previously hidden page becomes visible after five minutes. Browser opt-in is remembered locally.

For public coupons or offers, send the existing notification to active browser subscriptions themselves. This also includes browsers without accounts; it does not require a separate notification class or delivery job:

```php
PushSubscription::query()->whereNull('disabled_at')->chunkById(100, function ($subscriptions) {
    Notification::send($subscriptions, new WebsiteNotification('New coupon', body: 'Public offer details'));
});
```

Use the normal `$user->notify(new WebsiteNotification(...))` path for personal notifications. Public notifications are stored against the browser subscription; personal ones remain stored against their recipient. Queued delivery rechecks this ownership, so logging out or switching accounts prevents pending personal notifications for the previous account from being sent to that browser. Product event triggers and dashboard sending still need integration.

Push defaults to off in configuration and is configured through the existing `cash-afandy.php` feature overrides. To enable it, set `FIREBASE_PUSH_ENABLED=true`, provide `FIREBASE_CREDENTIALS` using a private service-account file outside `public`, ensure the locked Firebase packages are installed, and run the existing queue worker. Populate the public web configuration variables (`FIREBASE_API_KEY`, `FIREBASE_AUTH_DOMAIN`, `FIREBASE_PROJECT_ID`, `FIREBASE_MESSAGING_SENDER_ID`, `FIREBASE_APP_ID`) and the public `FIREBASE_VAPID_KEY` from the same Firebase project. The profile control is available only when the web configuration is complete. Database-only notifications do not require Firebase credentials. User-level marketing consent remains separate integration work; browser permission alone is not marketing consent.

Each push job rechecks the token fingerprint, subscription status, surviving recipient (respecting that model's global scopes), and notification ownership by both recipient type and ID before resolving Firebase. It sends a title/body preview and the stored notification ID; keep sensitive information out of previews. Only an explicit Firebase `UNREGISTERED` response disables a subscription. Other failures remain visible to the queue retry/failed-job mechanism. Delivery is at least once: a worker crash after Firebase accepts a message can cause a retry. The stable notification ID and Web Push tag support client-side deduplication when the frontend is added; they do not guarantee exactly-once delivery or action-level deduplication.

Push reuses the existing `/service-worker.js` independently of asset caching, including on localhost. Public web configuration and the pinned SDK version are passed in the worker registration URL; private service-account credentials are never included. Only the application's worker and asset cache are removed when both features are disabled. Background notification payloads are displayed by Firebase; foreground messages use the existing escaped toast UI and emit `website:notification-received` with the stored notification ID for future inbox updates.

### Firebase preparation

Create a project in the [Firebase console](https://console.firebase.google.com/). Open **Project settings → Service accounts → Firebase Admin SDK → Generate new private key**, then save the downloaded JSON as `storage/app/firebase/service-account.json`. This private directory is excluded from Git. On production, keep the file in shared storage outside the public document root so it survives releases. Never put the JSON contents into frontend configuration. See the [Firebase service-account setup guide](https://firebase.google.com/docs/admin/setup#initialize_the_sdk_in_non-google_environments).

Point `FIREBASE_CREDENTIALS` in the local `.env` to that file (relative to the project root, or use an absolute path). Keep `FIREBASE_PUSH_ENABLED=false` until credentials, public web configuration, and the subscriptions migration are ready. After changing cached production configuration, rebuild that configuration before starting workers. Service-account configuration uses the Firebase package defaults; no extra `config/firebase.php` is required.

The current database queue already uses the `jobs` and `failed_jobs` tables. For local processing, use `php artisan queue:work --tries=3 --timeout=30` in a terminal after approving any queued work that will be processed. Starting a worker can execute existing application jobs. Push jobs have their own three-attempt policy and a 30-second timeout; the database queue's default `retry_after` is 90 seconds. Windows cannot enforce Laravel's PCNTL worker timeout, so network timeouts must still be bounded when configuring the Firebase HTTP client.

Production already schedules `queue:work --tries=3 --stop-when-empty` every minute in `routes/console.php`; the hosting cron documented in `DEPLOYMENT.md` must actually invoke the scheduler. This can add about a minute of queue waiting time before Firebase delivery. Do not start a second production worker as part of local preparation. Enable browser notifications from a chosen test account's profile before running a live end-to-end test.
