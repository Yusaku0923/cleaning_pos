#!/bin/bash
# 切替のとき: v2 の Pusher を、旧の本番と同じアプリ (本番キー) に替える。
#   bash ~/v2/cleaning_pos/deploy/xrea/use_prod_pusher.bash
# 1. v2 の .env を ~/backup/v2_env_<日時> に控える
# 2. 旧の本番の .env から PUSHER_APP_ID / KEY / SECRET / CLUSTER を写し、VITE_PUSHER_APP_KEY / CLUSTER も同じ値にする
# 3. pull_v2.bash -f で作り直す (VITE_* はビルドのときに JS に埋め込まれるため) → config:cache も pull_v2 が流す
# 4. ビルドした JS に新しいキーが入ったかを確かめる
# 戻すとき: cp -p ~/backup/v2_env_<日時> ~/v2/cleaning_pos/v2/.env && /bin/bash ~/commands/pull_v2.bash -f
# 鍵の値は画面に出さない (先頭 4 文字だけ)。
# テスト用: SRC_ENV / DST_ENV / BACKUP_DIR / PULL_V2_TMP / PULL_V2_CMD を差し替え、NO_DEPLOY=1 で 3・4 を飛ばせる。
set -u
SRC="${SRC_ENV:-/virtual/cleaningpossystem/prod/cleaning_pos/.env}"
DST="${DST_ENV:-/virtual/cleaningpossystem/v2/cleaning_pos/v2/.env}"
BACKUP_DIR="${BACKUP_DIR:-/virtual/cleaningpossystem/backup}"
BUILD_DIR="$(dirname "$DST")/public/build"

# .env の値を読む (前後の引用符を外す)。無ければ空
val() {
  sed -n "s/^$1=//p" "$2" | tail -1 | sed -e 's/^"\(.*\)"$/\1/' -e "s/^'\(.*\)'$/\1/"
}
mask() { local v="$1"; [ -n "$v" ] && echo "${v:0:4}..." || echo "(空)"; }

[ -f "$SRC" ] || { echo "NG: $SRC がありません" >&2; exit 1; }
[ -f "$DST" ] || { echo "NG: $DST がありません" >&2; exit 1; }

ID=$(val PUSHER_APP_ID "$SRC")
KEY=$(val PUSHER_APP_KEY "$SRC")
SECRET=$(val PUSHER_APP_SECRET "$SRC")
CLUSTER=$(val PUSHER_APP_CLUSTER "$SRC")
for v in ID KEY SECRET CLUSTER; do
  [ -n "${!v}" ] || { echo "NG: 旧の本番の .env に PUSHER_APP_$v がありません。何も変えていません。" >&2; exit 1; }
done

OLD_KEY=$(val PUSHER_APP_KEY "$DST")
echo "v2 の今のキー: $(mask "$OLD_KEY") / 旧の本番のキー: $(mask "$KEY") (cluster $CLUSTER)"
if [ "$OLD_KEY" = "$KEY" ] && [ "$(val VITE_PUSHER_APP_KEY "$DST")" = "$KEY" ]; then
  echo "すでに本番のキーです。.env は変えません。"
else
  mkdir -p "$BACKUP_DIR"
  B="$BACKUP_DIR/v2_env_$(date '+%Y%m%d%H%M%S')"
  cp -p "$DST" "$B" && chmod 600 "$B" || exit 1
  echo "控え: $B"

  TMP="$DST.tmp.$$"
  cp -p "$DST" "$TMP" || exit 1
  set_key() { # <名前> <値>: 行があれば置き換え、無ければ末尾に足す
    if grep -q "^$1=" "$TMP"; then
      awk -v k="$1" -v v="$2" 'BEGIN { FS = OFS = "=" } index($0, k "=") == 1 { print k "=" v; next } { print }' "$TMP" > "$TMP.2" && mv "$TMP.2" "$TMP"
    else
      echo "$1=$2" >> "$TMP"
    fi
  }
  set_key PUSHER_APP_ID "$ID"
  set_key PUSHER_APP_KEY "$KEY"
  set_key PUSHER_APP_SECRET "$SECRET"
  set_key PUSHER_APP_CLUSTER "$CLUSTER"
  set_key VITE_PUSHER_APP_KEY "$KEY"
  set_key VITE_PUSHER_APP_CLUSTER "$CLUSTER"
  chmod 600 "$TMP"
  mv "$TMP" "$DST" || exit 1
  echo "書き換えました: $DST"
fi
for k in PUSHER_APP_ID PUSHER_APP_KEY PUSHER_APP_SECRET PUSHER_APP_CLUSTER VITE_PUSHER_APP_KEY VITE_PUSHER_APP_CLUSTER; do
  echo "  $k=$(mask "$(val $k "$DST")")"
done

[ "${NO_DEPLOY:-}" = 1 ] && exit 0

# cron の pull_v2 が動いている (ロックを持っている) と、pull_v2.bash -f は何もせずに終わる。
# ロックが空くのを待ってから流し、キーが入っていなければ最大 3 回までやり直す。
LOCK="${PULL_V2_TMP:-/virtual/cleaningpossystem/tmp}/pull_v2.lock"
PULL_V2_CMD="${PULL_V2_CMD:-/virtual/cleaningpossystem/commands/pull_v2.bash}"
wait_lock() {
  [ -e "$LOCK" ] || return 0
  if ! flock -n "$LOCK" true 2>/dev/null; then
    echo "cron の自動デプロイが動いています。終わるまで待ちます (最大 15 分)..."
    flock -w 900 "$LOCK" true || { echo "NG: 15 分待っても自動デプロイが終わりません。tail -50 ~/log/deploy_v2.log を見てください。" >&2; exit 1; }
  fi
}
for try in 1 2 3; do
  wait_lock
  echo "作り直します (pull_v2.bash -f。数分かかります)..."
  if ! /bin/bash "$PULL_V2_CMD" -f; then
    echo "NG: pull_v2.bash -f が失敗しました。tail -50 ~/log/deploy_v2.log を見てください。" >&2
    exit 1
  fi
  if grep -rqF "$KEY" "$BUILD_DIR"/assets/*.js 2>/dev/null; then
    echo "OK: ビルドした JS に本番のキー ($(mask "$KEY")) が入っています。"
    exit 0
  fi
  [ "$try" -lt 3 ] && { echo "まだキーが入っていません (自動デプロイと重なった)。1 分待ってやり直します ($try/3)..."; sleep 60; }
done
echo "NG: ビルドした JS に本番のキーが見つかりません ($BUILD_DIR)。1〜2 分待ってから、もう一度 use_prod_pusher.bash を流してください。" >&2
exit 1
