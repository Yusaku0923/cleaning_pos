#!/bin/bash
# v2 の自動デプロイ (cron: */10 * * * * /bin/bash ~/commands/pull_v2.bash)。pull_prod.bash と同じ形。
# 変更がある時だけ pull し、v2/ の依存・フロントが変わった時だけ composer / npm を回す。
# -f: 変更がなくても composer / npm build / migrate をすべて回す。
# どの手順も失敗したらそこで止め、ログに "!!! <手順> failed (exit N)" を書いて 0 以外で終わる。
# 失敗した印 ($FAILED) が残っている間は、次の実行で -f と同じくすべてやり直す
# (pull 済みだと差分がなくなり、失敗した migrate などが二度と流れないため)。
export PATH="/virtual/cleaningpossystem/node-v20.18.1-linux-x64/bin:$PATH"

REPO="/virtual/cleaningpossystem/v2/cleaning_pos"
APPDIR="$REPO/v2"
LOG="/virtual/cleaningpossystem/log/deploy_v2.log"
PHP="/usr/local/bin/php83cli"
COMPOSER="/virtual/cleaningpossystem/composer.phar"
LOCK="/virtual/cleaningpossystem/tmp/pull_v2.lock"
FAILED="/virtual/cleaningpossystem/tmp/pull_v2.failed"

mkdir -p "$(dirname "$LOCK")" "$(dirname "$LOG")"
exec 9>"$LOCK"
flock -n 9 || exit 0

fail() {
  local step="$1" code="$2"
  echo "!!! $step failed (exit $code): $(date '+%Y-%m-%d %H:%M:%S')" >> "$LOG"
  echo "!!! Deploy FAILED; next run retries everything (marker: $FAILED)" >> "$LOG"
  echo "" >> "$LOG"
  echo "$step $(date '+%Y-%m-%d %H:%M:%S')" > "$FAILED"
  echo "pull_v2: $step failed (exit $code). See $LOG" >&2
  exit 1
}

# 手順を実行し、出力をログへ。失敗したら fail
run() {
  local step="$1"; shift
  "$@" >> "$LOG" 2>&1 || fail "$step" $?
}

FORCE=""
[ "$1" = "-f" ] && FORCE=1

cd "$REPO" || fail "cd $REPO" $?

run "git fetch" git fetch -q
CURRENT_BRANCH=$(git rev-parse --abbrev-ref HEAD 2>>"$LOG") || fail "git rev-parse" $?
DIFF=$(git diff HEAD "origin/$CURRENT_BRANCH" --name-only 2>>"$LOG") || fail "git diff" $?

if [ -z "$DIFF" ] && [ -z "$FORCE" ] && [ ! -f "$FAILED" ]; then
  exit 0
fi

echo "=== Deploy started: $(date '+%Y-%m-%d %H:%M:%S') ===" >> "$LOG"
echo "Branch: $CURRENT_BRANCH" >> "$LOG"
if [ -f "$FAILED" ]; then
  echo "Previous deploy failed ($(cat "$FAILED")); running every step again" >> "$LOG"
  FORCE=1
fi
echo "Changed files:" >> "$LOG"
echo "$DIFF" >> "$LOG"

if [ -n "$DIFF" ]; then
  run "git pull" git pull --ff-only
fi

cd "$APPDIR" || fail "cd $APPDIR" $?

if [ -n "$FORCE" ] || echo "$DIFF" | grep -qE "^v2/composer\.(json|lock)$"; then
  echo "Running composer install..." >> "$LOG"
  run "composer install" "$PHP" "$COMPOSER" install --no-dev --optimize-autoloader --no-interaction
fi

if [ -n "$FORCE" ] || echo "$DIFF" | grep -qE "^v2/(resources/|package(-lock)?\.json$|vite\.config\.js$)"; then
  echo "Running npm ci + build..." >> "$LOG"
  run "npm ci" npm ci
  run "npm run build" npm run build
fi

run "artisan migrate" "$PHP" artisan migrate --force
run "artisan config:cache" "$PHP" artisan config:cache
run "artisan route:cache" "$PHP" artisan route:cache
run "artisan view:cache" "$PHP" artisan view:cache

rm -f "$FAILED"
echo "=== Deploy completed: $(date '+%Y-%m-%d %H:%M:%S') ===" >> "$LOG"
echo "" >> "$LOG"
