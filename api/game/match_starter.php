<?php
/**
 * 両プレイヤーが揃った時点でデッキを盤面に適用し試合を開始する共通処理
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/Chess.php';

/**
 * 待機中の試合にplayer2を参加させ、盤面を初期化してin_progressにする。
 * 成功時: ['match_id' => int, 'player_role' => 'player2'] を返す
 * 失敗時: 例外を投げる
 */
function joinAndStartMatch(PDO $db, int $matchId, int $player2Id, int $player2DeckId): array {
    $db->beginTransaction();
    try {
        // 待機中かつ未参加の試合をロック
        $stmt = $db->prepare('SELECT * FROM matches WHERE id = ? AND status = "waiting" AND player2_id IS NULL FOR UPDATE');
        $stmt->execute([$matchId]);
        $match = $stmt->fetch();
        if (!$match) {
            $db->rollBack();
            throw new RuntimeException('試合が見つかりません（すでに参加済みか存在しない）');
        }

        $player1Id    = (int)$match['player1_id'];
        $player1DeckId = (int)$match['player1_deck_id'];

        // 両デッキを取得
        $deckCols = ['pawn_char_id','knight_char_id','bishop_char_id','rook_char_id','queen_char_id','king_char_id'];
        $stmt = $db->prepare('SELECT * FROM decks WHERE id IN (?, ?)');
        $stmt->execute([$player1DeckId, $player2DeckId]);
        $decksResult = $stmt->fetchAll();
        $deckMap = [];
        foreach ($decksResult as $d) { $deckMap[(int)$d['id']] = $d; }

        $myDeck  = $deckMap[$player1DeckId] ?? null;
        $oppDeck = $deckMap[$player2DeckId] ?? null;
        if (!$myDeck || !$oppDeck) throw new RuntimeException('デッキが取得できません');

        // 使用キャラID収集
        $allCharIds = [];
        foreach ($deckCols as $col) {
            if ($myDeck[$col])  $allCharIds[] = (int)$myDeck[$col];
            if ($oppDeck[$col]) $allCharIds[] = (int)$oppDeck[$col];
        }

        $charMap = [];
        if ($allCharIds) {
            $unique = array_unique($allCharIds);
            $placeholders = implode(',', array_fill(0, count($unique), '?'));
            $stmt = $db->prepare("SELECT id, active_skill_id, passive_skill_id FROM characters WHERE id IN ($placeholders)");
            $stmt->execute($unique);
            foreach ($stmt->fetchAll() as $c) {
                $charMap[(int)$c['id']] = [
                    'active_skill_id'  => $c['active_skill_id']  !== null ? (int)$c['active_skill_id']  : null,
                    'passive_skill_id' => $c['passive_skill_id'] !== null ? (int)$c['passive_skill_id'] : null,
                ];
            }
        }

        // 盤面作成
        $toClassMap = fn($deck) => [
            'pawn'   => $deck['pawn_char_id']   ? (int)$deck['pawn_char_id']   : null,
            'knight' => $deck['knight_char_id'] ? (int)$deck['knight_char_id'] : null,
            'bishop' => $deck['bishop_char_id'] ? (int)$deck['bishop_char_id'] : null,
            'rook'   => $deck['rook_char_id']   ? (int)$deck['rook_char_id']   : null,
            'queen'  => $deck['queen_char_id']  ? (int)$deck['queen_char_id']  : null,
            'king'   => $deck['king_char_id']   ? (int)$deck['king_char_id']   : null,
        ];

        $board = Chess::createInitialBoard();
        $board = Chess::applyDeckToBoard($board, $toClassMap($myDeck),  $charMap, 'white');
        $board = Chess::applyDeckToBoard($board, $toClassMap($oppDeck), $charMap, 'black');

        $initState = [
            'board'            => $board,
            'traps'            => [],
            'rematchPending'   => null,
            'skillOpportunity' => null,
        ];

        // 試合更新
        $stmt = $db->prepare('
            UPDATE matches SET
                player2_id      = ?,
                player2_deck_id = ?,
                status          = "in_progress",
                current_turn    = 1,
                current_player  = "player1"
            WHERE id = ?
        ');
        $stmt->execute([$player2Id, $player2DeckId, $matchId]);

        // 初期盤面挿入
        $stmt = $db->prepare('INSERT INTO board_states (match_id, turn, board_json) VALUES (?, 1, ?)');
        $stmt->execute([$matchId, Chess::encodeGameData($initState)]);

        $db->commit();
        return ['match_id' => $matchId, 'player_role' => 'player2'];
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
}

/**
 * 新規待機試合を作成する（player1のみ、盤面はまだ作らない）
 */
function createWaitingMatch(PDO $db, int $player1Id, int $player1DeckId, ?string $roomCode = null): int {
    $stmt = $db->prepare('
        INSERT INTO matches (player1_id, player2_id, player1_deck_id, status, current_turn, current_player, room_code)
        VALUES (?, NULL, ?, "waiting", 1, "player1", ?)
    ');
    $stmt->execute([$player1Id, $player1DeckId, $roomCode]);
    return (int)$db->lastInsertId();
}
