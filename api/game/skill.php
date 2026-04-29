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
$skillId  = (int)($body['skill_id']  ?? 0);
$targetSq = isset($body['target']) ? trim($body['target']) : null;

if (!$matchId || !$userId || !$fromSq || !$skillId) {
    jsonError(400, 'match_id, user_id, from, skill_id は必須です');
}

// ─── 試合情報取得 ────────────────────────────────────────────

$db = getDb();

$stmt = $db->prepare('SELECT * FROM matches WHERE id = ? AND status = "in_progress" LIMIT 1');
$stmt->execute([$matchId]);
$match = $stmt->fetch();
if (!$match) jsonError(404, '進行中の試合が見つかりません');

if ($userId === (int)$match['player1_id'])      $player = 'player1';
elseif ($userId === (int)$match['player2_id'])  $player = 'player2';
else jsonError(403, 'この試合の参加者ではありません');

// ─── 盤面状態取得 ────────────────────────────────────────────

$stmt = $db->prepare('SELECT board_json FROM board_states WHERE match_id = ? ORDER BY turn DESC, id DESC LIMIT 1');
$stmt->execute([$matchId]);
$bs = $stmt->fetch();
if (!$bs) jsonError(500, '盤面データが見つかりません');

$gameData = Chess::decodeGameData($bs['board_json']);

$state = [
    'board'          => $gameData['board'],
    'traps'          => $gameData['traps'],
    'rematchPending' => $gameData['rematchPending'],
    'currentPlayer'  => $match['current_player'],
    'turn'           => (int)$match['current_turn'],
    'maxTurns'       => 30,
    'status'         => $match['status'],
    'winner'         => null,
    'endReason'      => null,
];

// ─── スキル実行 ──────────────────────────────────────────────

try {
    $newState = Chess::executeSkill($state, $player, $fromSq, $skillId, $targetSq ?: null);
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
    $ins->execute([
        $matchId,
        $newState['turn'],
        Chess::encodeGameData($newState),
    ]);

    // matches を更新
    $upd = $db->prepare(
        'UPDATE matches SET current_turn=?, current_player=?, updated_at=NOW() WHERE id=?'
    );
    $upd->execute([$newState['turn'], $newState['currentPlayer'], $matchId]);

    // skill_logs にスキル使用を記録
    $log = $db->prepare(
        'INSERT INTO skill_logs (match_id, turn, user_id, skill_id, target_square) VALUES (?, ?, ?, ?, ?)'
    );
    $log->execute([$matchId, $state['turn'], $userId, $skillId, $targetSq]);

    $db->commit();
} catch (Exception $e) {
    $db->rollBack();
    jsonError(500, 'DB更新に失敗しました: ' . $e->getMessage());
}

// ─── レスポンス ──────────────────────────────────────────────

$skillData = Chess::SKILL_DATA[$skillId] ?? null;

echo json_encode([
    'success'         => true,
    'skill_name'      => $skillData['name'] ?? '',
    'turn'            => $newState['turn'],
    'board'           => $newState['board'],
    'traps'           => $newState['traps'],
    'rematch_pending' => $newState['rematchPending'],
    'current_player'  => $newState['currentPlayer'],
    'is_my_turn'      => $newState['currentPlayer'] === $player,
]);
