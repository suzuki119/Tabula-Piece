-- Phase 6: マッチング対応
-- player2_id をNULL許可に変更（マッチング待機中はNULLになる）

USE tabula_piece;

ALTER TABLE matches
  MODIFY player2_id INT DEFAULT NULL;
