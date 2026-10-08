# VPS 移行ブランチ (`infra/vps-migration`)

XREA 本番(PHP 8.1 + MariaDB 10.6)と同じ構成を Docker で再現し、DB を丸ごと復元して E2E で検証する。
**XREA 本番には一切触らない。** 本番 DB ダンプはローカルコピーにのみ復元する。

## 起動
```bash
cp .env.vps.example .env.vps            # APP_KEY / DB_PASSWORD を設定
cp .secrets/backups/<日付>/prod.sql.gz deploy/initdb/10-prod.sql.gz   # 初回のみ(gitignore済み)
docker compose -f docker-compose.vps.yml --env-file .env.vps up -d --build   # http://localhost:8000
e2e/setup-test-store.sh                 # E2E 用ログイン(test@gmail.com)をコピーDBに作成
```

## E2E
```bash
cd e2e && npm ci && npx playwright install chromium
npm run test:smoke      # 全画面スモーク
npm test                # smoke + 納品書
HEADED=1 SLOWMO=300 npx playwright test   # 目視デバッグ
```
`E2E_BASE_URL` で対象を変更できる。`cleaningpossystem.shop`(本番)は書き込みテストがあるため設定で拒否される。

## 運用(VPS)
- `scheduler` コンテナが日次で 7 日超の API トークンを削除(XREA の cron 代替)。
- `deploy/backup.sh` で日次ダンプ(30日保持)。cron 例: `30 3 * * * /opt/cleaning_pos/deploy/backup.sh`。**ダンプは別ホストにも複製すること。**
- HTTPS: ドメイン取得後 `SITE_ADDRESS=pos.example.com docker compose ... --profile https up -d`(Caddy が証明書を自動取得)。
  プリンタ(wss)検証が済むまでは HTTP のまま運用する。

## 本ブランチで直した既知の不具合
- 請求画面: テーマ未設定の担当者で白画面(`:theme` の出力ミス)。
- API トークンが表示のたびに発行され続ける(本番コピーで約 11.8 万行)。セッション内で再利用に変更。
- `/login/auto`: 総当たり対策(throttle 10/分)と `Referrer-Policy: no-referrer`。URL に資格情報を載せる方式自体は端末互換のため維持(置換は端末更新が必要)。

## 未検証(帰宅後・実機)
- HTTPS 化後の EPSON プリンタ接続(wss:8043 + 証明書)。レシート印刷は E2E 対象外。
- Pusher(顧客表示)は `BROADCAST_DRIVER=log` のため未検証。
- 切替手順: 閉店後 XREA 停止 → 最終ダンプ復元 → 端末 URL 切替(XREA は温存)。
