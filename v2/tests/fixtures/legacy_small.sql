-- 旧 DB (Laravel 8) の小さなテスト用コピー。スキーマは本番ダンプの CREATE TABLE と同じ (AUTO_INCREMENT 値のみ除去)。
-- データは架空。LegacyImportTest が legacy_testing DB に入れて使う。
-- 旧本番と同じ sql_mode (NO_ZERO_IN_DATE なし)。本番には '2026-07-00' のような日付が実在する
SET SESSION sql_mode='STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';
SET FOREIGN_KEY_CHECKS=0;
DROP TABLE IF EXISTS `categories`;
CREATE TABLE `categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `store_id` bigint(20) unsigned NOT NULL COMMENT '店舗ID',
  `name` varchar(255) NOT NULL COMMENT 'カテゴリ名',
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT '作成日時',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT '更新日時',
  PRIMARY KEY (`id`),
  KEY `categories_store_id_foreign` (`store_id`),
  CONSTRAINT `categories_store_id_foreign` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
DROP TABLE IF EXISTS `client_errors`;
CREATE TABLE `client_errors` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `error_type` varchar(20) NOT NULL,
  `message` text NOT NULL,
  `stack` text DEFAULT NULL,
  `url` varchar(500) DEFAULT NULL,
  `user_agent` varchar(500) DEFAULT NULL,
  `manager_id` bigint(20) unsigned DEFAULT NULL,
  `extra_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`extra_data`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `client_errors_error_type_index` (`error_type`),
  KEY `client_errors_created_at_index` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
DROP TABLE IF EXISTS `clothes`;
CREATE TABLE `clothes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `store_id` bigint(20) unsigned NOT NULL COMMENT '店舗ID',
  `category_id` bigint(20) unsigned NOT NULL COMMENT 'カテゴリID',
  `sort_key` bigint(20) unsigned NOT NULL DEFAULT 0 COMMENT 'ソートキー',
  `name` varchar(255) NOT NULL COMMENT '商品名',
  `name_kana` varchar(255) NOT NULL COMMENT '商品略名',
  `price` bigint(20) unsigned NOT NULL COMMENT '値段',
  `tag_count` int(10) unsigned NOT NULL DEFAULT 1 COMMENT 'タグ枚数',
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT '作成日時',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT '更新日時',
  PRIMARY KEY (`id`),
  KEY `clothes_store_id_foreign` (`store_id`),
  KEY `clothes_category_id_foreign` (`category_id`),
  CONSTRAINT `clothes_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`),
  CONSTRAINT `clothes_store_id_foreign` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
DROP TABLE IF EXISTS `customer_information`;
CREATE TABLE `customer_information` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` bigint(20) unsigned NOT NULL COMMENT '顧客ID',
  `information` varchar(255) NOT NULL COMMENT 'CS情報',
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT '作成日時',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT '更新日時',
  PRIMARY KEY (`id`),
  KEY `customer_information_customer_id_foreign` (`customer_id`),
  CONSTRAINT `customer_information_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
DROP TABLE IF EXISTS `customers`;
CREATE TABLE `customers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `manager_id` bigint(20) unsigned NOT NULL COMMENT '担当者ID',
  `name` varchar(255) NOT NULL COMMENT '顧客名',
  `name_kana` varchar(255) NOT NULL COMMENT '顧客名カナ',
  `phone_number` varchar(255) DEFAULT NULL COMMENT '電話番号',
  `birth_day` date DEFAULT NULL COMMENT '生年月日',
  `sex` tinyint(3) unsigned DEFAULT NULL COMMENT '性別',
  `point` bigint(20) unsigned DEFAULT NULL COMMENT 'ポイント',
  `cutoff_date` tinyint(4) NOT NULL DEFAULT 99 COMMENT '締め日',
  `is_invoice` tinyint(1) NOT NULL DEFAULT 0 COMMENT '請求書フラグ',
  `needs_payment_confimation` tinyint(1) NOT NULL DEFAULT 0 COMMENT '入金確認フラグ',
  `needs_return_confimation` tinyint(1) NOT NULL DEFAULT 1 COMMENT '返却確認フラグ',
  `total_sales` bigint(20) unsigned NOT NULL DEFAULT 0 COMMENT '累計売上',
  `number_of_visits` bigint(20) unsigned NOT NULL DEFAULT 0 COMMENT '来店回数',
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT '作成日時',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT '更新日時',
  PRIMARY KEY (`id`),
  KEY `customers_manager_id_foreign` (`manager_id`),
  CONSTRAINT `customers_manager_id_foreign` FOREIGN KEY (`manager_id`) REFERENCES `managers` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
