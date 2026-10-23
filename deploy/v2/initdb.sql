-- テスト用 DB（php artisan test が使う）
CREATE DATABASE IF NOT EXISTS testing CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
GRANT ALL PRIVILEGES ON testing.* TO 'v2'@'%';
FLUSH PRIVILEGES;
