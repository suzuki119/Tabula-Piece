-- Phase 5 テスト用: テストユーザーに全キャラクターを付与 + デフォルトデッキ作成
-- 実行前に 001〜003 が適用済みであること

USE tabula_piece;

-- テストユーザー確保
INSERT IGNORE INTO users (id, name) VALUES (1, 'プレイヤー1'), (2, 'プレイヤー2');

-- ユーザー1: 全キャラクターを付与
INSERT IGNORE INTO user_characters (user_id, character_id) VALUES
(1, 1),(1, 2),(1, 3),(1, 4),(1, 5),(1, 6),
(1, 7),(1, 8),(1, 9),(1,10),(1,11),(1,12),
(1,13),(1,14),(1,15),(1,16);

-- ユーザー2: 全キャラクターを付与
INSERT IGNORE INTO user_characters (user_id, character_id) VALUES
(2, 1),(2, 2),(2, 3),(2, 4),(2, 5),(2, 6),
(2, 7),(2, 8),(2, 9),(2,10),(2,11),(2,12),
(2,13),(2,14),(2,15),(2,16);

-- ユーザー1のスターターデッキ（バランス型）
-- pawn=決死の護衛(3), knight=影の騎士(6), bishop=聖壁の守者(8)
-- rook=要塞将軍(12), queen=戦場の女王(13), king=老王(15)
INSERT INTO decks (user_id, name, pawn_char_id, knight_char_id, bishop_char_id, rook_char_id, queen_char_id, king_char_id)
VALUES (1, 'スターターデッキ', 3, 6, 8, 12, 13, 15)
ON DUPLICATE KEY UPDATE name = name;

-- ユーザー2のスターターデッキ（攻撃型）
-- pawn=歩哨兵(2), knight=突撃騎兵(5), bishop=呪術師(9)
-- rook=鉄壁(11), queen=絶対女王(14), king=不死の王(16)
INSERT INTO decks (user_id, name, pawn_char_id, knight_char_id, bishop_char_id, rook_char_id, queen_char_id, king_char_id)
VALUES (2, 'スターターデッキ', 2, 5, 9, 11, 14, 16)
ON DUPLICATE KEY UPDATE name = name;

SELECT 'ユーザー1・2にキャラクターとデッキを付与しました' AS result;
SELECT id, name FROM decks ORDER BY id;
