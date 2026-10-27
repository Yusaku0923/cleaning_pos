#!/bin/bash
# v2 の自動デプロイ (cron: */10 * * * * /bin/bash ~/commands/pull_v2.bash)。pull_prod.bash と同じ形。
# 変更がある時だけ pull し、v2/ の依存・フロントが変わった時だけ composer / npm を回す。
# -f: 変更がなくても composer / npm build / migrate をすべて回す。
#
# 失敗したとき:
# - pull より前 (cd / git fetch / rev-parse / diff / pull) の失敗は、ファイルが何も変わっていないので
#   ログに "!!! <手順> failed (exit N)" を書いて 0 以外で終わるだけ。失敗の印は残さない (次の cron が普通にやり直す)。
# - pull より後 (composer / npm / artisan) の失敗は、印 ($FAILED) に「まだ終わっていない手順」(composer・npm) を残す。
#   次の実行では、その手順と artisan (migrate・cache) だけをやり直す (pull 済みで差分がなくなっても流れるように)。
#   同じ失敗を 10 分ごとに繰り返さないよう、印がある間は待ち時間を 10・20・40・80・160・最大 360 分と延ばす。
#   新しい commit が来た (差分がある) か -f のときは待たずにやり直す。成功すると印を消す。
# パスは環境変数で差し替えられる (テスト用。cron では既定のまま)。
export PATH="${PULL_V2_NODE_BIN:-/virtual/cleaningpossystem/node-v20.18.1-linux-x64/bin}:$PATH"

REPO="${PULL_V2_REPO:-/virtual/cleaningpossystem/v2/cleaning_pos}"
APPDIR="$REPO/v2"
LOG="${PULL_V2_LOG:-/virtual/cleaningpossystem/log/deploy_v2.log}"
PHP="${PULL_V2_PHP:-/usr/local/bin/php83cli}"
COMPOSER="${PULL_V2_COMPOSER:-/virtual/cleaningpossystem/composer.phar}"
TMPDIR_V2="${PULL_V2_TMP:-/virtual/cleaningpossystem/tmp}"
LOCK="$TMPDIR_V2/pull_v2.lock"
FAILED="$TMPDIR_V2/pull_v2.failed"
NOW="${PULL_V2_NOW:-$(date +%s)}"   # テストで時刻を進めるため

mkdir -p "$TMPDIR_V2" "$(dirname "$LOG")"
exec 9>"$LOCK"
flock -n 9 || exit 0

stamp() { date '+%Y-%m-%d %H:%M:%S'; }

# pull より前の失敗: 何も変えていないので印は残さない
prefail() {
  local step="$1" code="$2"
  echo "!!! $step failed (exit $code): $(stamp) (nothing was changed; next run tries again)" >> "$LOG"
  echo "" >> "$LOG"
  echo "pull_v2: $step failed (exit $code). See $LOG" >&2
  exit 1
}

# pull より後の失敗: まだ終わっていない手順と回数を印に残す
fail() {
  local step="$1" code="$2"
  local attempts=$((PREV_ATTEMPTS + 1))
  {
    echo "step=$step"
    echo "time=$NOW"
    echo "attempts=$attempts"
    echo "need_composer=$NEED_COMPOSER"
    echo "need_npm=$NEED_NPM"
  } > "$FAILED"
  echo "!!! $step failed (exit $code): $(stamp)" >> "$LOG"
  echo "!!! Deploy FAILED (attempt $attempts); next run retries: $(pending) + artisan, after $(backoff_min "$attempts") min or on a new commit (marker: $FAILED)" >> "$LOG"
  echo "" >> "$LOG"
  echo "pull_v2: $step failed (exit $code). See $LOG" >&2
  exit 1
}

pending() {
  local p=""
  [ "$NEED_COMPOSER" = 1 ] && p="$p composer"
  [ "$NEED_NPM" = 1 ] && p="$p npm"
  echo "${p:- (none)}"
}

# 失敗 n 回目のあとの待ち時間 (分): 10, 20, 40, 80, 160, 360, 360, ...
backoff_min() {
  local n="$1" m=10 i=1
  while [ "$i" -lt "$n" ] && [ "$m" -lt 360 ]; do m=$((m * 2)); i=$((i + 1)); done
  [ "$m" -gt 360 ] && m=360
  echo "$m"
}

