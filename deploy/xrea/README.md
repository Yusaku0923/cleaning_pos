# v2 の XREA 配置

v2 (Laravel 12 / Vue 3) を XREA (s239.xrea.com, ユーザー cleaningpossystem) で動かす手順と現状。
本番 (`~/prod`, `~/public_html/cleaningpossystem.shop`, `~/commands/pull_prod.bash`, DB `cleaningpossystem`) には触れない。

## 現状 (2026-10-23)

| 項目 | 値 |
|---|---|
| URL | **http://dev.cleaningpossystem.shop** (一時。下の「URL について」) |
| Web ルート | `~/public_html/dev.cleaningpossystem.shop/` (symlink + `.htaccess`) |
| アプリ | `~/v2/cleaning_pos` (ブランチ `rebuild/v2` の clone。アプリ本体は `v2/` 以下) |
| PHP | CLI `/usr/local/bin/php83cli`、Web は `.htaccess` の `FcgidWrapper ... php83.fcgi` |
| Node | `~/node-v20.18.1-linux-x64` (Vite のビルド用。PATH には入れていない) |
| DB | `cleaningpossystem_dev` (一時。owner が `cleaningpossystem_v2` を作ったら `.env` を差し替える) |
| アクセス制御 | 全員 Basic 認証 (ユーザー `asahiya`、`~/.htpasswd_v2`、bcrypt)。店の IP が固定と分かったら `htaccess.v2` に `<RequireAny> Require ip ... / Require valid-user </RequireAny>` |
| 自動デプロイ | cron `*/10 * * * * /bin/bash ~/commands/pull_v2.bash` (差分がある時だけ)。ログ `~/log/deploy_v2.log` |

### URL について

`http://cleaningpossystem.s239.xrea.com/` は XREA のサーバー設定で `http://www.cleaningpossystem.shop/` へ 301 される
(`~/public_html/cleaningpossystem.s239.xrea.com/` を作っても 301 のまま。2026-10-23 確認)。
コントロールパネルでの設定が要るため、それまでは dev のドメインを v2 に使う。

## Web ルートの中身

```
~/public_html/dev.cleaningpossystem.shop/
  .htaccess    <- deploy/xrea/htaccess.v2 のコピー (変えたら手で cp し直す)
  index.php build js img favicon.ico robots.txt  -> ~/v2/cleaning_pos/v2/public/<同名> (symlink)
```

旧 dev の symlink 一覧と `.htaccess` は `~/backup/dev_docroot_<ts>.txt` / `~/backup/dev_htaccess_<ts>` に控えてある。

**symlink を張り替えたら opcache を捨てる。** PHP の opcache は index.php を実体のパスで覚えていて、
symlink の先を変えても旧アプリを返し続ける (2026-10-23 に発生)。Web ルートに一時ファイルを置いて消す:

```bash
D=~/public_html/dev.cleaningpossystem.shop; F=_r_$RANDOM.php
echo '<?php echo getmypid(), " ", var_export(opcache_reset(), true);' > $D/$F
curl -s -u asahiya:<pw> http://dev.cleaningpossystem.shop/$F; rm -f $D/$F
```

(git pull によるファイルの更新は opcache がタイムスタンプで検知するので不要。)

## 初回の配置 (やったこと)

```bash
# 0. 控え
mkdir -p ~/backup && crontab -l > ~/backup/crontab_<ts>.txt
MYSQL_PWD=... mysqldump --single-transaction --no-tablespaces -h 127.0.0.1 -u cleaningpossystem_dev cleaningpossystem_dev | gzip > ~/backup/dev_db_<ts>.sql.gz
# 1. Node 20.18.1 (SHASUMS256 で検証して展開)
curl -LO https://nodejs.org/dist/v20.18.1/node-v20.18.1-linux-x64.tar.xz && tar -xJf node-v20.18.1-linux-x64.tar.xz -C ~
# 2. clone とビルド
git clone -b rebuild/v2 https://github.com/Yusaku0923/cleaning_pos.git ~/v2/cleaning_pos
cd ~/v2/cleaning_pos/v2
php83cli ~/composer.phar install --no-dev --optimize-autoloader
PATH=~/node-v20.18.1-linux-x64/bin:$PATH npm ci && PATH=~/node-v20.18.1-linux-x64/bin:$PATH npm run build
# 3. .env (.env.example から。APP_ENV=production, APP_DEBUG=false, APP_URL, DB_*) と鍵
php83cli artisan key:generate --force && chmod 600 .env
# 4. DB (dev DB には旧スキーマが入っていたので migrate:fresh。控えは手順 0)
php83cli artisan migrate:fresh --force
php83cli artisan config:cache && php83cli artisan route:cache && php83cli artisan view:cache
# 5. Basic 認証とWeb ルート
htpasswd -cbB ~/.htpasswd_v2 asahiya '<pw>'
cp deploy/xrea/htaccess.v2 ~/public_html/dev.cleaningpossystem.shop/.htaccess   # + symlink、opcache を捨てる
# 6. 自動デプロイ
cp deploy/xrea/pull_v2.bash ~/commands/ && (crontab -l; echo '*/10 * * * * /bin/bash ~/commands/pull_v2.bash') | crontab -
```

## 確認

```bash
curl -s -o /dev/null -w '%{http_code}' http://dev.cleaningpossystem.shop/up              # 401
curl -s -o /dev/null -w '%{http_code}' -u asahiya:<pw> http://dev.cleaningpossystem.shop/up  # 200
curl -s -u asahiya:<pw> http://dev.cleaningpossystem.shop/login | grep 'name="email"'
```

## 手で再デプロイ

```bash
/bin/bash ~/commands/pull_v2.bash -f   # 差分がなくても composer / npm / migrate / cache をやり直す
tail -50 ~/log/deploy_v2.log
```

## 端末の自動ログイン

```bash
cd ~/v2/cleaning_pos/v2
php83cli artisan device:issue 'レジ iPad'   # URL は1回だけ表示。端末のホーム画面に登録
php83cli artisan device:revoke <id>
```

## 元に戻す (v2 をやめて dev に戻す)

1. `crontab -e` で `pull_v2.bash` の行を消す (控え: `~/backup/crontab_<ts>.txt`)
2. `~/public_html/dev.cleaningpossystem.shop/` の symlink を `~/backup/dev_docroot_<ts>.txt` の通りに張り直し、`.htaccess` を `~/backup/dev_htaccess_<ts>` から戻し、opcache を捨てる
3. dev DB は `gunzip -c ~/backup/dev_db_<ts>.sql.gz | mysql ...` で戻す
