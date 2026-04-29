-- Phase 7: ガチャシステム

USE tabula_piece;

-- ガチャ石カラム追加
ALTER TABLE users ADD COLUMN stones INT NOT NULL DEFAULT 0;

-- ガチャ履歴テーブル
CREATE TABLE IF NOT EXISTS gacha_logs (
  id           INT      NOT NULL AUTO_INCREMENT,
  user_id      INT      NOT NULL,
  character_id INT      NOT NULL,
  rarity       ENUM('N','R','SR','SSR') NOT NULL,
  is_new       TINYINT(1) NOT NULL DEFAULT 1,
  stones_spent INT      NOT NULL DEFAULT 0,
  pulled_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  FOREIGN KEY (user_id)      REFERENCES users(id),
  FOREIGN KEY (character_id) REFERENCES characters(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- テスト用: ユーザー1・2に石を付与
UPDATE users SET stones = 10000 WHERE id IN (1, 2);
-- テスト用: 所持キャラをリセットして引けるようにする（任意）
-- DELETE FROM user_characters WHERE user_id IN (1,2);
