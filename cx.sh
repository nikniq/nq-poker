#!/bin/bash
#================================================================
# LOCAL operations assistant for nq-poker — cx.sh
#
#   ./cx.sh            # interactive menu
#   ./cx.sh 20         # run an option directly
#   ./cx.sh 33 "migrate:status"   # option with a parameter
#
# Runs on your LOCAL machine. Its server counterpart is deploy.sh.
# Requires vars.sh (copy vars.sh.example).
#================================================================

cd "$(dirname "$0")"

if [ ! -f ./vars.sh ]; then
  echo "vars.sh not found — copy vars.sh.example to vars.sh and fill it in."
  exit 1
fi
source ./vars.sh
REMOTE_APP="${REMOTE_APP:-$PROJECT}"
APP_URL="${APP_URL:-https://$PROJECT}"

log_history() {
  mkdir -p ./log
  printf '%s  %s\n' "$(date '+%Y-%m-%d %H:%M')" "$1" >> ./log/history.log
}
remote()     { $APP_SSH "$REMOTE" "cd ~/$REMOTE_APP && $*"; }
remote_tty() { $APP_SSH -t "$REMOTE" "cd ~/$REMOTE_APP && $*"; }
need_param() { [ -n "$PARAM2" ] || read -r -p "$1: " PARAM2; }
banner()     { echo "==========================================================="; echo "$1"; echo "==========================================================="; }
confirm() {
  echo "⚠ $1"
  read -r -p "Type 'yes' to continue: " reply
  [ "$reply" = "yes" ] || { echo "Skipped $SELECTION."; exit 0; }
}

if [ -z "$1" ]; then
cat <<MENU
=============================================================
nq-poker — acting on $REMOTE:~/$REMOTE_APP
-------------------------------------------------------------
ROUTINE
20 : DEPLOY: pull + composer + migrate + caches
21 : DEPLOY + run one seeder class
22 : WHAT'S LIVE: compare server HEAD with local
23 : MIGRATIONS: status
24 : CACHES: rebuild (optimize)
25 : CACHES: clear all (optimize:clear)
26 : COMPOSER: install production dependencies
DIAGNOSE
30 : HEALTH: check $APP_URL and /up
31 : LOGS: tail laravel.log
32 : LOGS: recent errors
33 : ARTISAN: run a command
34 : CONFIG: environment summary (secrets hidden)
35 : DISK: usage
MAINTENANCE
40 : SEED: run one seeder class
41 : QUEUE: list failed jobs
42 : QUEUE: retry failed jobs
43 : MAINTENANCE MODE: on  [site goes down]
44 : MAINTENANCE MODE: off
45 : LOGS: truncate laravel.log
ACCESS
00 : CONNECT: ssh to server
01 : SETUP: first-time clone instructions
qq : Quit
=============================================================
MENU
  read -r -p "Selection: " SELECTION
else
  SELECTION="$1"
fi
PARAM2="${2:-}"

case "$SELECTION" in
  20)
    banner "DEPLOY: $PROJECT"
    [ -n "$(git status --porcelain)" ] && { echo "⚠ Uncommitted local changes (will NOT deploy):"; git status --short; echo; }
    UNPUSHED=$(git log --oneline @{u}.. 2>/dev/null)
    [ -n "$UNPUSHED" ] && { echo "⚠ Unpushed commits (will NOT deploy):"; echo "$UNPUSHED"; confirm "Deploy anyway?"; }
    echo "Local HEAD: $(git log --oneline -1)"
    remote_tty "./deploy.sh" && log_history "Deployed $PROJECT"
    ;;
  21)
    banner "DEPLOY + SEEDER"
    need_param "Seeder class"
    remote_tty "./deploy.sh $PARAM2" && log_history "Deployed $PROJECT with seeder $PARAM2"
    ;;
  22)
    banner "WHAT'S LIVE"
    echo "--- server ---"; remote "git log --oneline -3 && git status --short"
    echo "--- local ---";  git log --oneline -3
    L=$(git rev-parse HEAD); S=$(remote "git rev-parse HEAD" | tr -d '\r')
    [ "$L" = "$S" ] && echo "✓ server matches local HEAD" || echo "✗ server $S ≠ local $L"
    ;;
  23) banner "MIGRATIONS"; remote "php artisan migrate:status" ;;
  24) banner "CACHES: rebuild"; remote "php artisan optimize" && log_history "Rebuilt caches" ;;
  25) banner "CACHES: clear"; remote "php artisan optimize:clear" && log_history "Cleared caches" ;;
  26) banner "COMPOSER"; remote_tty "composer install --no-dev --optimize-autoloader --no-interaction" ;;
  30)
    banner "HEALTH: $APP_URL"
    curl -sS -o /dev/null -w 'status: %{http_code}\ntotal : %{time_total}s\n' --max-time 25 "$APP_URL" || echo "✗ no response"
    echo "--- /up ---"
    curl -sS -o /dev/null -w 'status: %{http_code}\n' --max-time 25 "$APP_URL/up" || echo "✗ no response"
    ;;
  31) banner "LOGS: tail (Ctrl-C to stop)"; remote_tty "tail -n 40 -f storage/logs/laravel.log" ;;
  32) banner "LOGS: errors"; remote "grep -nE '\.(ERROR|CRITICAL|ALERT|EMERGENCY):' storage/logs/laravel.log | tail -n 25" || echo "None found." ;;
  33)
    banner "ARTISAN"
    need_param "artisan command"
    remote_tty "php artisan $PARAM2" && log_history "artisan $PARAM2"
    ;;
  34)
    banner "CONFIG"
    remote "grep -E '^(APP_ENV|APP_DEBUG|APP_URL|DB_CONNECTION|SESSION_DRIVER|CACHE_STORE|QUEUE_CONNECTION|MAIL_MAILER)=' .env | sort"
    echo "--- secret keys present (values hidden) ---"
    remote "grep -oE '^[A-Z_]*(KEY|SECRET|TOKEN|PASSWORD)[A-Z_]*=' .env | tr -d '=' | sort"
    ;;
  35) banner "DISK"; remote "du -sh . ; du -sh */ 2>/dev/null | sort -rh | head -12" ;;
  40)
    banner "SEED"
    need_param "Seeder class"
    remote_tty "php artisan db:seed --class=$PARAM2 --force" && log_history "Seeded $PARAM2"
    ;;
  41) banner "QUEUE: failed"; remote "php artisan queue:failed" ;;
  42) banner "QUEUE: retry"; remote "php artisan queue:retry all" && log_history "Retried failed jobs" ;;
  43) confirm "This takes the site down."; remote "php artisan down" && log_history "Maintenance ON" ;;
  44) remote "php artisan up" && log_history "Maintenance OFF" ;;
  45) confirm "Discard the Laravel log?"; remote ": > storage/logs/laravel.log" && log_history "Truncated log" ;;
  00) $APP_SSH -t "$REMOTE" "cd ~/$REMOTE_APP 2>/dev/null; exec \$SHELL -l" ;;
  01)
    banner "FIRST-TIME SERVER SETUP"
    cat <<STEPS
On the server:
  git clone $(git remote get-url origin 2>/dev/null || echo '<repo-url>') ~/$REMOTE_APP
  cd ~/$REMOTE_APP && cp .env.example .env   # then edit .env
  composer install --no-dev --optimize-autoloader
  php artisan key:generate
  chmod +x deploy.sh && ./deploy.sh
Point the domain's document root at ~/$REMOTE_APP/public_html.
STEPS
    ;;
  qq|q) exit 0 ;;
  *) echo "Unknown selection: $SELECTION"; exit 1 ;;
esac
