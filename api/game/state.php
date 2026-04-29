<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../config/db.php';

function jsonError(int $code, string $msg): void {
    http_response_code($code);
    echo json_encode(['error' => $msg]);
    exit;
}

$matchId = (int)($_GET['match_id'] ?? 0);
$userId  = (int)($_GET['user_id']  ?? 0);
if (!$matchId || !$userId) jsonError(400, 'match_id と user_id は必須です');

$db = getDb();

// 試合情報
$stmt = $db->prepare('SELECT * FROM matches WHERE id = ? LIMIT 1');
$stmt->execute([$matchId]);
$match = $stmt->fetch();
if (!$match) jsonError(404, '試合が見つかりません');

// 参加者チェック
if ($userId === (int)$match['player1_id'])     $myRole = 'player1';
elseif ($userId === (int)$match['player2_id']) $myRole = 'player2';
else jsonError(403, 'この試合の参加者ではありません');

// 最新盤面
$stmt = $db->prepare(
    'SELECT board_json, turn FROM board_states WHERE match_id = ? ORDER BY turn DESC LIMIT 1'
);
$stmt->execute([$matchId]);
$bs = $stmt->fetch();
if (!$bs) jsonError(500, '盤面データが見つかりません');

// 相手プレイヤー名
$opponentId   = $myRole === 'player1' ? $match['player2_id'] : $match['player1_id'];
$stmt = $db->prepare('SELECT name FROM users WHERE id = ? LIMIT 1');
$stmt->execute([$opponentId]);
$opponentName = ($stmt->fetch()['name'] ?? '相手');
$stmt->execute([$userId]);
$myName       = ($stmt->fetch()['name'] ?? 'あなた');

echo json_encode([
    'match_id'       => $matchId,
    'my_role'        => $myRole,
    'my_color'       => $myRole === 'player1' ? 'white' : 'black',
    'my_name'        => $myName,
    'opponent_name'  => $opponentName,
    'current_player' => $match['current_player'],
    'is_my_turn'     => $match['current_player'] === $myRole,
    'turn'           => (int)$match['current_turn'],
    'max_turns'      => 30,
    'status'         => $match['status'],
    'winner'         => $match['winner_id'],
    'end_reason'     => $match['end_reason'],
    'board'          => json_decode($bs['board_json'], true),
]);
