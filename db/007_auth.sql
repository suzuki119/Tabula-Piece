-- 007_auth.sql: ユーザー認証カラム追加

USE tabula_piece;

ALTER TABLE users
  ADD COLUMN email         VARCHAR(255) NULL UNIQUE AFTER name,
  ADD COLUMN password_hash VARCHAR(255) NULL        AFTER email;

-- 既存ユーザーのメールアドレスをダミー設定（テスト環境のみ）
UPDATE users SET email = CONCAT('user', id, '@example.com') WHERE email IS NULL;
