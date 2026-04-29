<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST')    { http_response_code(405); echo json_encode(['error' => 'Method Not Allowed']); exit; }

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../game/match_starter.php';

function jsonError(int $code, string $msg): void {
    http_response_code($code);
    echo json_encode(['error' => $msg]);
    exit;
}

$body   = json_decode(file_get_contents('php://input'), true);
$userId = (int)($body['user_id'] ?? 0);
$deckId = (int)($body['deck_id'] ?? 0);

if (!$userId || !$deckId) jsonError(400, 'user_id と deck_id は必須です');

$db = getDb();

// デッキ確認
$stmt = $db->prepare('SELECT id FROM decks WHERE id = ? AND user_id = ? LIMIT 1');
$stmt->execute([$deckId, $userId]);
if (!$stmt->fetch()) jsonError(404, 'デッキが見つかりません');

// 既存の待機ルームがあれば再利用
$stmt = $db->prepare('SELECT id, room_code FROM matches WHERE player1_id = ? AND status = "waiting" AND room_code IS NOT NULL LIMIT 1');
$stmt->execute([$userId]);
$existing = $stmt->fetch();
if ($existing) {
    echo json_encode(['match_id' => (int)$existing['id'], 'room_code' => $existing['room_code']]);
    exit;
}

// ユニークなルームコードを生成
$chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
do {
    $code = '';
    for ($i = 0; $i < 6; $i++) $code .= $chars[random_int(0, strlen($chars) - 1)];
    $stmt = $db->prepare('SELECT id FROM matches WHERE room_code = ? LIMIT 1');
    $stmt->execute([$code]);
} while ($stmt->fetch());

$matchId = createWaitingMatch($db, $userId, $deckId, $code);

echo json_encode(['match_id' => $matchId, 'room_code' => $code]);
