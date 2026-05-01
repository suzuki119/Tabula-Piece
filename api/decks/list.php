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

// 標準スロットのキャラクター情報を一括取得
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
            'id'                 => (int)$c['id'],
            'name'               => $c['name'],
            'piece_class'        => $c['piece_class'],
            'class'              => $c['class'] ?? null,
            'rarity'             => $c['rarity'],
            'active_skill_id'    => $c['active_skill_id']   ? (int)$c['active_skill_id']  : null,
            'passive_skill_id'   => $c['passive_skill_id']  ? (int)$c['passive_skill_id'] : null,
            'active_skill_name'  => $c['active_skill_name'],
            'passive_skill_name' => $c['passive_skill_name'],
        ];
    }
}

// クラス駒配置を一括取得
$deckIds = array_column($decks, 'id');
$classPiecesMap = [];
if ($deckIds) {
    $placeholders = implode(',', array_fill(0, count($deckIds), '?'));
    $stmt = $db->prepare("
        SELECT
            dcp.deck_id, dcp.board_col, dcp.board_row,
            c.id AS char_id, c.name AS char_name, c.piece_class, c.class, c.rarity,
            sa.name AS active_skill_name, sp.name AS passive_skill_name
        FROM deck_class_pieces dcp
        JOIN characters c ON c.id = dcp.character_id
        LEFT JOIN skills sa ON sa.id = c.active_skill_id
        LEFT JOIN skills sp ON sp.id = c.passive_skill_id
        WHERE dcp.deck_id IN ($placeholders)
        ORDER BY dcp.deck_id, dcp.board_row, dcp.board_col
    ");
    $stmt->execute($deckIds);
    foreach ($stmt->fetchAll() as $row) {
        $classPiecesMap[(int)$row['deck_id']][] = [
            'character_id' => (int)$row['char_id'],
            'name'         => $row['char_name'],
            'piece_class'  => $row['piece_class'],
            'class'        => $row['class'],
            'rarity'       => $row['rarity'],
            'active_skill_name'  => $row['active_skill_name'],
            'passive_skill_name' => $row['passive_skill_name'],
            'col'          => (int)$row['board_col'],
            'row'          => (int)$row['board_row'],
        ];
    }
}

$result = [];
foreach ($decks as $d) {
    $did = (int)$d['id'];
    $slots = [];
    foreach (['pawn','knight','bishop','rook','queen','king'] as $type) {
        $charId = $d["{$type}_char_id"] ? (int)$d["{$type}_char_id"] : null;
        $slots[$type] = [
            'char_id'   => $charId,
            'character' => $charId ? ($charMap[$charId] ?? null) : null,
        ];
    }
    $result[] = [
        'id'           => $did,
        'name'         => $d['name'],
        'class'        => $d['class'] ?? 'neutral',
        'slots'        => $slots,
        'class_pieces' => $classPiecesMap[$did] ?? [],
    ];
}

echo json_encode($result);
