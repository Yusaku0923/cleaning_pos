#!/bin/bash
# 旧 (本番) を止める: Web ルートの .htaccess の先頭に「メンテナンス中」(503) の設定を足す。
#   bash ~/v2/cleaning_pos/deploy/xrea/freeze_legacy.bash [Web ルート]
#   Web ルートの既定は旧の本番 ~/public_html/cleaningpossystem.shop。
#   試すときは dev を渡す: bash freeze_legacy.bash ~/public_html/dev.cleaningpossystem.shop
# 元に戻すのは unfreeze_legacy.bash (同じ Web ルートを渡す)。
# 足す前の .htaccess を <Web ルート>/.htaccess.before-freeze と ~/backup/htaccess_<名前>_<日時> に控える。
# すでに止めてあるときは何もしない。
set -u
DOCROOT="${1:-/virtual/cleaningpossystem/public_html/cleaningpossystem.shop}"
DOCROOT="${DOCROOT%/}"
HT="$DOCROOT/.htaccess"
SAVED="$HT.before-freeze"
BEGIN="# >>> freeze_legacy.bash (メンテナンス中) >>>"
END="# <<< freeze_legacy.bash <<<"

[ -f "$HT" ] || { echo "NG: $HT がありません" >&2; exit 1; }

if grep -qF "$BEGIN" "$HT"; then
  echo "すでに止めてあります ($HT)。何もしません。"
  exit 0
fi

BACKUP_DIR=/virtual/cleaningpossystem/backup
mkdir -p "$BACKUP_DIR"
STAMP=$(date '+%Y%m%d%H%M%S')
cp -p "$HT" "$SAVED" || exit 1
cp -p "$HT" "$BACKUP_DIR/htaccess_$(basename "$DOCROOT")_$STAMP" || exit 1

TMP="$HT.freeze-tmp.$$"
{
  echo "$BEGIN"
  echo "# 外すときは unfreeze_legacy.bash。手で外すならこの印の行から下の印の行までを消す"
  echo 'ErrorDocument 503 "<!doctype html><html lang=ja><head><meta charset=utf-8><meta name=viewport content=width=device-width><title>メンテナンス中</title></head><body style=font-family:sans-serif;text-align:center;padding-top:20vh><h1>メンテナンス中です</h1><p>新しいレジに切り替えています。</p></body></html>"'
  echo "<IfModule mod_rewrite.c>"
  echo "    RewriteEngine On"
  echo "    RewriteRule ^ - [R=503,L]"
  echo "</IfModule>"
  echo "$END"
  echo ""
  cat "$HT"
} > "$TMP" || { rm -f "$TMP"; exit 1; }
chmod --reference="$HT" "$TMP" 2>/dev/null
mv "$TMP" "$HT" || exit 1

echo "止めました: $HT"
echo "控え: $SAVED / $BACKUP_DIR/htaccess_$(basename "$DOCROOT")_$STAMP"
echo "確認: curl -s -o /dev/null -w '%{http_code}\n' http://<ドメイン>/login   → 503"
