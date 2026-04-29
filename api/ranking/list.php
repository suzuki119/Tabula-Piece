<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';

$myUserId = requireAuth();
$db = getDb();

// 全ユーザーの戦績集計
$stmt = $db->query('
    SELECT
        u.id,
        u.name,
        COUNT(m.id)                                                        AS total,
        SUM(CASE WHEN m.winner_id = u.id THEN 1 ELSE 0 END)               AS wins,
        SUM(CASE WHEN m.winner_id IS NOT NULL AND m.winner_id != u.id
                  AND (m.player1_id = u.id OR m.player2_id = u.id)
                 THEN 1 ELSE 0 END)                                        AS losses,
        SUM(CASE WHEN m.winner_id IS NULL
                  AND (m.player1_id = u.id OR m.player2_id = u.id)
                 THEN 1 ELSE 0 END)                                        AS draws
    FROM users u
    JOIN matches m ON (m.player1_id = u.id OR m.player2_id = u.id)
    WHERE m.status = \'finished\'
    GROUP BY u.id, u.name
    ORDER BY wins DESC, total ASC
    LIMIT 50
');

$rows = $stmt->fetchAll();

$ranking = [];
foreach ($rows as $i => $r) {
    $ranking[] = [
        'rank'    => $i + 1,
        'user_id' => (int)$r['id'],
        'name'    => $r['name'],
        'total'   => (int)$r['total'],
        'wins'    => (int)$r['wins'],
        'losses'  => (int)$r['losses'],
        'draws'   => (int)$r['draws'],
        'is_me'   => (int)$r['id'] === $myUserId,
    ];
}

// 自分の戦績（ランキング外でも返す）
$myStmt = $db->prepare('
    SELECT
        COUNT(m.id)                                                        AS total,
        SUM(CASE WHEN m.winner_id = ? THEN 1 ELSE 0 END)                  AS wins,
        SUM(CASE WHEN m.winner_id IS NOT NULL AND m.winner_id != ?
                  AND (m.player1_id = ? OR m.player2_id = ?)
                 THEN 1 ELSE 0 END)                                        AS losses,
        SUM(CASE WHEN m.winner_id IS NULL
                  AND (m.player1_id = ? OR m.player2_id = ?)
                 THEN 1 ELSE 0 END)                                        AS draws
    FROM matches m
    WHERE (m.player1_id = ? OR m.player2_id = ?)
      AND m.status = \'finished\'
');
$myStmt->execute([$myUserId, $myUserId, $myUserId, $myUserId, $myUserId, $myUserId, $myUserId, $myUserId]);
$my = $myStmt->fetch();

echo json_encode([
    'ranking' => $ranking,
    'my_stats' => [
        'total'  => (int)$my['total'],
        'wins'   => (int)$my['wins'],
        'losses' => (int)$my['losses'],
        'draws'  => (int)$my['draws'],
    ],
]);
