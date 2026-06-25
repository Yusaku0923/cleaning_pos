# 納品商品マスタの追加・編集 設計

## 目的

納品管理（`/delivery`）の商品マスタ（`DeliveryProduct`）を、これまで MySQL への手動 INSERT で行っていた追加・編集を、画面から操作できるようにする。

## スコープ

- 対象エンティティ: `DeliveryProduct` のみ
- 操作: 追加・編集（**削除は対象外**）
- 入り口: `/delivery` の顧客カードから、その顧客の商品一覧へ
- 部署・顧客の管理は対象外（既存の部署にぶら下げる前提）

## データ構造（前提）

```
DeliveryCustomer (name, email, registration_number)
  └ DeliveryDepartment (name, sort_order)
      └ DeliveryProduct (name, unit_price:int, tax_rate:decimal(4,2), sort_order)
```

## ルート（`routes/web.php`・`auth` 内 `delivery` prefix）

| メソッド | パス | アクション | 名前 |
|---|---|---|---|
| GET | `delivery/{customer}/products` | `index` | `delivery.products.index` |
| GET | `delivery/{customer}/products/create` | `create` | `delivery.products.create` |
| POST | `delivery/{customer}/products` | `store` | `delivery.products.store` |
| GET | `delivery/{customer}/products/{product}/edit` | `edit` | `delivery.products.edit` |
| PUT | `delivery/{customer}/products/{product}` | `update` | `delivery.products.update` |

`{customer}`=`DeliveryCustomer`、`{product}`=`DeliveryProduct` をルートモデルバインディング。
商品がその顧客の部署に属するかをコントローラで検証し、属さない場合は 404。

## コントローラ（新規 `app/Http/Controllers/DeliveryProductController.php`）

`DeliveryNoteController` から分離する。

- `index(DeliveryCustomer $customer)` — `$customer->departments()->with('products')` を一覧表示
- `create(DeliveryCustomer $customer)` — 共有フォーム表示。部署プルダウン用に顧客の部署を渡す。`?department_id=` で初期選択
- `store(Request, DeliveryCustomer $customer)` — バリデーション後、`sort_order` 未指定なら部署内 `max(sort_order)+1` を自動採番して作成
- `edit(DeliveryCustomer $customer, DeliveryProduct $product)` — 商品が顧客に属すか検証してフォーム表示
- `update(Request, DeliveryCustomer $customer, DeliveryProduct $product)` — 検証後に更新

## バリデーション

| 項目 | ルール |
|---|---|
| `delivery_department_id` | required / exists:delivery_departments,id / **その顧客に属すること** |
| `name` | required, string, max:255 |
| `unit_price` | required, integer, min:0 |
| `tax_rate` | required, in:0.10,0.08（10% / 8% 軽減税率） |
| `sort_order` | nullable, integer（未指定は自動採番） |

## 画面（Blade）

- **一覧** `resources/views/delivery/products/index.blade.php`
  部署ごとにカード化し、商品テーブル（名前 / 単価 / 税率 / 並び順 / 「編集」）を表示。
  部署ごとに「＋商品を追加」ボタン（その部署を初期選択した create へ）。
  顧客に部署が無い場合は「部署が登録されていません」と案内。
- **フォーム** `resources/views/delivery/products/form.blade.php`（create/edit 共用）
  部署 select・商品名・単価・税率 select（10%/8%）・並び順。`@error` でエラー表示。

## 入り口

`resources/views/delivery/index.blade.php` の各顧客カードに「商品管理」ボタンを追加し
`delivery.products.index` へリンク。

## テスト（`tests/Feature/DeliveryProductControllerTest.php`）

既存の `DeliveryNoteControllerTest` と同形式。

- 一覧が表示できる
- 商品を追加できる（DB に保存され、`sort_order` が自動採番される）
- 商品を更新できる
- 他顧客の部署 ID を指定すると弾かれる（バリデーションエラー）
- 必須・型のバリデーションエラー（name 空 / unit_price 非数値 / tax_rate 範囲外）
