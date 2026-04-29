-- Phase 4 スキルテスト用データ
-- スキル付き駒を持つ試合データを作成する
-- 実行前に 001_create_tables.sql と 002_seed_master_data.sql を実行済みであること

USE tabula_piece;

-- テストユーザー（すでに存在する場合はスキップ）
INSERT IGNORE INTO users (id, name) VALUES (1, 'プレイヤー1'), (2, 'プレイヤー2');

-- テスト試合を作成
INSERT INTO matches
  (player1_id, player2_id, status, current_turn, current_player)
VALUES
  (1, 2, 'in_progress', 1, 'player1');

SET @match_id = LAST_INSERT_ID();

-- 初期盤面: JSON を直接文字列で挿入（boolean false を確実に表現）
-- 各駒にスキルを付与してテスト可能にしている
INSERT INTO board_states (match_id, turn, board_json)
VALUES (@match_id, 1, CAST('{
  "squares": {
    "a1": {"piece":"rook",   "color":"white","character_id":null,"active_skill_id":4, "passive_skill_id":6,  "active_used":0,"passive_used":0,"shield":false,"value_bonus":0},
    "b1": {"piece":"knight", "color":"white","character_id":null,"active_skill_id":1, "passive_skill_id":null,"active_used":0,"passive_used":0,"shield":false,"value_bonus":0},
    "c1": {"piece":"bishop", "color":"white","character_id":null,"active_skill_id":4, "passive_skill_id":10, "active_used":0,"passive_used":0,"shield":false,"value_bonus":0},
    "d1": {"piece":"queen",  "color":"white","character_id":null,"active_skill_id":7, "passive_skill_id":6,  "active_used":0,"passive_used":0,"shield":false,"value_bonus":0},
    "e1": {"piece":"king",   "color":"white","character_id":null,"active_skill_id":4, "passive_skill_id":null,"active_used":0,"passive_used":0,"shield":false,"value_bonus":0},
    "a2": {"piece":"pawn",   "color":"white","character_id":null,"active_skill_id":null,"passive_skill_id":null,"active_used":0,"passive_used":0,"shield":false,"value_bonus":0},
    "b2": {"piece":"pawn",   "color":"white","character_id":null,"active_skill_id":null,"passive_skill_id":null,"active_used":0,"passive_used":0,"shield":false,"value_bonus":0},
    "c2": {"piece":"pawn",   "color":"white","character_id":null,"active_skill_id":null,"passive_skill_id":null,"active_used":0,"passive_used":0,"shield":false,"value_bonus":0},
    "d2": {"piece":"pawn",   "color":"white","character_id":null,"active_skill_id":null,"passive_skill_id":null,"active_used":0,"passive_used":0,"shield":false,"value_bonus":0},
    "e2": {"piece":"pawn",   "color":"white","character_id":null,"active_skill_id":null,"passive_skill_id":null,"active_used":0,"passive_used":0,"shield":false,"value_bonus":0},
    "f2": {"piece":"pawn",   "color":"white","character_id":null,"active_skill_id":null,"passive_skill_id":null,"active_used":0,"passive_used":0,"shield":false,"value_bonus":0},
    "a5": {"piece":"pawn",   "color":"black","character_id":null,"active_skill_id":null,"passive_skill_id":8, "active_used":0,"passive_used":0,"shield":false,"value_bonus":0},
    "b5": {"piece":"pawn",   "color":"black","character_id":null,"active_skill_id":null,"passive_skill_id":null,"active_used":0,"passive_used":0,"shield":false,"value_bonus":0},
    "c5": {"piece":"pawn",   "color":"black","character_id":null,"active_skill_id":null,"passive_skill_id":null,"active_used":0,"passive_used":0,"shield":false,"value_bonus":0},
    "d5": {"piece":"pawn",   "color":"black","character_id":null,"active_skill_id":null,"passive_skill_id":null,"active_used":0,"passive_used":0,"shield":false,"value_bonus":0},
    "e5": {"piece":"pawn",   "color":"black","character_id":null,"active_skill_id":null,"passive_skill_id":null,"active_used":0,"passive_used":0,"shield":false,"value_bonus":0},
    "f5": {"piece":"pawn",   "color":"black","character_id":null,"active_skill_id":null,"passive_skill_id":null,"active_used":0,"passive_used":0,"shield":false,"value_bonus":0},
    "a6": {"piece":"rook",   "color":"black","character_id":null,"active_skill_id":4, "passive_skill_id":6,  "active_used":0,"passive_used":0,"shield":false,"value_bonus":0},
    "b6": {"piece":"knight", "color":"black","character_id":null,"active_skill_id":2, "passive_skill_id":null,"active_used":0,"passive_used":0,"shield":false,"value_bonus":0},
    "c6": {"piece":"bishop", "color":"black","character_id":null,"active_skill_id":9, "passive_skill_id":8,  "active_used":0,"passive_used":0,"shield":false,"value_bonus":0},
    "d6": {"piece":"queen",  "color":"black","character_id":null,"active_skill_id":7, "passive_skill_id":6,  "active_used":0,"passive_used":0,"shield":false,"value_bonus":0},
    "e6": {"piece":"king",   "color":"black","character_id":null,"active_skill_id":4, "passive_skill_id":null,"active_used":0,"passive_used":0,"shield":false,"value_bonus":0}
  },
  "traps": {},
  "rematch_pending": null
}' AS JSON));

SELECT CONCAT('テスト試合作成完了: match_id=', @match_id) AS result;
SELECT CONCAT('http://localhost:8888/Tabula-Piece/public/match.html?id=', @match_id, '&user_id=1') AS player1_url;
SELECT CONCAT('http://localhost:8888/Tabula-Piece/public/match.html?id=', @match_id, '&user_id=2') AS player2_url;
