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

$stmt = $db->prepare('SELECT * FROM decks WHERE user_id = ? ORDER BY id');
$stmt->execute([$userId]);
$decks = $stmt->fetchAll();

// 各デッキのキャラクター情報を付加
$charIds = [];
foreach ($decks as $d) {
    foreach (['pawn_char_id','knight_char_id','bishop_char_id','rook_char_id','queen_char_id','king_char_id'] as $col) {
        if ($d[$col]) $charIds[] = (int)$d[$col];
    }
}

$charMap = [];
if ($charIds) {
    $placeholders = implode(',', array_fill(0, count($charIds), '?'));
    $stmt = $db->prepare("
        SELECT c.*, sa.name AS active_skill_name, sp.name AS passive_skill_name
        FROM characters c
        LEFT JOIN skills sa ON sa.id = c.active_skill_id
        LEFT JOIN skills sp ON sp.id = c.passive_skill_id
        WHERE c.id IN ($placeholders)
    ");
    $stmt->execute(array_unique($charIds));
    foreach ($stmt->fetchAll() as $c) {
        $charMap[(int)$c['id']] = [
            'id'         => (int)$c['id'],
            'name'       => $c['name'],
            'piece_class'=> $c['piece_class'],
            'rarity'     => $c['rarity'],
            'active_skill_id'  => $c['active_skill_id']  ? (int)$c['active_skill_id']  : null,
            'passive_skill_id' => $c['passive_skill_id'] ? (int)$c['passive_skill_id'] : null,
            'active_skill_name' => $c['active_skill_name'],
            'passive_skill_name'=> $c['passive_skill_name'],
        ];
    }
}

$result = [];
foreach ($decks as $d) {
    $slots = [];
    foreach (['pawn','knight','bishop','rook','queen','king'] as $class) {
        $charId = $d["{$class}_char_id"] ? (int)$d["{$class}_char_id"] : null;
        $slots[$class] = [
            'char_id'   => $charId,
            'character' => $charId ? ($charMap[$charId] ?? null) : null,
        ];
    }
    $result[] = [
        'id'    => (int)$d['id'],
        'name'  => $d['name'],
        'slots' => $slots,
    ];
}

echo json_encode($result);
