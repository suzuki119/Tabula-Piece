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

$body     = json_decode(file_get_contents('php://input'), true);
$userId   = (int)($body['user_id']   ?? 0);
$deckId   = (int)($body['deck_id']   ?? 0);
$roomCode = strtoupper(trim($body['room_code'] ?? ''));

if (!$userId || !$deckId || !$roomCode) jsonError(400, 'user_id・deck_id・room_code は必須です');

$db = getDb();

// デッキ確認
$stmt = $db->prepare('SELECT id FROM decks WHERE id = ? AND user_id = ? LIMIT 1');
$stmt->execute([$deckId, $userId]);
if (!$stmt->fetch()) jsonError(404, 'デッキが見つかりません');

// ルームを検索
$stmt = $db->prepare('SELECT id, player1_id FROM matches WHERE room_code = ? AND status = "waiting" AND player2_id IS NULL LIMIT 1');
$stmt->execute([$roomCode]);
$match = $stmt->fetch();

if (!$match) jsonError(404, 'ルームが見つかりません（コードを確認してください）');
if ((int)$match['player1_id'] === $userId) jsonError(422, '自分のルームには参加できません');

try {
    $result = joinAndStartMatch($db, (int)$match['id'], $userId, $deckId);
    echo json_encode(['success' => true, 'match_id' => $result['match_id'], 'player_role' => 'player2']);
} catch (Exception $e) {
    jsonError(409, 'ルームへの参加に失敗しました: ' . $e->getMessage());
}
