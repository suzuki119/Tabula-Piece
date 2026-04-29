<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';

function jsonError(int $code, string $msg): void {
    http_response_code($code);
    echo json_encode(['error' => $msg]);
    exit;
}

$userId = requireAuth();

$db = getDb();

$stmt = $db->prepare('
    SELECT
        m.id,
        m.status,
        m.current_turn,
        m.current_player,
        m.winner_id,
        m.end_reason,
        m.created_at,
        m.updated_at,
        u1.name AS player1_name,
        u2.name AS player2_name,
        CASE WHEN m.player1_id = ? THEN "player1" ELSE "player2" END AS my_role
    FROM matches m
    JOIN users u1 ON u1.id = m.player1_id
    LEFT JOIN users u2 ON u2.id = m.player2_id
    WHERE (m.player1_id = ? OR m.player2_id = ?)
      AND m.status IN ("in_progress", "finished")
    ORDER BY m.updated_at DESC
    LIMIT 30
');
$stmt->execute([$userId, $userId, $userId]);
$rows = $stmt->fetchAll();

$result = [];
foreach ($rows as $r) {
    $isMyTurn = $r['status'] === 'in_progress' && $r['current_player'] === $r['my_role'];
    $result[] = [
        'id'           => (int)$r['id'],
        'status'       => $r['status'],
        'my_role'      => $r['my_role'],
        'current_turn' => (int)$r['current_turn'],
        'is_my_turn'   => $isMyTurn,
        'opponent'     => $r['my_role'] === 'player1' ? $r['player2_name'] : $r['player1_name'],
        'winner_id'    => $r['winner_id'] ? (int)$r['winner_id'] : null,
        'end_reason'   => $r['end_reason'],
        'updated_at'   => $r['updated_at'],
    ];
}

echo json_encode($result);
