#!/bin/bash
# v2 の自動デプロイ (cron: */10 * * * * /bin/bash ~/commands/pull_v2.bash)。pull_prod.bash と同じ形。
# 変更がある時だけ pull し、v2/ の依存・フロントが変わった時だけ composer / npm を回す。
export PATH="/virtual/cleaningpossystem/node-v20.18.1-linux-x64/bin:$PATH"

REPO="/virtual/cleaningpossystem/v2/cleaning_pos"
APPDIR="$REPO/v2"
LOG="/virtual/cleaningpossystem/log/deploy_v2.log"
PHP="/usr/local/bin/php83cli"
COMPOSER="/virtual/cleaningpossystem/composer.phar"
LOCK="/virtual/cleaningpossystem/tmp/pull_v2.lock"

mkdir -p "$(dirname "$LOCK")"
exec 9>"$LOCK"
flock -n 9 || exit 0

cd "$REPO" || exit 1

git fetch -q >> "$LOG" 2>&1
CURRENT_BRANCH=$(git rev-parse --abbrev-ref HEAD)
DIFF=$(git diff HEAD "origin/$CURRENT_BRANCH" --name-only 2>&1)

if [ -z "$DIFF" ] && [ "$1" != "-f" ]; then
  exit 0
fi

echo "=== Deploy started: $(date '+%Y-%m-%d %H:%M:%S') ===" >> "$LOG"
echo "Branch: $CURRENT_BRANCH" >> "$LOG"
echo "Changed files:" >> "$LOG"
echo "$DIFF" >> "$LOG"

if ! git pull --ff-only >> "$LOG" 2>&1; then
  echo "!!! git pull failed" >> "$LOG"
  exit 1
fi

cd "$APPDIR" || exit 1

if [ "$1" = "-f" ] || echo "$DIFF" | grep -qE "^v2/composer\.(json|lock)$"; then
  echo "Running composer install..." >> "$LOG"
  $PHP "$COMPOSER" install --no-dev --optimize-autoloader --no-interaction >> "$LOG" 2>&1 || { echo "!!! composer failed" >> "$LOG"; exit 1; }
fi

if [ "$1" = "-f" ] || echo "$DIFF" | grep -qE "^v2/(resources/|package(-lock)?\.json$|vite\.config\.js$)"; then
  echo "Running npm ci + build..." >> "$LOG"
  { npm ci && npm run build; } >> "$LOG" 2>&1 || { echo "!!! npm build failed" >> "$LOG"; exit 1; }
fi

$PHP artisan migrate --force >> "$LOG" 2>&1
$PHP artisan config:cache >> "$LOG" 2>&1
$PHP artisan route:cache >> "$LOG" 2>&1
$PHP artisan view:cache >> "$LOG" 2>&1

echo "=== Deploy completed: $(date '+%Y-%m-%d %H:%M:%S') ===" >> "$LOG"
echo "" >> "$LOG"
