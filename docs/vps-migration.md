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

## 未検証(帰宅後・実機)
- HTTPS 化後の EPSON プリンタ接続(wss:8043 + 証明書)。レシート印刷は E2E 対象外。
- Pusher(顧客表示)は `BROADCAST_DRIVER=log` のため未検証。
- 切替手順: 閉店後 XREA 停止 → 最終ダンプ復元 → 端末 URL 切替(XREA は温存)。
