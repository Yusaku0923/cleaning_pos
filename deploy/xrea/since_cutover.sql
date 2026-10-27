-- 戻し手順で使う: 切替 (@t) のあとに v2 で作られた・変わった行の一覧。読むだけ (SELECT のみ)。
-- 使い方 (cutover.md「戻し手順」):
--   (echo "SET @t = '2026-10-29 19:30:00';"; cat ~/v2/cleaning_pos/deploy/xrea/since_cutover.sql) | v2sql > ~/backup/since_cutover.txt
-- 取り込んだ行は旧の created_at / updated_at のままなので、@t 以後の行 = v2 で入れた・変えた行。
-- deleted_at が入っている行は v2 で取り消した行 (旧では取り消しとして入れる、または入れない)。
-- 3〜5 は created_at / updated_at だけで見る。paid_at は入金確認ありのお客さんだと「期間の終わりの日」(切替より後の日) が
-- 前もって入るので、paid_at で見ると切替前の請求書・注文まで出てしまう。v2 の保存は必ず updated_at を新しくする。

SELECT '== 1. 切替後の新しい預り (注文) ==' AS '';
SELECT o.id, o.created_at, m.name AS manager, c.name AS customer, o.is_invoice, o.amount, o.reduction, o.discount,
       o.payment, o.paid_at, o.handed_at, o.invoice_id, o.note, o.deleted_at
  FROM orders o
  JOIN customers c ON c.id = o.customer_id
  JOIN managers m ON m.id = o.manager_id
 WHERE o.created_at >= @t
 ORDER BY o.id;

SELECT '== 2. 1 の品物 (タグ) ==' AS '';
SELECT i.order_id, i.id, cl.name AS clothes, cl.price, i.tag, i.handed_at, i.deleted_at
  FROM order_items i
  JOIN orders o ON o.id = i.order_id
  JOIN clothes cl ON cl.id = i.clothes_id
 WHERE o.created_at >= @t
 ORDER BY i.order_id, i.id;

SELECT '== 3. 切替前の注文で、切替後に変えたもの (お渡し・支払い・取り消し・メモ) ==' AS '';
SELECT o.id, o.created_at, c.name AS customer, o.amount, o.paid_at, o.handed_at, o.deleted_at, o.note, o.updated_at
  FROM orders o
  JOIN customers c ON c.id = o.customer_id
 WHERE o.created_at < @t AND o.updated_at >= @t
 ORDER BY o.id;

SELECT '== 4. 切替前の品物で、切替後に変えたもの (お渡し・タグ変更・取り消し) ==' AS '';
SELECT i.order_id, i.id, cl.name AS clothes, i.tag, i.handed_at, i.deleted_at, i.updated_at
  FROM order_items i
  JOIN orders o ON o.id = i.order_id
  JOIN clothes cl ON cl.id = i.clothes_id
 WHERE o.created_at < @t AND i.updated_at >= @t
 ORDER BY i.order_id, i.id;

SELECT '== 5. 請求書 (切替後に作った・入金確認・発行・繰越したもの) ==' AS '';
SELECT v.id, c.name AS customer, v.period_start, v.period_end, v.paid_at, v.issued_at, v.has_carried_over,
       v.carry_over_id, v.created_at, v.updated_at, v.deleted_at
  FROM invoices v
  JOIN customers c ON c.id = v.customer_id
 WHERE v.created_at >= @t OR v.updated_at >= @t
 ORDER BY v.id;

SELECT '== 6. 顧客 (切替後に登録・変更したもの) ==' AS '';
SELECT c.id, IF(c.created_at >= @t, '新規', '変更') AS kind, m.name AS manager, c.name, c.name_kana, c.phone_number,
       c.cutoff_date, c.is_invoice, c.needs_payment_confirmation, c.needs_return_confirmation, c.deleted_at, c.updated_at
  FROM customers c
  JOIN managers m ON m.id = c.manager_id
 WHERE c.created_at >= @t OR c.updated_at >= @t OR c.deleted_at >= @t
 ORDER BY c.id;

SELECT '== 7. 顧客メモ ==' AS '';
SELECT ci.customer_id, ci.information, ci.created_at, ci.updated_at, ci.deleted_at
  FROM customer_information ci
 WHERE ci.created_at >= @t OR ci.updated_at >= @t OR ci.deleted_at >= @t
 ORDER BY ci.customer_id;

SELECT '== 8. タグ番号の今の値 (旧のホームの「タグ番号更新」でこの値にそろえる) ==' AS '';
SELECT m.name AS manager, t.value, CONCAT(FLOOR(t.value / 1000), '-', LPAD(t.value % 1000, 3, '0')) AS shown, t.updated_at
  FROM tag_counters t
  JOIN managers m ON m.id = t.manager_id
 ORDER BY m.id;

SELECT '== 9. 衣類マスタ (切替後に足した・変えたもの) ==' AS '';
SELECT cl.id, cl.name, cl.name_kana, cl.price, cl.tag_count, cl.created_at, cl.updated_at, cl.deleted_at
  FROM clothes cl
 WHERE cl.created_at >= @t OR cl.updated_at >= @t OR cl.deleted_at >= @t
 ORDER BY cl.id;

SELECT '== 10. 納品書: 記帳 (切替後に入れた・変えたもの) ==' AS '';
SELECT dc.name AS delivery_customer, dd.name AS department, dp.name AS product, e.date, e.quantity, e.note,
       e.delivery_note_id, e.created_at, e.updated_at
  FROM delivery_daily_entries e
  JOIN delivery_products dp ON dp.id = e.delivery_product_id
  JOIN delivery_departments dd ON dd.id = dp.delivery_department_id
  JOIN delivery_customers dc ON dc.id = dd.delivery_customer_id
 WHERE e.created_at >= @t OR e.updated_at >= @t
 ORDER BY dc.id, e.date, dp.id;

SELECT '== 11. 納品書: 発行・メール送信 ==' AS '';
SELECT n.id, dc.name AS delivery_customer, n.note_number, n.period_start, n.period_end, n.created_at
  FROM delivery_notes n
  JOIN delivery_customers dc ON dc.id = n.delivery_customer_id
 WHERE n.created_at >= @t OR n.updated_at >= @t
 ORDER BY n.id;
SELECT l.id, dc.name AS delivery_customer, l.period_start, l.period_end, l.sent_to, l.sent_at, l.status
  FROM delivery_email_logs l
  JOIN delivery_customers dc ON dc.id = l.delivery_customer_id
 WHERE l.created_at >= @t
 ORDER BY l.id;

SELECT '== 12. 納品書: 取引先・部署・商品のマスタ (切替後に足した・変えたもの) ==' AS '';
SELECT 'customer' AS kind, id, name, created_at, updated_at FROM delivery_customers WHERE created_at >= @t OR updated_at >= @t
UNION ALL
SELECT 'department', id, name, created_at, updated_at FROM delivery_departments WHERE created_at >= @t OR updated_at >= @t
UNION ALL
SELECT 'product', id, name, created_at, updated_at FROM delivery_products WHERE created_at >= @t OR updated_at >= @t;
