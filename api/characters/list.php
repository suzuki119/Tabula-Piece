<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../config/db.php';

function jsonError(int $code, string $msg): void {
    http_response_code($code);
    echo json_encode(['error' => $msg]);
    exit;
}

$userId = (int)($_GET['user_id'] ?? 0);
if (!$userId) jsonError(400, 'user_id は必須です');

$db = getDb();

// ユーザーが所持しているキャラクター + スキル情報を一括取得
$stmt = $db->prepare('
    SELECT
        c.id,
        c.name,
        c.piece_class,
        c.rarity,
        c.active_skill_id,
        c.passive_skill_id,
        sa.name        AS active_skill_name,
        sa.description AS active_skill_desc,
        sa.max_uses    AS active_skill_max_uses,
        sp.name        AS passive_skill_name,
        sp.description AS passive_skill_desc
    FROM user_characters uc
    JOIN characters c ON c.id = uc.character_id
    LEFT JOIN skills sa ON sa.id = c.active_skill_id
    LEFT JOIN skills sp ON sp.id = c.passive_skill_id
    WHERE uc.user_id = ?
    ORDER BY c.piece_class, c.rarity DESC, c.id
');
$stmt->execute([$userId]);
$rows = $stmt->fetchAll();

$result = [];
foreach ($rows as $r) {
    $result[] = [
        'id'         => (int)$r['id'],
        'name'       => $r['name'],
        'piece_class'=> $r['piece_class'],
        'rarity'     => $r['rarity'],
        'active_skill'  => $r['active_skill_id'] ? [
            'id'          => (int)$r['active_skill_id'],
            'name'        => $r['active_skill_name'],
            'description' => $r['active_skill_desc'],
            'max_uses'    => $r['active_skill_max_uses'] !== null ? (int)$r['active_skill_max_uses'] : null,
        ] : null,
        'passive_skill' => $r['passive_skill_id'] ? [
            'id'          => (int)$r['passive_skill_id'],
            'name'        => $r['passive_skill_name'],
            'description' => $r['passive_skill_desc'],
        ] : null,
    ];
}

echo json_encode($result);
