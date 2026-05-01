-- 009_surrender_enum.sql: matches.end_reason に 'surrender' を追加

USE tabula_piece;

ALTER TABLE matches
  MODIFY COLUMN end_reason ENUM('checkmate','points','timeout','surrender') DEFAULT NULL;