prerun() {
  local step="$1"; shift
  "$@" >> "$LOG" 2>&1 || prefail "$step" $?
}

run() {
  local step="$1"; shift
  "$@" >> "$LOG" 2>&1 || fail "$step" $?
}

FORCE=""
[ "$1" = "-f" ] && FORCE=1

cd "$REPO" || prefail "cd $REPO" $?

prerun "git fetch" git fetch -q
CURRENT_BRANCH=$(git rev-parse --abbrev-ref HEAD 2>>"$LOG") || prefail "git rev-parse" $?
DIFF=$(git diff HEAD "origin/$CURRENT_BRANCH" --name-only 2>>"$LOG") || prefail "git diff" $?

NEED_COMPOSER=0
NEED_NPM=0
PREV_ATTEMPTS=0
PREV_STEP=""
if [ -f "$FAILED" ]; then
  # 印は source せずに読む (旧い形式 "<手順> <日時>" の印でも動くように。その場合は全部やり直す)
  field() { sed -n "s/^$1=//p" "$FAILED" | head -1; }
  if grep -q '^attempts=' "$FAILED"; then
    PREV_STEP=$(field step)
    PREV_ATTEMPTS=$(field attempts | tr -cd '0-9')
    time=$(field time | tr -cd '0-9')
    [ "$(field need_composer)" = 1 ] && NEED_COMPOSER=1
    [ "$(field need_npm)" = 1 ] && NEED_NPM=1
  else
    PREV_STEP=$(head -1 "$FAILED")
    PREV_ATTEMPTS=1
    time=0
    NEED_COMPOSER=1
    NEED_NPM=1
  fi
  PREV_ATTEMPTS=${PREV_ATTEMPTS:-1}
  if [ -z "$DIFF" ] && [ -z "$FORCE" ]; then
    wait_s=$(( $(backoff_min "$PREV_ATTEMPTS") * 60 ))
    # cron のずれで数秒早く起きても待ちすぎないよう、30 秒の余裕を見る
    if [ $((NOW - ${time:-0} + 30)) -lt "$wait_s" ]; then
      exit 0
    fi
  fi
elif [ -z "$DIFF" ] && [ -z "$FORCE" ]; then
  exit 0
fi

if [ -n "$FORCE" ]; then
  NEED_COMPOSER=1
  NEED_NPM=1
fi
echo "$DIFF" | grep -qE "^v2/composer\.(json|lock)$" && NEED_COMPOSER=1
echo "$DIFF" | grep -qE "^v2/(resources/|package(-lock)?\.json$|vite\.config\.js$)" && NEED_NPM=1

echo "=== Deploy started: $(stamp) ===" >> "$LOG"
echo "Branch: $CURRENT_BRANCH" >> "$LOG"
if [ -n "$PREV_STEP" ] || [ "$PREV_ATTEMPTS" -gt 0 ]; then
  echo "Previous deploy failed at '$PREV_STEP' ($PREV_ATTEMPTS time(s)); retrying:$(pending) + artisan" >> "$LOG"
fi
echo "Changed files:" >> "$LOG"
echo "$DIFF" >> "$LOG"

if [ -n "$DIFF" ]; then
  prerun "git pull" git pull --ff-only
fi

cd "$APPDIR" || fail "cd $APPDIR" $?

if [ "$NEED_COMPOSER" = 1 ]; then
  echo "Running composer install..." >> "$LOG"
  run "composer install" "$PHP" "$COMPOSER" install --no-dev --optimize-autoloader --no-interaction
  NEED_COMPOSER=0
fi

if [ "$NEED_NPM" = 1 ]; then
  echo "Running npm ci + build..." >> "$LOG"
  run "npm ci" npm ci
  run "npm run build" npm run build
  NEED_NPM=0
fi

run "artisan migrate" "$PHP" artisan migrate --force
run "artisan config:cache" "$PHP" artisan config:cache
run "artisan route:cache" "$PHP" artisan route:cache
run "artisan view:cache" "$PHP" artisan view:cache

rm -f "$FAILED"
echo "=== Deploy completed: $(stamp) ===" >> "$LOG"
echo "" >> "$LOG"
