<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST')    { http_response_code(405); echo json_encode(['error' => 'Method Not Allowed']); exit; }

require_once __DIR__ . '/../config/db.php';

function jsonError(int $code, string $msg): void {
    http_response_code($code);
    echo json_encode(['error' => $msg]);
    exit;
}

$body    = json_decode(file_get_contents('php://input'), true);
$matchId = (int)($body['match_id'] ?? 0);
$userId  = (int)($body['user_id']  ?? 0);

if (!$matchId || !$userId) jsonError(400, 'match_id と user_id は必須です');

$db = getDb();

$stmt = $db->prepare('SELECT * FROM matches WHERE id = ? AND status = "in_progress" LIMIT 1');
$stmt->execute([$matchId]);
$match = $stmt->fetch();
if (!$match) jsonError(404, '進行中の試合が見つかりません');

// 降参者を確認し、勝者を決定
if ((int)$match['player1_id'] === $userId) {
    $winnerId = (int)$match['player2_id'];
} elseif ((int)$match['player2_id'] === $userId) {
    $winnerId = (int)$match['player1_id'];
} else {
    jsonError(403, 'この試合の参加者ではありません');
}

$stmt = $db->prepare('
    UPDATE matches SET status = "finished", winner_id = ?, end_reason = "checkmate"
    WHERE id = ?
');
$stmt->execute([$winnerId, $matchId]);

echo json_encode(['success' => true, 'winner_id' => $winnerId]);
