#!/bin/bash
# freeze_legacy.bash で足した「メンテナンス中」(503) を外して、旧 (本番) を元どおり開ける。
#   bash ~/v2/cleaning_pos/deploy/xrea/unfreeze_legacy.bash [Web ルート]
#   Web ルートの既定は旧の本番 ~/public_html/cleaningpossystem.shop (freeze_legacy.bash と同じ)。
# 印の行から印の行までと、そのあとの空行 1 行を消す (= freeze の前と同じ中身)。
# 結果を freeze の前の控え (.htaccess.before-freeze) と比べて、同じなら控えを消す。
set -u
DOCROOT="${1:-/virtual/cleaningpossystem/public_html/cleaningpossystem.shop}"
DOCROOT="${DOCROOT%/}"
HT="$DOCROOT/.htaccess"
SAVED="$HT.before-freeze"
BEGIN="# >>> freeze_legacy.bash (メンテナンス中) >>>"
END="# <<< freeze_legacy.bash <<<"

[ -f "$HT" ] || { echo "NG: $HT がありません" >&2; exit 1; }

if ! grep -qF "$BEGIN" "$HT"; then
  echo "止めていません ($HT に印がありません)。何もしません。"
  exit 0
fi
if ! grep -qF "$END" "$HT"; then
  echo "NG: 終わりの印がありません。$SAVED を見て手で戻してください: cp -p $SAVED $HT" >&2
  exit 1
fi

TMP="$HT.unfreeze-tmp.$$"
awk -v b="$BEGIN" -v e="$END" '
  $0 == b { skip = 1; next }
  skip && $0 == e { skip = 0; drop_blank = 1; next }
  skip { next }
  drop_blank && $0 == "" { drop_blank = 0; next }
  { drop_blank = 0; print }
' "$HT" > "$TMP" || { rm -f "$TMP"; exit 1; }
chmod --reference="$HT" "$TMP" 2>/dev/null
mv "$TMP" "$HT" || exit 1

echo "開けました: $HT"
if [ -f "$SAVED" ]; then
  if cmp -s "$HT" "$SAVED"; then
    echo "freeze の前と同じ中身です (控え $SAVED は消しました)。"
    rm -f "$SAVED"
  else
    echo "注意: freeze の前の控え ($SAVED) と中身が違います。止めている間に .htaccess を変えましたか?"
    echo "      差分: diff $SAVED $HT"
  fi
fi
echo "確認: curl -s -o /dev/null -w '%{http_code}\n' http://<ドメイン>/login   → 200"
