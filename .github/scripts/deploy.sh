#!/usr/bin/env bash
set -euo pipefail
umask 022

root="$(realpath -e "${1:?Deployment path is required}")"
release_id="${2:?Release ID is required}"
php_bin="${3:-php}"
health_url="${4:-}"
prepare_only="${5:-false}"

fail() {
    printf '%s\n' "$*" >&2
    exit 1
}

[[ "$root" == "$(realpath -e "$HOME")/"* ]] || fail 'Deployment path must be inside the hosting home directory.'
[[ "$root/" != *'/public_html/'* ]] || fail 'Laravel must be outside public_html.'
[[ "$release_id" =~ ^[0-9]+-[0-9]+-[a-f0-9]{40}$ ]] || fail 'Invalid release ID.'
[[ "$prepare_only" == true || "$prepare_only" == false ]] || fail 'Invalid preparation mode.'
[[ "$prepare_only" == true || "$health_url" =~ ^https://[a-zA-Z0-9.-]+(:[0-9]+)?/[a-zA-Z0-9_./-]+$ ]] || fail 'Set an HTTPS health URL without a query string before publishing.'

shared="$root/shared"
release="$root/releases/$release_id"
archive="$root/incoming/$release_id.tar.gz"
[[ -f "$shared/.env" && ! -L "$shared/.env" ]] || fail 'Create shared/.env on the server first.'
[[ ! -e "$root/current" || -L "$root/current" ]] || fail 'current must be a symlink.'

exec 9> "$root/.deploy.lock"
flock -n 9 || fail 'Another deployment is running.'

mkdir -p "$root/releases"
mkdir "$release"
tar -xzf "$archive" -C "$release" --no-same-owner
rm "$archive"

mkdir -p "$shared/storage/app/private" "$shared/storage/app/public" \
    "$shared/storage/framework/cache/data" "$shared/storage/framework/sessions" \
    "$shared/storage/framework/views" "$shared/storage/logs" "$shared/uploads"

# Archive contents are pristine tracked files; runtime data lives only in shared.
mv "$release/storage" "$release/storage-template"
ln -s "$shared/storage" "$release/storage"
mv "$release/public/uploads" "$release/uploads-template"
ln -s "$shared/uploads" "$release/public/uploads"
ln -s "$shared/storage/app/public" "$release/public/storage"
ln -s "$shared/.env" "$release/.env"

cd "$release"
"$php_bin" vendor/composer/platform_check.php
# PHP expressions must not be expanded by the shell.
# shellcheck disable=SC2016
"$php_bin" -r '
$required = ["bcmath", "ctype", "curl", "dom", "exif", "fileinfo", "gd", "gmp", "intl", "mbstring", "openssl", "pdo_mysql", "xml", "xmlreader", "xmlwriter", "zip"];
$missing = array_filter($required, fn ($extension) => ! extension_loaded($extension));
if ($missing) {
    fwrite(STDERR, "Enable PHP extensions: " . implode(", ", $missing) . "\n");
    exit(1);
}
'

if [[ "$prepare_only" == true ]]; then
    printf 'Release prepared without publishing: %s\n' "$release"
    printf 'Configure production .env and initialize the database before a normal deployment.\n'
    exit 0
fi

# shellcheck disable=SC2016
"$php_bin" -r '
require "vendor/autoload.php";
$app = require "bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if (! $app->environment("production") || config("app.debug") || ! config("app.key")) {
    fwrite(STDERR, "Set APP_ENV=production, APP_DEBUG=false and a permanent APP_KEY.\n");
    exit(1);
}
'

# Never apply migrations during deployment; a pending migration blocks activation.
"$php_bin" artisan migrate:status --pending=1 --no-ansi --no-interaction
"$php_bin" artisan config:cache --no-interaction
"$php_bin" artisan route:cache --no-interaction
"$php_bin" artisan dependencies:build --no-interaction

previous="$(readlink "$root/current" || true)"
if [[ -n "$previous" ]]; then
    [[ "$previous" == "$root/releases/"* && -d "$previous" ]] || fail 'current does not point to a managed release.'
fi

switched=false
marker="deploy-$release_id.txt"
rollback() {
    status=$?
    rm -f "$release/public/$marker" "$root/current-next"
    if [[ "$status" -ne 0 && "$switched" == true ]]; then
        if [[ -n "$previous" ]]; then
            ln -s "$previous" "$root/current-next"
            mv -Tf "$root/current-next" "$root/current"
            printf 'Deployment failed; restored %s\n' "$previous" >&2
        else
            rm -f "$root/current"
            printf 'First deployment failed; the release was not kept live.\n' >&2
        fi
    fi
    exit "$status"
}
trap rollback EXIT
trap 'exit 1' HUP INT TERM

printf '%s' "$release_id" > "$release/public/$marker"
ln -s "$release" "$root/current-next"
mv -Tf "$root/current-next" "$root/current"
switched=true

# Confirm the web server serves this release, then check the existing Laravel page.
origin="$(printf '%s' "$health_url" | sed -E 's#^(https://[^/]+).*#\1#')"
served_id="$(curl --fail --silent --show-error --retry 3 --max-time 20 "$origin/$marker")"
[[ "$served_id" == "$release_id" ]] || fail 'The domain is not serving the new release.'
status="$(curl --silent --show-error --retry 3 --max-time 20 --output /dev/null --write-out '%{http_code}' "${health_url}?deployment=$release_id")"
[[ "$status" == 200 ]] || fail "Health check returned HTTP $status."

printf 'Published release: %s\nPrevious release: %s\n' "$release" "${previous:-none}"
