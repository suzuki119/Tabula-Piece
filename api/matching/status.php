<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';

function jsonError(int $code, string $msg): void {
    http_response_code($code);
    echo json_encode(['error' => $msg]);
    exit;
}

$userId  = requireAuth();
$matchId = (int)($_GET['match_id'] ?? 0);
if (!$matchId) jsonError(400, 'match_id は必須です');

$db = getDb();

$stmt = $db->prepare('SELECT * FROM matches WHERE id = ? LIMIT 1');
$stmt->execute([$matchId]);
$match = $stmt->fetch();
if (!$match) jsonError(404, '試合が見つかりません');

$playerRole = null;
if ((int)$match['player1_id'] === $userId) $playerRole = 'player1';
if ((int)$match['player2_id'] === $userId) $playerRole = 'player2';

echo json_encode([
    'status'      => $match['status'],
    'match_id'    => (int)$match['id'],
    'player_role' => $playerRole,
]);
