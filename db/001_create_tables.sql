-- Tabula-Piece DB Migration 001
-- 全テーブル作成

CREATE DATABASE IF NOT EXISTS tabula_piece CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE tabula_piece;

-- -----------------------------------------------
-- マスターデータ
-- -----------------------------------------------

CREATE TABLE IF NOT EXISTS skills (
  id          INT          NOT NULL AUTO_INCREMENT,
  name        VARCHAR(100) NOT NULL,
  type        ENUM('active','passive') NOT NULL,
  category    ENUM('move','combat','point','board') NOT NULL,
  description TEXT         NOT NULL,
  max_uses    INT          DEFAULT NULL COMMENT 'NULLは無制限',
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS characters (
  id              INT          NOT NULL AUTO_INCREMENT,
  name            VARCHAR(100) NOT NULL,
  piece_class     ENUM('pawn','knight','bishop','rook','queen','king') NOT NULL,
  rarity          ENUM('N','R','SR','SSR') NOT NULL,
  active_skill_id INT          DEFAULT NULL,
  passive_skill_id INT         DEFAULT NULL,
  PRIMARY KEY (id),
  FOREIGN KEY (active_skill_id)  REFERENCES skills(id),
  FOREIGN KEY (passive_skill_id) REFERENCES skills(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------
-- ユーザーデータ
-- -----------------------------------------------

CREATE TABLE IF NOT EXISTS users (
  id         INT          NOT NULL AUTO_INCREMENT,
  name       VARCHAR(100) NOT NULL,
  created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS user_characters (
  id           INT      NOT NULL AUTO_INCREMENT,
  user_id      INT      NOT NULL,
  character_id INT      NOT NULL,
  obtained_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  FOREIGN KEY (user_id)      REFERENCES users(id),
  FOREIGN KEY (character_id) REFERENCES characters(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS decks (
  id              INT          NOT NULL AUTO_INCREMENT,
  user_id         INT          NOT NULL,
  name            VARCHAR(100) NOT NULL,
  pawn_char_id    INT          DEFAULT NULL,
  knight_char_id  INT          DEFAULT NULL,
  bishop_char_id  INT          DEFAULT NULL,
  rook_char_id    INT          DEFAULT NULL,
  queen_char_id   INT          DEFAULT NULL,
  king_char_id    INT          DEFAULT NULL,
  PRIMARY KEY (id),
  FOREIGN KEY (user_id)        REFERENCES users(id),
  FOREIGN KEY (pawn_char_id)   REFERENCES characters(id),
  FOREIGN KEY (knight_char_id) REFERENCES characters(id),
  FOREIGN KEY (bishop_char_id) REFERENCES characters(id),
  FOREIGN KEY (rook_char_id)   REFERENCES characters(id),
  FOREIGN KEY (queen_char_id)  REFERENCES characters(id),
  FOREIGN KEY (king_char_id)   REFERENCES characters(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------
-- 対戦データ
-- -----------------------------------------------

CREATE TABLE IF NOT EXISTS matches (
  id              INT      NOT NULL AUTO_INCREMENT,
  player1_id      INT      NOT NULL,
  player2_id      INT      NOT NULL,
  player1_deck_id INT      DEFAULT NULL,
  player2_deck_id INT      DEFAULT NULL,
  status          ENUM('waiting','in_progress','finished') NOT NULL DEFAULT 'waiting',
  current_turn    INT      NOT NULL DEFAULT 1,
  current_player  ENUM('player1','player2') NOT NULL DEFAULT 'player1',
  winner_id       INT      DEFAULT NULL,
  end_reason      ENUM('checkmate','points','timeout') DEFAULT NULL,
  room_code       VARCHAR(20) DEFAULT NULL,
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  FOREIGN KEY (player1_id)      REFERENCES users(id),
  FOREIGN KEY (player2_id)      REFERENCES users(id),
  FOREIGN KEY (player1_deck_id) REFERENCES decks(id),
  FOREIGN KEY (player2_deck_id) REFERENCES decks(id),
  FOREIGN KEY (winner_id)       REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS board_states (
  id         INT      NOT NULL AUTO_INCREMENT,
  match_id   INT      NOT NULL,
  turn       INT      NOT NULL,
  board_json JSON     NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  FOREIGN KEY (match_id) REFERENCES matches(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS skill_logs (
  id            INT         NOT NULL AUTO_INCREMENT,
  match_id      INT         NOT NULL,
  turn          INT         NOT NULL,
  user_id       INT         NOT NULL,
  skill_id      INT         NOT NULL,
  target_square VARCHAR(10) DEFAULT NULL,
  PRIMARY KEY (id),
  FOREIGN KEY (match_id) REFERENCES matches(id),
  FOREIGN KEY (user_id)  REFERENCES users(id),
  FOREIGN KEY (skill_id) REFERENCES skills(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