DROP TABLE IF EXISTS `delivery_customers`;
CREATE TABLE `delivery_customers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `registration_number` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
DROP TABLE IF EXISTS `delivery_daily_entries`;
CREATE TABLE `delivery_daily_entries` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `delivery_product_id` bigint(20) unsigned NOT NULL,
  `delivery_note_id` bigint(20) unsigned DEFAULT NULL,
  `date` date NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `note` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `delivery_daily_entries_delivery_product_id_date_unique` (`delivery_product_id`,`date`),
  KEY `delivery_daily_entries_delivery_note_id_foreign` (`delivery_note_id`),
  CONSTRAINT `delivery_daily_entries_delivery_note_id_foreign` FOREIGN KEY (`delivery_note_id`) REFERENCES `delivery_notes` (`id`) ON DELETE SET NULL,
  CONSTRAINT `delivery_daily_entries_delivery_product_id_foreign` FOREIGN KEY (`delivery_product_id`) REFERENCES `delivery_products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
DROP TABLE IF EXISTS `delivery_departments`;
CREATE TABLE `delivery_departments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `delivery_customer_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `delivery_departments_delivery_customer_id_foreign` (`delivery_customer_id`),
  CONSTRAINT `delivery_departments_delivery_customer_id_foreign` FOREIGN KEY (`delivery_customer_id`) REFERENCES `delivery_customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
DROP TABLE IF EXISTS `delivery_email_logs`;
CREATE TABLE `delivery_email_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `delivery_note_id` bigint(20) unsigned NOT NULL,
  `delivery_customer_id` bigint(20) unsigned NOT NULL,
  `period_start` date NOT NULL,
  `period_end` date NOT NULL,
  `sent_to` varchar(255) NOT NULL,
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status` enum('success','failed') NOT NULL DEFAULT 'success',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `delivery_email_logs_delivery_note_id_foreign` (`delivery_note_id`),
  KEY `delivery_email_logs_delivery_customer_id_foreign` (`delivery_customer_id`),
  CONSTRAINT `delivery_email_logs_delivery_customer_id_foreign` FOREIGN KEY (`delivery_customer_id`) REFERENCES `delivery_customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `delivery_email_logs_delivery_note_id_foreign` FOREIGN KEY (`delivery_note_id`) REFERENCES `delivery_notes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
DROP TABLE IF EXISTS `delivery_notes`;
CREATE TABLE `delivery_notes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `delivery_customer_id` bigint(20) unsigned NOT NULL,
  `period_start` date NOT NULL,
  `period_end` date NOT NULL,
  `note_number` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `delivery_notes_delivery_customer_id_note_number_unique` (`delivery_customer_id`,`note_number`),
  CONSTRAINT `delivery_notes_delivery_customer_id_foreign` FOREIGN KEY (`delivery_customer_id`) REFERENCES `delivery_customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
DROP TABLE IF EXISTS `delivery_products`;
CREATE TABLE `delivery_products` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `delivery_department_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `unit_price` int(11) NOT NULL,
  `tax_rate` decimal(4,2) NOT NULL DEFAULT 0.10,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `delivery_products_delivery_department_id_foreign` (`delivery_department_id`),
  CONSTRAINT `delivery_products_delivery_department_id_foreign` FOREIGN KEY (`delivery_department_id`) REFERENCES `delivery_departments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
DROP TABLE IF EXISTS `invoices`;
CREATE TABLE `invoices` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `manager_id` bigint(20) unsigned NOT NULL COMMENT '担当者ID',
  `customer_id` bigint(20) unsigned NOT NULL COMMENT '顧客ID',
  `period_start` date NOT NULL COMMENT '請求対象開始日',
  `period_end` date NOT NULL COMMENT '請求対象終了日',
  `paid_at` date DEFAULT NULL COMMENT '支払い確認日',
  `issued_at` date DEFAULT NULL COMMENT 'PDF出力日',
  `has_carried_over` tinyint(1) NOT NULL DEFAULT 0 COMMENT '繰り越しフラグ',
  `carry_over_id` bigint(20) unsigned DEFAULT NULL COMMENT '繰越先ID',
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT '作成日時',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT '更新日時',
  PRIMARY KEY (`id`),
  KEY `invoices_manager_id_foreign` (`manager_id`),
  KEY `invoices_customer_id_foreign` (`customer_id`),
  CONSTRAINT `invoices_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  CONSTRAINT `invoices_manager_id_foreign` FOREIGN KEY (`manager_id`) REFERENCES `managers` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
