# 移行・UX刷新 進捗メモ（2026-10-08 時点）

## ブランチ
| ブランチ | 役割 | E2E |
|---|---|---|
| `feature/launch` | **本番(XREA)。push すると cron が10分毎に自動デプロイする。** 触らない | – |
| `infra/vps-migration` | VPS移行（Docker/Caddy/バックアップ/E2E/不具合修正） | 47件成功 |
| `ux/refresh` | 上記 + UX刷新（第1弾: ログイン・メニュー） | 52件成功 |

`feature/launch` にはローカルで1コミット（e2e/node_modules をgit管理から外す）があるが **未push**。

## 完了
- XREA相当の環境（PHP8.1 + MariaDB10.6）をDockerで再現し、本番ダンプを復元して動作確認
- E2E: 認証 / 全画面スモーク / 注文→預り→入金→削除 / 請求 / 納品書 / UX
- 修正: 請求画面の白画面（テーマ未設定の担当者）、APIトークンの増殖（セッション内再利用・7日超のみ削除）、`/login/auto` の総当たり制限と Referrer-Policy
- 運用部品: 日次トークン掃除、`deploy/backup.sh`、Caddy（profile https）

## 未了・帰宅後
1. **プリンタ実機: HTTPS(wss:8043)で印刷できるか**（最大リスク。結果次第で HTTPS 化の可否が決まる）
2. ドメイン取得（Cloudflare Registrar 年1,000円強）→ Cloudflare Tunnel か Caddy で公開
3. VPS契約（日本リージョン1〜2GB）→ 構築 → 最終ダンプ復元 → 閉店後に切替（XREAは残す）
4. Pusher（顧客表示）は未検証。並行試験中は dev アプリのキーを使い、店舗に影響させない
5. UX第2弾の対象画面を決める（注文画面は印刷が絡むため実機確認後が安全）

## 本番へ先に入れたい修正（任意）
請求画面の白画面・トークン増殖の修正は `infra/vps-migration` にのみある。本番に出すなら該当コミットを `feature/launch` へcherry-pick（= 自動デプロイされる点に注意）。
