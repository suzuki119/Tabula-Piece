-- 010_quest.sql: Phase 15 クエストモード（PvE章立て）
-- - CPUユーザー (id=99999) を作成
-- - matches に quest_id / is_cpu_match 列を追加
-- - quests / quest_clears テーブルを作成
-- - Chapter 1 の3ステージをseed

USE tabula_piece;

-- ================================================
-- 1. CPUユーザー（固定ID 99999）
-- ================================================

INSERT INTO users (id, name, email, password_hash, stones)
VALUES (99999, 'CPU', 'cpu@system.local', '', 0)
ON DUPLICATE KEY UPDATE name = 'CPU';

-- ================================================
-- 2. matches 列拡張
-- ================================================

ALTER TABLE matches
  ADD COLUMN quest_id     INT          NULL     AFTER room_code,
  ADD COLUMN is_cpu_match TINYINT(1)   NOT NULL DEFAULT 0 AFTER quest_id;

-- ================================================
-- 3. quests / quest_clears テーブル
-- ================================================

CREATE TABLE IF NOT EXISTS quests (
  id                  INT          NOT NULL AUTO_INCREMENT,
  chapter             INT          NOT NULL,
  stage               INT          NOT NULL,
  name                VARCHAR(100) NOT NULL,
  description         TEXT         NULL,
  difficulty          ENUM('easy','normal','hard') NOT NULL DEFAULT 'easy',
  cpu_deck_class      ENUM('neutral','witch','blade','architect','paladin','dominant') NOT NULL DEFAULT 'neutral',
  cpu_deck_json       JSON         NOT NULL COMMENT '{"pawn":id,"knight":id,"bishop":id,"rook":id,"queen":id,"king":id}',
  reward_gem          INT          NOT NULL DEFAULT 0,
  reward_character_id INT          NULL,
  sort_order          INT          NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  UNIQUE KEY uniq_chapter_stage (chapter, stage),
  FOREIGN KEY (reward_character_id) REFERENCES characters(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS quest_clears (
  id         INT      NOT NULL AUTO_INCREMENT,
  user_id    INT      NOT NULL,
  quest_id   INT      NOT NULL,
  cleared_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uniq_user_quest (user_id, quest_id),
  FOREIGN KEY (user_id)  REFERENCES users(id),
  FOREIGN KEY (quest_id) REFERENCES quests(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ================================================
-- 4. 初期クエスト（Chapter 1 / 3ステージ）
--    CPUデッキは 002_seed と 008_class で投入済のキャラIDを参照
--      ニュートラル基本セット: pawn=1, knight=4, bishop=7, rook=10, queen=13, king=15
--      ニュートラル強化セット: pawn=2, knight=5, bishop=8, rook=11, queen=14, king=16
--      ウィッチセット (king は唯一の不死の王=16): pawn=36, knight=32, bishop=28, rook=24, queen=20, king=16
-- ================================================

INSERT INTO quests
  (chapter, stage, name,                   description,                              difficulty, cpu_deck_class, cpu_deck_json, reward_gem, reward_character_id, sort_order)
VALUES
  (1, 1, '訓練場の対戦',     '基本デッキを持つ初心者CPUと対戦する。クエストモードの入り口。',
     'easy',   'neutral',
     '{"pawn":1,"knight":4,"bishop":7,"rook":10,"queen":13,"king":15}',
     50,  NULL, 10),
  (1, 2, '熟練の挑戦者',     'R以上のキャラで編成した中堅CPUと対戦する。',
     'normal', 'neutral',
     '{"pawn":2,"knight":5,"bishop":8,"rook":11,"queen":14,"king":16}',
     100, NULL, 20),
  (1, 3, '章ボス: 黒の魔女', 'ウィッチクラスの強敵CPU。初回クリアで「不死の王」を解放。',
     'hard',   'witch',
     '{"pawn":36,"knight":32,"bishop":28,"rook":24,"queen":20,"king":16}',
     200, 16,   30);