DROP TABLE IF EXISTS `ipaddresses`;
CREATE TABLE `ipaddresses` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `ipaddress` varchar(255) NOT NULL DEFAULT '0.0.0.0' COMMENT 'IPアドレス',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
DROP TABLE IF EXISTS `managers`;
CREATE TABLE `managers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `store_id` bigint(20) unsigned NOT NULL COMMENT '店舗ID',
  `name` varchar(255) NOT NULL COMMENT '担当者名',
  `theme_header` varchar(255) DEFAULT NULL COMMENT 'テーマカラーコード（ヘッダー）',
  `theme_body` varchar(255) DEFAULT NULL COMMENT 'テーマカラーコード（ボディ）',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT '作成日時',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT '更新日時',
  PRIMARY KEY (`id`),
  KEY `managers_store_id_foreign` (`store_id`),
  CONSTRAINT `managers_store_id_foreign` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
DROP TABLE IF EXISTS `migrations`;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
DROP TABLE IF EXISTS `order_clothes`;
CREATE TABLE `order_clothes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint(20) unsigned NOT NULL COMMENT '注文ID',
  `clothes_id` bigint(20) unsigned NOT NULL COMMENT '商品ID',
  `tag` varchar(255) NOT NULL COMMENT 'タグ番号',
  `handed_at` datetime DEFAULT NULL COMMENT 'お渡し日時',
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT '作成日時',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT '更新日時',
  PRIMARY KEY (`id`),
  KEY `order_clothes_order_id_foreign` (`order_id`),
  KEY `order_clothes_clothes_id_foreign` (`clothes_id`),
  CONSTRAINT `order_clothes_clothes_id_foreign` FOREIGN KEY (`clothes_id`) REFERENCES `clothes` (`id`),
  CONSTRAINT `order_clothes_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
DROP TABLE IF EXISTS `orders`;
CREATE TABLE `orders` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `store_id` bigint(20) unsigned NOT NULL COMMENT '店舗ID',
  `manager_id` bigint(20) unsigned NOT NULL COMMENT '担当者ID',
  `customer_id` bigint(20) unsigned NOT NULL COMMENT '顧客ID',
  `invoice_id` bigint(20) unsigned DEFAULT NULL COMMENT '請求書ID',
  `is_invoice` tinyint(1) NOT NULL DEFAULT 0 COMMENT '請求書フラグ',
  `amount` bigint(20) NOT NULL COMMENT '合計金額',
  `reduction` bigint(20) NOT NULL COMMENT '値引き金額',
  `discount` bigint(20) NOT NULL DEFAULT 0 COMMENT '値引き割合',
  `payment` bigint(20) NOT NULL COMMENT 'お支払い金額',
  `paid_at` datetime DEFAULT NULL COMMENT '支払い日時',
  `handed_at` datetime DEFAULT NULL COMMENT 'お渡し日時',
  `note` text DEFAULT NULL COMMENT '特記事項',
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT '作成日時',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT '更新日時',
  PRIMARY KEY (`id`),
  KEY `orders_store_id_foreign` (`store_id`),
  KEY `orders_manager_id_foreign` (`manager_id`),
  KEY `orders_customer_id_foreign` (`customer_id`),
  KEY `orders_invoice_id_foreign` (`invoice_id`),
  CONSTRAINT `orders_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  CONSTRAINT `orders_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`),
  CONSTRAINT `orders_manager_id_foreign` FOREIGN KEY (`manager_id`) REFERENCES `managers` (`id`),
  CONSTRAINT `orders_store_id_foreign` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
