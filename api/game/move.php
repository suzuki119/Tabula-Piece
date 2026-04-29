<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST')    { http_response_code(405); echo json_encode(['error' => 'Method Not Allowed']); exit; }

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/Chess.php';

function jsonError(int $code, string $message): void {
    http_response_code($code);
    echo json_encode(['error' => $message]);
    exit;
}

// ─── リクエスト取得 ──────────────────────────────────────────

$body = json_decode(file_get_contents('php://input'), true);
if (!$body) jsonError(400, 'リクエストボディが不正です');

$matchId  = (int)($body['match_id']  ?? 0);
$userId   = (int)($body['user_id']   ?? 0);
$fromSq   = trim($body['from']       ?? '');
$toSq     = trim($body['to']         ?? '');

if (!$matchId || !$userId || !$fromSq || !$toSq) {
    jsonError(400, 'match_id, user_id, from, to は必須です');
}

// ─── 試合情報取得 ────────────────────────────────────────────

$db = getDb();

$match = $db->prepare('SELECT * FROM matches WHERE id = ? AND status = "in_progress" LIMIT 1');
$match->execute([$matchId]);
$match = $match->fetch();
if (!$match) jsonError(404, '進行中の試合が見つかりません');

// プレイヤー判定
if ($userId === (int)$match['player1_id'])      $player = 'player1';
elseif ($userId === (int)$match['player2_id'])  $player = 'player2';
else jsonError(403, 'この試合の参加者ではありません');

// ─── 盤面状態取得 ────────────────────────────────────────────

$bs = $db->prepare('SELECT board_json FROM board_states WHERE match_id = ? ORDER BY turn DESC LIMIT 1');
$bs->execute([$matchId]);
$bs = $bs->fetch();
if (!$bs) jsonError(500, '盤面データが見つかりません');

$board = json_decode($bs['board_json'], true);

// ゲーム状態を組み立て
$state = [
    'board'         => $board,
    'currentPlayer' => $match['current_player'],
    'turn'          => (int)$match['current_turn'],
    'maxTurns'      => 30,
    'status'        => $match['status'],
    'winner'        => null,
    'endReason'     => null,
];

// ─── 移動実行 ────────────────────────────────────────────────

try {
    $newState = Chess::executeMove($state, $player, $fromSq, $toSq);
} catch (RuntimeException $e) {
    jsonError(422, $e->getMessage());
}

// ─── DB更新（トランザクション） ──────────────────────────────

$db->beginTransaction();
try {
    // board_states に新盤面を追記
    $ins = $db->prepare(
        'INSERT INTO board_states (match_id, turn, board_json) VALUES (?, ?, ?)'
    );
    $ins->execute([$matchId, $newState['turn'], json_encode($newState['board'])]);

    // matches を更新
    if ($newState['status'] === 'finished') {
        $winnerId = null;
        if ($newState['winner'] === 'player1') $winnerId = $match['player1_id'];
        if ($newState['winner'] === 'player2') $winnerId = $match['player2_id'];

        $upd = $db->prepare(
            'UPDATE matches SET status="finished", winner_id=?, end_reason=?, updated_at=NOW() WHERE id=?'
        );
        $upd->execute([$winnerId, $newState['endReason'], $matchId]);
    } else {
        $upd = $db->prepare(
            'UPDATE matches SET current_turn=?, current_player=?, updated_at=NOW() WHERE id=?'
        );
        $upd->execute([$newState['turn'], $newState['currentPlayer'], $matchId]);
    }

    $db->commit();
} catch (Exception $e) {
    $db->rollBack();
    jsonError(500, 'DB更新に失敗しました: ' . $e->getMessage());
}

// ─── レスポンス ──────────────────────────────────────────────

echo json_encode([
    'success'   => true,
    'turn'      => $newState['turn'],
    'status'    => $newState['status'],
    'winner'    => $newState['winner'],
    'endReason' => $newState['endReason'],
    'board'     => $newState['board'],
]);
