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

// 自分のデッキ確認
$stmt = $db->prepare('SELECT id FROM decks WHERE id = ? AND user_id = ? LIMIT 1');
$stmt->execute([$deckId, $userId]);
if (!$stmt->fetch()) jsonError(404, 'デッキが見つかりません');

// 自分が作成済みの待機試合があれば返す（重複防止）
$stmt = $db->prepare('SELECT id FROM matches WHERE player1_id = ? AND status = "waiting" AND player2_id IS NULL AND room_code IS NULL ORDER BY id DESC LIMIT 1');
$stmt->execute([$userId]);
$existing = $stmt->fetch();
if ($existing) {
    echo json_encode(['status' => 'waiting', 'match_id' => (int)$existing['id']]);
    exit;
}

// 他のユーザーが作成した待機試合を探す
$stmt = $db->prepare('SELECT id FROM matches WHERE player1_id != ? AND status = "waiting" AND player2_id IS NULL AND room_code IS NULL ORDER BY id ASC LIMIT 1');
$stmt->execute([$userId]);
$waitingMatch = $stmt->fetch();

if ($waitingMatch) {
    try {
        $result = joinAndStartMatch($db, (int)$waitingMatch['id'], $userId, $deckId);
        echo json_encode(['status' => 'matched', 'match_id' => $result['match_id'], 'player_role' => 'player2']);
    } catch (Exception $e) {
        // 競合（他のユーザーが先に参加）→ 新規待機へ
        $matchId = createWaitingMatch($db, $userId, $deckId);
        echo json_encode(['status' => 'waiting', 'match_id' => $matchId]);
    }
} else {
    // 待機試合なし → 新規作成
    $matchId = createWaitingMatch($db, $userId, $deckId);
    echo json_encode(['status' => 'waiting', 'match_id' => $matchId]);
}