DROP TABLE IF EXISTS `personal_access_tokens`;
CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
DROP TABLE IF EXISTS `sessions`;
CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` text NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
DROP TABLE IF EXISTS `stores`;
CREATE TABLE `stores` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL COMMENT '店舗名',
  `email` varchar(255) NOT NULL COMMENT 'メールアドレス',
  `password` varchar(255) NOT NULL COMMENT 'パスワード',
  `postal_code` varchar(255) DEFAULT NULL COMMENT '郵便番号',
  `address` varchar(255) NOT NULL COMMENT '住所',
  `phone_number` varchar(255) NOT NULL COMMENT '電話番号',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT '作成日時',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT '更新日時',
  PRIMARY KEY (`id`),
  UNIQUE KEY `stores_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
DROP TABLE IF EXISTS `tag_numbers`;
CREATE TABLE `tag_numbers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `manager_id` bigint(20) unsigned NOT NULL COMMENT '担当者ID',
  `tag_number` bigint(20) unsigned NOT NULL COMMENT 'シリアルナンバー',
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT '作成日時',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT '更新日時',
  PRIMARY KEY (`id`),
  KEY `tag_numbers_manager_id_foreign` (`manager_id`),
  CONSTRAINT `tag_numbers_manager_id_foreign` FOREIGN KEY (`manager_id`) REFERENCES `managers` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
DROP TABLE IF EXISTS `taxes`;
CREATE TABLE `taxes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `store_id` bigint(20) unsigned NOT NULL COMMENT '店舗ID',
  `tax` varchar(255) NOT NULL COMMENT '税率',
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT '作成日時',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT '更新日時',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `stores` VALUES (1,'テスト本店','store@example.com','$2y$10$abcdefghijklmnopqrstuuG1t0pZ2gq2b0ZQ1sQ1sQ1sQ1sQ1sQ1s','877-0037','大分県日田市1-1','0973-00-0000','2022-12-01 06:52:08','2023-02-01 10:00:00');
INSERT INTO `managers` VALUES (1,1,'本店','#111111','#eeeeee','2022-12-01 06:52:08','2022-12-01 06:52:08'),(2,1,'外交',NULL,NULL,'2022-12-01 06:52:08','2022-12-01 06:52:08');
INSERT INTO `customers` VALUES
 (1,1,'山田 太郎','ヤマダ タロウ','090-0000-0001','1980-01-02',1,NULL,99,0,0,1,1200,3,NULL,'2022-12-02 01:00:00','2023-01-02 01:00:00'),
 (2,1,'請求 病院','セイキュウ ビョウイン',NULL,NULL,NULL,NULL,20,1,1,0,50000,10,NULL,'2022-12-03 01:00:00','2023-01-03 01:00:00'),
 (3,2,'削除 済子','サクジョ スミコ',NULL,NULL,2,5,99,0,0,1,0,0,'2023-03-01 00:00:00','2022-12-04 01:00:00','2023-03-01 00:00:00');
INSERT INTO `customer_information` VALUES (1,1,'ボタン注意',NULL,'2022-12-05 01:00:00','2022-12-05 01:00:00'),(2,3,'消した情報','2023-03-01 00:00:00','2022-12-05 01:00:00','2023-03-01 00:00:00');
INSERT INTO `invoices` VALUES
 (1,1,2,'2022-12-21','2023-01-20','2023-01-20',NULL,1,2,NULL,'2023-01-21 01:00:00','2023-01-21 01:00:00'),
 (2,1,2,'2023-01-21','2023-02-20',NULL,'2023-02-21',0,NULL,NULL,'2023-02-21 01:00:00','2023-02-21 01:00:00'),
 (3,1,2,'2023-02-21','2023-03-20',NULL,NULL,0,NULL,'2023-03-22 00:00:00','2023-03-21 01:00:00','2023-03-22 00:00:00'),
 (4,1,2,'2023-03-01','2023-04-00',NULL,NULL,0,NULL,NULL,'2023-04-01 01:00:00','2023-04-01 01:00:00');
INSERT INTO `orders` VALUES
 (1,1,1,1,NULL,0,1100,0,0,1100,'2022-12-10 10:00:00','2022-12-15 10:00:00','シミあり',NULL,'2022-12-10 10:00:00','2022-12-15 10:00:00'),
 (2,1,1,2,1,1,5500,500,10,4950,NULL,NULL,NULL,NULL,'2023-01-05 10:00:00','2023-01-05 10:00:00'),
 (3,1,2,1,NULL,0,-300,0,0,-300,'2023-01-06 10:00:00',NULL,NULL,'2023-01-07 00:00:00','2023-01-06 10:00:00','2023-01-07 00:00:00'),
 (5,1,1,2,2,1,2200,0,0,2200,'2023-11-00 00:00:00',NULL,'',NULL,'2023-02-05 10:00:00','2023-02-05 10:00:00');
