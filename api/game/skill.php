<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Methods: POST, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST')    { http_response_code(405); echo json_encode(['error' => 'Method Not Allowed']); exit; }

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/Chess.php';

function jsonError(int $code, string $message): void {
    http_response_code($code);
    echo json_encode(['error' => $message]);
    exit;
}

// ─── リクエスト取得 ──────────────────────────────────────────

$userId = requireAuth();

$body = json_decode(file_get_contents('php://input'), true);
if (!$body) jsonError(400, 'リクエストボディが不正です');

$matchId  = (int)($body['match_id']  ?? 0);
$fromSq   = trim($body['from']       ?? '');
$skillId  = (int)($body['skill_id']  ?? 0);
$targetSq = isset($body['target']) && $body['target'] !== '' ? trim($body['target']) : null;

if (!$matchId) {
    jsonError(400, 'match_id は必須です');
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
    'board'            => $gameData['board'],
    'traps'            => $gameData['traps'],
    'rematchPending'   => $gameData['rematchPending'],
    'skillOpportunity' => $gameData['skillOpportunity'],
    'currentPlayer'    => $match['current_player'],
    'turn'             => (int)$match['current_turn'],
    'maxTurns'         => 30,
    'status'           => $match['status'],
    'winner'           => null,
    'endReason'        => null,
];

// ─── スキップ（skill_id = 0）────────────────────────────────

if ($skillId === 0) {
    $opportunity = $state['skillOpportunity'] ?? null;
    if (!$opportunity || $opportunity['player'] !== $player) {
        jsonError(422, 'スキップできるスキル機会がありません');
    }

    $opponentPlayer = $player === 'player1' ? 'player2' : 'player1';
    $nextTurn       = ($player === 'player2') ? $state['turn'] + 1 : $state['turn'];

    $newState = array_merge($state, [
        'currentPlayer'    => $opponentPlayer,
        'turn'             => $nextTurn,
        'skillOpportunity' => null,
    ]);

    $db->beginTransaction();
    try {
        $ins = $db->prepare('INSERT INTO board_states (match_id, turn, board_json) VALUES (?, ?, ?)');
        $ins->execute([$matchId, $newState['turn'], Chess::encodeGameData($newState)]);

        $upd = $db->prepare('UPDATE matches SET current_turn=?, current_player=?, updated_at=NOW() WHERE id=?');
        $upd->execute([$newState['turn'], $newState['currentPlayer'], $matchId]);

        $db->commit();
    } catch (Exception $e) {
        $db->rollBack();
        jsonError(500, 'DB更新に失敗しました: ' . $e->getMessage());
    }

    echo json_encode([
        'success'          => true,
        'skipped'          => true,
        'turn'             => $newState['turn'],
        'board'            => $newState['board'],
        'traps'            => $newState['traps'],
        'rematch_pending'  => null,
        'skill_opportunity'=> null,
        'current_player'   => $newState['currentPlayer'],
        'is_my_turn'       => false,
    ]);
    exit;
}

// ─── スキル実行 ──────────────────────────────────────────────

if (!$fromSq || !$skillId) {
    jsonError(400, 'from, skill_id は必須です（スキップ以外）');
}

try {
    $newState = Chess::executeSkill($state, $player, $fromSq, $skillId, $targetSq);
} catch (RuntimeException $e) {
    jsonError(422, $e->getMessage());
}

// ─── DB更新（トランザクション） ──────────────────────────────

$db->beginTransaction();
try {
    $ins = $db->prepare('INSERT INTO board_states (match_id, turn, board_json) VALUES (?, ?, ?)');
    $ins->execute([$matchId, $newState['turn'], Chess::encodeGameData($newState)]);

    $upd = $db->prepare('UPDATE matches SET current_turn=?, current_player=?, updated_at=NOW() WHERE id=?');
    $upd->execute([$newState['turn'], $newState['currentPlayer'], $matchId]);

    // skill_logs
    $log = $db->prepare('INSERT INTO skill_logs (match_id, turn, user_id, skill_id, target_square) VALUES (?, ?, ?, ?, ?)');
    $log->execute([$matchId, $state['turn'], $userId, $skillId, $targetSq]);

    $db->commit();
} catch (Exception $e) {
    $db->rollBack();
    jsonError(500, 'DB更新に失敗しました: ' . $e->getMessage());
}

// ─── レスポンス ──────────────────────────────────────────────

$skillData = Chess::SKILL_DATA[$skillId] ?? null;

echo json_encode([
    'success'          => true,
    'skill_name'       => $skillData['name'] ?? '',
    'turn'             => $newState['turn'],
    'board'            => $newState['board'],
    'traps'            => $newState['traps'],
    'rematch_pending'  => $newState['rematchPending'],
    'skill_opportunity'=> $newState['skillOpportunity'],
    'current_player'   => $newState['currentPlayer'],
    'is_my_turn'       => $newState['currentPlayer'] === $player,
]);
