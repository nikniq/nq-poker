#!/usr/bin/env bash
# Production deploy for nq-poker. Run ON the server, from the app root:
#
#   ./deploy.sh                 # pull + composer (if lock changed) + migrate + caches
#   ./deploy.sh SomeSeeder      # ...and re-run one seeder class
#   ./deploy.sh --seed          # ...and re-run every seeder (confirms first)
#
# Its local counterpart is cx.sh (option 20 runs this over ssh).

set -euo pipefail
cd "$(dirname "$0")"

PHP="${PHP:-php}"

echo "→ git pull"
LOCK_BEFORE=$(git rev-parse HEAD:composer.lock 2>/dev/null || echo none)
git pull --ff-only
LOCK_AFTER=$(git rev-parse HEAD:composer.lock 2>/dev/null || echo none)

if [ "$LOCK_BEFORE" != "$LOCK_AFTER" ] || [ ! -d vendor ]; then
    if command -v composer >/dev/null 2>&1; then
        echo "→ composer install"
        composer install --no-dev --optimize-autoloader --no-interaction
    else
        echo "⚠ dependencies changed but composer is not on PATH — install them manually."
    fi
fi

echo "→ migrate"
$PHP artisan migrate --force

if [ "${1:-}" = "--seed" ]; then
    read -r -p "Re-run EVERY seeder? Type 'yes' to continue: " confirm
    if [ "$confirm" = "yes" ]; then $PHP artisan db:seed --force; else echo "→ skipped seeding"; fi
elif [ -n "${1:-}" ]; then
    echo "→ db:seed --class=$1"
    $PHP artisan db:seed --class="$1" --force
fi

echo "→ storage link + caches"
$PHP artisan storage:link >/dev/null 2>&1 || true
$PHP artisan optimize

echo "✓ deployed $(git log --oneline -1)"