INSERT INTO `categories` VALUES (1,1,'ワイシャツ',NULL,'2022-12-01 06:52:08','2022-12-01 06:52:08'),(2,1,'旧カテゴリ','2023-01-01 00:00:00','2022-12-01 06:52:08','2023-01-01 00:00:00');
INSERT INTO `clothes` VALUES (1,1,1,2,'ワイシャツ','Yシャツ',220,1,NULL,'2022-12-01 06:52:08','2022-12-01 06:52:08'),(2,1,1,1,'背広上下','背広',1100,2,NULL,'2022-12-01 06:52:08','2022-12-01 06:52:08'),(3,1,2,0,'廃番','廃番',500,1,'2023-01-01 00:00:00','2022-12-01 06:52:08','2023-01-01 00:00:00');
INSERT INTO `order_clothes` VALUES
 (1,1,2,'1-001','2022-12-15 10:00:00',NULL,'2022-12-10 10:00:00','2022-12-15 10:00:00'),
 (2,1,2,'1-002','2022-12-15 10:00:00',NULL,'2022-12-10 10:00:00','2022-12-15 10:00:00'),
 (3,2,1,'0-000',NULL,NULL,'2023-01-05 10:00:00','2023-01-05 10:00:00'),
 (4,2,3,'10-999',NULL,NULL,'2023-01-05 10:00:00','2023-01-05 10:00:00'),
 (6,3,1,'1-003',NULL,'2023-01-07 00:00:00','2023-01-06 10:00:00','2023-01-07 00:00:00');
INSERT INTO `tag_numbers` VALUES (1,1,3592,NULL,'2022-12-01 06:52:08','2023-01-06 10:00:00'),(2,2,1000,NULL,'2022-12-01 06:52:08','2022-12-01 06:52:08');
INSERT INTO `taxes` VALUES (1,1,'10',NULL,'2022-12-01 06:52:08','2022-12-01 06:52:08');
INSERT INTO `ipaddresses` VALUES (1,'192.168.0.214','2023-02-27 05:53:08','2023-02-27 05:50:23');
INSERT INTO `delivery_customers` VALUES (1,'テスト病院','byoin@example.com','T1234567890123','2026-03-19 01:00:00','2026-03-19 01:00:00');
INSERT INTO `delivery_departments` VALUES (1,1,'病棟',1,'2026-03-19 01:00:00','2026-03-19 01:00:00');
INSERT INTO `delivery_products` VALUES (1,1,'白衣',150,0.10,1,'2026-03-19 01:00:00','2026-03-19 01:00:00'),(2,1,'シーツ',80,0.08,2,'2026-03-19 01:00:00','2026-03-19 01:00:00');
INSERT INTO `delivery_notes` VALUES (1,1,'2026-03-01','2026-03-31',1,'2026-04-01 01:00:00','2026-04-01 01:00:00');
INSERT INTO `delivery_daily_entries` VALUES (1,1,1,'2026-03-02',3,NULL,'2026-03-02 01:00:00','2026-03-02 01:00:00'),(2,2,NULL,'2026-04-02',5,'追加分','2026-04-02 01:00:00','2026-04-02 01:00:00');
INSERT INTO `delivery_email_logs` VALUES (1,1,1,'2026-03-01','2026-03-31','byoin@example.com','2026-04-01 02:00:00','success','2026-04-01 02:00:00','2026-04-01 02:00:00');
INSERT INTO `sessions` VALUES ('sess1',1,'127.0.0.1','ua','payload',1700000000);
INSERT INTO `personal_access_tokens` VALUES (1,'App\Models\Store',1,'token','0123456789012345678901234567890123456789012345678901234567890123','[\"*\"]',NULL,'2023-01-01 00:00:00','2023-01-01 00:00:00');
INSERT INTO `client_errors` VALUES (1,'error','boom',NULL,NULL,NULL,1,NULL,'2026-03-01 00:00:00','2026-03-01 00:00:00');
INSERT INTO `migrations` VALUES (1,'2022_10_14_042759_create_stores_table',1);
SET FOREIGN_KEY_CHECKS=1;
