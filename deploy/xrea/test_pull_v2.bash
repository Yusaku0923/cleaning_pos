#!/bin/bash
# pull_v2.bash の失敗時の動きを確かめるテスト (使い捨ての git リポジトリと、偽の php / npm で流す。本物には触れない)。
#   bash deploy/xrea/test_pull_v2.bash        (flock が要るので XREA か Linux で)
# 確かめること: pull 前の失敗は印を残さない / pull 後の失敗は残りの手順だけ印に残す / 待ち時間 / 成功で印を消す
set -u
SCRIPT="$(cd "$(dirname "$0")" && pwd)/pull_v2.bash"
W=$(mktemp -d)
trap 'rm -rf "$W"' EXIT
PASS=0; FAILS=0
ok() { if eval "$2"; then PASS=$((PASS+1)); echo "ok   - $1"; else FAILS=$((FAILS+1)); echo "FAIL - $1  [$2]"; fi; }

# 偽の php / npm: 呼ばれた引数を CALLS に書き、FAIL_ON に一致したら exit 7
mkdir -p "$W/bin"
for t in php npm; do
  cat > "$W/bin/$t" <<EOF
#!/bin/bash
echo "$t \$*" >> "$W/calls"
if [ -n "\${FAIL_ON:-}" ] && echo "$t \$*" | grep -qE "\$FAIL_ON"; then exit 7; fi
exit 0
EOF
  chmod +x "$W/bin/$t"
done

git init -q --bare "$W/origin.git"
git clone -q "$W/origin.git" "$W/work" 2>/dev/null
cd "$W/work" && git checkout -q -b main
mkdir -p v2/resources && echo a > v2/resources/a.js && echo '{}' > v2/composer.json && echo x > README
git add -A && git -c user.email=t@t -c user.name=t commit -qm init && git push -q origin main 2>/dev/null
git clone -q -b main "$W/origin.git" "$W/repo" 2>/dev/null

commit() { # <file> : work で file を変えて push
  cd "$W/work" && echo "$RANDOM" >> "$1" && git add -A && git -c user.email=t@t -c user.name=t commit -qm "c $1" && git push -q origin main 2>/dev/null
}

T0=1800000000
pv() { # <now offset sec> [args] : pull_v2 を流して終了コードを RC に
  : > "$W/calls"
  PULL_V2_REPO="$W/repo" PULL_V2_LOG="$W/log" PULL_V2_PHP="$W/bin/php" PULL_V2_COMPOSER=composer.phar \
  PULL_V2_TMP="$W/tmp" PULL_V2_NODE_BIN="$W/bin" PULL_V2_NOW=$((T0 + $1)) bash "$SCRIPT" "${@:2}" 2>/dev/null
  RC=$?
}
called() { grep -qE "$1" "$W/calls"; }
M="$W/tmp/pull_v2.failed"

echo "# 1. 変更なし → 何もしない"
pv 0; ok "exit 0" '[ $RC = 0 ]'; ok "no calls" '[ ! -s "$W/calls" ]'

echo "# 2. pull 前の失敗 (fetch) → exit 1、印なし"
git -C "$W/repo" remote set-url origin "$W/nowhere.git"
pv 0; ok "exit 1" '[ $RC = 1 ]'; ok "no marker" '[ ! -f "$M" ]'; ok "logged" 'grep -q "!!! git fetch failed" "$W/log"'
ok "nothing built" '[ ! -s "$W/calls" ]'
git -C "$W/repo" remote set-url origin "$W/origin.git"

echo "# 3. resources だけ変更 → npm と artisan、composer なし"
commit v2/resources/a.js
pv 0; ok "exit 0" '[ $RC = 0 ]'; ok "npm build" 'called "npm run build"'; ok "no composer" '! called "composer.phar"'
ok "migrate" 'called "artisan migrate"'; ok "completed" 'tail -3 "$W/log" | grep -q "Deploy completed"'

echo "# 4. composer + resources 変更で npm build が失敗 → 印に npm だけ残る"
commit v2/composer.json; commit v2/resources/a.js
FAIL_ON="npm run build" pv 0; ok "exit 1" '[ $RC = 1 ]'; ok "composer ran" 'called "composer.phar install"'
ok "marker" '[ -f "$M" ]'; ok "need_npm=1" 'grep -q "^need_npm=1" "$M"'; ok "need_composer=0" 'grep -q "^need_composer=0" "$M"'
ok "attempts=1" 'grep -q "^attempts=1" "$M"'

echo "# 5. 直後 (1 分後) → 待ち時間中なので何もしない"
pv 60; ok "exit 0" '[ $RC = 0 ]'; ok "no calls" '[ ! -s "$W/calls" ]'

echo "# 6. 10 分後 → npm と artisan だけやり直す。また失敗 → attempts=2"
FAIL_ON="npm run build" pv 600; ok "exit 1" '[ $RC = 1 ]'; ok "npm retried" 'called "npm ci"'; ok "no composer" '! called "composer.phar"'
ok "attempts=2" 'grep -q "^attempts=2" "$M"'

echo "# 7. さらに 10 分後 → 2 回目の待ち (20 分) の途中なので何もしない"
pv 1200; ok "skipped" '[ $RC = 0 ] && [ ! -s "$W/calls" ]'

echo "# 8. さらに 20 分後 → やり直して成功、印が消える"
pv 1800; ok "exit 0" '[ $RC = 0 ]'; ok "npm" 'called "npm run build"'; ok "no composer" '! called "composer.phar"'
ok "marker removed" '[ ! -f "$M" ]'; ok "completed" 'tail -3 "$W/log" | grep -q "Deploy completed"'

echo "# 9. 待ち時間中でも新しい commit が来たらすぐやり直す"
FAIL_ON="artisan migrate" pv 2000 -f; ok "migrate failed" '[ $RC = 1 ] && grep -q "^need_npm=0" "$M" && grep -q "^need_composer=0" "$M"'
commit README
pv 2060; ok "retried on new commit" '[ $RC = 0 ] && called "artisan migrate" && ! called "npm" && [ ! -f "$M" ]'

echo "# 10. 旧い形式の印 (\"<手順> <日時>\") → source せず、全部やり直す"
echo "npm run build 2026-10-27 12:00:00" > "$M"
pv 3000; ok "full retry" '[ $RC = 0 ] && called "composer.phar install" && called "npm run build" && [ ! -f "$M" ]'

echo "# 11. -f は待ち時間中でも流す"
FAIL_ON="artisan config:cache" pv 4000; ok "no diff, no marker → nothing" '[ $RC = 0 ] && [ ! -s "$W/calls" ]'
FAIL_ON="artisan config:cache" pv 4000 -f; ok "fail" '[ $RC = 1 ]'
pv 4060 -f; ok "-f runs during back-off" '[ $RC = 0 ] && called "npm run build" && [ ! -f "$M" ]'

echo "# 12. pull の失敗 (fast-forward できない) → 印なし、ビルドしない"
commit README
cd "$W/repo" && echo local >> README && git -c user.email=t@t -c user.name=t commit -qam local
pv 5000; ok "exit 1, no marker, no build" '[ $RC = 1 ] && [ ! -f "$M" ] && ! called "npm" && grep -q "!!! git pull failed" "$W/log"'

echo "passed $PASS, failed $FAILS"
[ "$FAILS" = 0 ]
