#!/usr/bin/env bash
# DB を日次ダンプし、直近 BACKUP_KEEP_DAYS 日分だけ残す。cron 例: 30 3 * * * /opt/cleaning_pos/deploy/backup.sh
set -euo pipefail
cd "$(dirname "$0")/.."
DIR="${BACKUP_DIR:-./backups}"; KEEP="${BACKUP_KEEP_DAYS:-30}"
mkdir -p "$DIR"
OUT="$DIR/db-$(date +%Y%m%d-%H%M%S).sql.gz"
docker compose -f docker-compose.vps.yml --env-file .env.vps exec -T db sh -c 'exec mariadb-dump --single-transaction --routines -u"$MARIADB_USER" -p"$MARIADB_PASSWORD" "$MARIADB_DATABASE"' | gzip > "$OUT"
gzip -t "$OUT" && echo "backup ok: $OUT ($(du -h "$OUT" | cut -f1))"
find "$DIR" -name 'db-*.sql.gz' -mtime +"$KEEP" -delete
