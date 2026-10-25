# Hostinger production deployment

The `Production` GitHub Actions workflow tests the locked dependencies, packages the application with production-only Composer dependencies, and uploads it over SSH on every push to `main`. It also supports manual runs on `main`.

Pull requests targeting `main` run the build and tests without deploying or accessing the production environment secrets.

## Current setup

The production GitHub environment has been configured with the SSH secrets and deployment variables, restricted to `main`. SSH key access, the PHP 8.3 executable/extensions, and private filesystem symlink switching have been verified on Hostinger. `shared/.env` exists with production mode, a permanent application key, and the database name and username. Its database password still needs to be entered directly on the server. No migrations, seeders, document-root changes, scheduler changes, or production deployment have been performed.

To enter the database password privately from PowerShell:

```powershell
ssh -t -i "$env:USERPROFILE\.ssh\cash-afandy-deploy" -p 65002 u554309232@89.116.133.246 'nano /home/u554309232/domains/cashafandy.com/cash-afandy/shared/.env'
```

Edit `DB_PASSWORD`, then save with Ctrl+O, Enter, and exit with Ctrl+X. Keep the password out of chat and GitHub. The test suite must also pass before a release can deploy.

CI uses Ubuntu 24.04 and checks for SQLite 3.44+ because the existing representatives migration uses `CONCAT`, which older SQLite versions do not provide. Production continues to use MySQL/MariaDB. See the [SQLite 3.44 release notes](https://www.sqlite.org/releaselog/3_44_0.html).

Each deployment gets its own release directory. `.env`, storage, and uploads are shared across releases. The current release changes only after preparation succeeds. A failed HTTP check restores the previous release. Database migrations and seeders never run automatically; pending migrations block publication.

## 1. Hosting requirements

- SSH, Bash, `tar`, `flock`, `curl`, `realpath`, and GNU `mv` with `-T` support.
- PHP 8.3, for both the website and SSH commands, with bcmath, ctype, curl, dom, exif, fileinfo, gd, gmp, intl, mbstring, openssl, pdo_mysql, xml, xmlreader, xmlwriter, and zip enabled. The deployment checks the CLI runtime; verify the website runtime in hPanel as well.
- An HTTPS domain and a MySQL/MariaDB database configured in hPanel.
- Working filesystem symlinks, including serving the website through a `public_html` symlink. Enable the PHP `symlink` function for application features too. Verify this on the actual plan before enabling automatic publishing. If Hostinger will not serve this layout, stop and adapt deployment to the supported document root.
- Enough disk space for the uploaded archive and multiple complete releases. Releases are retained for recovery and are not automatically deleted; monitor disk usage and remove old, inactive releases separately.

Hostinger references: [SSH access](https://www.hostinger.com/support/1583245-how-to-connect-to-a-hosting-plan-via-ssh-in-hostinger/) and [symlinks](https://www.hostinger.com/support/1583694-how-to-create-a-symlink-at-hostinger/).

## 2. Server layout

The paths below use the confirmed Hostinger account `u554309232` and domain `cashafandy.com`. The private deployment root is `/home/u554309232/domains/cashafandy.com/cash-afandy`.

```text
/home/u554309232/domains/cashafandy.com/
├── cash-afandy/
│   ├── shared/
│   │   ├── .env
│   │   ├── storage/
│   │   └── uploads/
│   ├── incoming/
│   ├── releases/<run>-<attempt>-<commit>/
│   └── current -> releases/<run>-<attempt>-<commit>
└── public_html -> cash-afandy/current/public
```

Laravel source and secrets must be outside `public_html`. Do not place the entire repository in the public directory or enable Hostinger's separate Git auto-deployment for this same site.

Connect using the SSH command shown by hPanel, then create the private application directory:

```bash
ssh -p 65002 u554309232@89.116.133.246
mkdir -p /home/u554309232/domains/cashafandy.com/cash-afandy/shared
chmod 700 /home/u554309232/domains/cashafandy.com/cash-afandy/shared
```

Using the file manager or SFTP, create `cash-afandy/shared/.env` from the repository's `.env.example`. Set at least:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://cashafandy.com
DB_CONNECTION=mariadb
DB_HOST=localhost
DB_DATABASE=YOUR_HOSTINGER_DATABASE
DB_USERNAME=YOUR_HOSTINGER_DATABASE_USER
DB_PASSWORD="YOUR_DATABASE_PASSWORD"
SESSION_DRIVER=file
CACHE_STORE=file
QUEUE_CONNECTION=database
LOG_LEVEL=warning
```

Use the actual database connection details from hPanel, including the host and port. Configure mail and OAuth credentials as needed. The current server already has a permanent `APP_KEY`; preserve it. For a fresh installation only, leave the key blank until generating it once during preparation. Never upload a local `.env` or commit the production file.

```bash
chmod 600 /home/u554309232/domains/cashafandy.com/cash-afandy/shared/.env
```

## 3. Dedicated SSH key and GitHub settings

This computer already has a dedicated Ed25519 deployment key:

- Public key: `C:/Users/ahmed/.ssh/cash-afandy-deploy.pub` — add this to Hostinger.
- Private key: `C:/Users/ahmed/.ssh/cash-afandy-deploy` — add its contents only to the GitHub `HOSTINGER_SSH_KEY` secret.

Reuse that pair for this setup. If setting up from another computer where this key does not exist, generate a dedicated pair locally (Windows Git Bash):

```bash
ssh-keygen -t ed25519 -f ~/.ssh/cash-afandy-deploy -C cash-afandy-actions
```

Use an empty passphrase for this automation key. Add only its public `.pub` key to the Hostinger account's authorized SSH keys. Put the private key in GitHub Secrets. Do not send it in chat, put it in the repository, or reuse your personal SSH key. Never overwrite an existing key during setup.

To authorize the existing deployment key from PowerShell on this computer, run the following command and enter your usual SSH password locally when prompted. It appends only the public key and sets the SSH directory/file permissions:

```powershell
Get-Content "$env:USERPROFILE\.ssh\cash-afandy-deploy.pub" | ssh -p 65002 u554309232@89.116.133.246 'umask 077; mkdir -p ~/.ssh; cat >> ~/.ssh/authorized_keys; chmod 700 ~/.ssh; chmod 600 ~/.ssh/authorized_keys'
```

Run it once, then test key-based access:

```powershell
ssh -i "$env:USERPROFILE\.ssh\cash-afandy-deploy" -p 65002 -o BatchMode=yes u554309232@89.116.133.246
```

Verify the server's SSH fingerprint with Hostinger before trusting the first connection. Use the matching entry from your trusted `~/.ssh/known_hosts` for the secret below. For a non-default port, its host entry should look like `[HOST]:PORT`. Do not trust a fresh `ssh-keyscan` result without verifying its fingerprint.

In GitHub, open repository **Settings → Environments → New environment**, create `production`, and restrict deployment branches to `main`. Leave required reviewers disabled if you want unattended deployments. See [GitHub deployment environments](https://docs.github.com/en/actions/how-tos/deploy/configure-and-manage-deployments/control-deployments).

Add these environment **secrets**:

| Name | Value |
| --- | --- |
| `HOSTINGER_HOST` | `89.116.133.246` |
| `HOSTINGER_PORT` | `65002` |
| `HOSTINGER_USER` | `u554309232` |
| `HOSTINGER_SSH_KEY` | Complete private key, including header and footer |
| `HOSTINGER_KNOWN_HOSTS` | Verified SSH known-host entry for that host and port |

Add these environment **variables**:

| Name | Example |
| --- | --- |
| `HOSTINGER_DEPLOY_PATH` | `/home/u554309232/domains/cashafandy.com/cash-afandy` |
| `HOSTINGER_PHP_BIN` | `/opt/alt/php83/usr/bin/php` (verified on this hosting account; the default `php` is 8.2) |
| `HOSTINGER_HEALTH_URL` | `https://cashafandy.com/ar/up` |

The health URL must return HTTP 200 directly, with no redirect, authentication, or query string. Use the supported locale and canonical domain. Disable caching for this URL. Deployment also checks a temporary, unique public marker to confirm the domain serves the new release.

If Composer needs authentication for private Redot packages, add repository-level secret `COMPOSER_AUTH` containing the appropriate Composer authentication JSON with read access to those packages. It must be a repository secret because the build job does not use the production environment. Do not commit `auth.json`.

## 4. First release: prepare without publishing

After committing and pushing the workflow files, the initial automatic deployment cannot publish until production settings and the database are ready. A missing `.env` or missing migration table causes a failure without switching the live release.

In **Actions → Production → Run workflow**, select `main` and enable **Prepare a release for initial database setup without publishing it**. This installs no database tables and does not change `current` or `public_html`.

The run prints the prepared release's full path. Connect over SSH and enter that exact directory:

```bash
cd /home/u554309232/domains/cashafandy.com/cash-afandy/releases/RELEASE_ID_FROM_ACTIONS
```

Use the same PHP executable as `HOSTINGER_PHP_BIN` for all manual Artisan commands. On a fresh server without an application key, run `/opt/alt/php83/usr/bin/php artisan key:generate --force` once. The current server's key has already been generated, so do not run that command again. Future releases reuse `shared/.env`.

Before publication, review and explicitly approve the existing migrations and the production initialization plan. The migrations live in `database/migrations`; essential reference data comes from the existing language, country, state, settings, role, and static-page seeders. Run only the approved initialization commands from this prepared directory. No database command is part of this workflow or this guide's executable setup steps.

Do not blindly run `DatabaseSeeder` in production: `AdminSeeder` creates a publicly known default administrator password. Administrator provisioning needs a separate, approved setup with a unique credential. Do not publish until that setup is complete.

For this new, unpublished website, preserve the existing `public_html` directory as a backup before replacing it. Inspect it first and choose an unused backup name. Do not overwrite an existing backup:

```bash
cd /home/u554309232/domains/cashafandy.com
ls -la public_html
test ! -e public_html.before-cash-afandy && test ! -L public_html.before-cash-afandy && mv public_html public_html.before-cash-afandy
test ! -e public_html && test ! -L public_html && ln -s cash-afandy/current/public public_html
```

This is a one-time document-root change and temporarily leaves the domain pointing at a missing `current` until the first normal deployment. Use it only for this unpublished site after confirming the symlink layout works on the hosting plan.

After database initialization and production settings are ready, run the workflow again with preparation disabled. It checks PHP, production mode, the application key, and migration status; caches configuration and routes; builds Redot assets with `dependencies:build`; switches `current`; and checks the website. Subsequent pushes to `main` repeat this automatically.

## 5. Scheduler, updates, and recovery

Add one hPanel cron job every minute, using the correct PHP executable:

```bash
cd /home/u554309232/domains/cashafandy.com/cash-afandy/current && /opt/alt/php83/usr/bin/php artisan schedule:run >> /dev/null 2>&1
```

The existing `routes/console.php` already schedules a short-lived queue worker. Do not start a persistent worker on shared hosting.

When a later release adds migrations, it remains staged and the current release stays live. Review and approve the migration SQL, take a database backup, and plan compatibility with the currently live code before manually applying it from the staged release. Then rerun deployment. Automatic code rollback cannot undo database changes.

For manual code recovery, first confirm that no deployment is running and that the selected prior release is compatible with the current database. Substitute an actual retained release path:

```bash
cd /home/u554309232/domains/cashafandy.com/cash-afandy
ln -s /home/u554309232/domains/cashafandy.com/cash-afandy/releases/PREVIOUS_RELEASE current-recovery
mv -Tf current-recovery current
```

Recheck the health URL and assets afterward. Settings, sessions, uploads, and storage survive code rollback. Old releases remain on disk until deliberately removed. If PHP OPcache does not revalidate files or Hostinger caches the document-root symlink, ask Hostinger to clear/restart the site's PHP cache and verify switching before relying on automatic releases.
