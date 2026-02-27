# クリーニングPOS リファクタリング計画

## 背景

- 実家のクリーニング屋のレジシステム
- リモートでの実機確認が困難
- クライアントエラーの収集手段がない
- 問題発生時の原因特定が難しい

## フェーズ1: クライアントエラー収集（今日の目標）

### 1.1 サーバー側実装

- [x] `client_errors` テーブル作成（マイグレーション）
- [x] `ClientError` モデル作成
- [x] `Api\ClientErrorController` 作成
- [x] APIエンドポイント `POST /api/client-error`

**テーブル設計:**
```
client_errors
├── id
├── error_type (error|warn|unhandled)
├── message (TEXT)
├── stack (TEXT, nullable)
├── url (現在のURL)
├── user_agent
├── manager_id (nullable)
├── extra_data (JSON, nullable) - 追加情報
├── created_at
└── updated_at
```

### 1.2 クライアント側実装

- [x] `ErrorLogger.js` ユーティリティ作成
  - グローバルエラーハンドラー（window.onerror）
  - Promise未処理エラー（unhandledrejection）
  - console.error/warn のラップ
  - サーバーへの送信（デバウンス付き）
- [x] `app.js` でErrorLoggerを初期化

### 1.3 管理画面（シンプル版）

- [ ] `/errors` ページで直近のエラー一覧表示
- [ ] フィルタリング（日付、エラータイプ）

### 1.4 エラー確認方法（暫定）

```bash
# tinker でエラー一覧確認
php artisan tinker
>>> App\Models\ClientError::latest()->take(10)->get()

# API経由で確認（要認証）
GET /api/client-errors
GET /api/client-errors?error_type=error
GET /api/client-errors?date=2026-02-27
```

---

## フェーズ2: レシート印刷周りのリファクタリング（次回以降）

- ReceiptPrinter.vue の分離
  - 接続管理を独立したクラスに
  - 印刷データ生成を独立したクラスに
- エラーハンドリングの統一
- テスト用のモック対応

## フェーズ3: 全体的なコード品質改善（次回以降）

- TypeScript導入検討
- コンポーネントの責務分離
- API呼び出しの共通化
