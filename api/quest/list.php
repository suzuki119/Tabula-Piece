<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';

$userId = requireAuth();
$db     = getDb();

// クエスト一覧
$stmt = $db->query('
    SELECT q.id, q.chapter, q.stage, q.name, q.description, q.difficulty,
           q.cpu_deck_class, q.reward_gem, q.reward_character_id, q.sort_order,
           c.name AS reward_character_name, c.rarity AS reward_character_rarity
    FROM quests q
    LEFT JOIN characters c ON c.id = q.reward_character_id
    ORDER BY q.chapter ASC, q.sort_order ASC, q.stage ASC
');
$quests = $stmt->fetchAll();

// クリア状況
$stmt = $db->prepare('SELECT quest_id, cleared_at FROM quest_clears WHERE user_id = ?');
$stmt->execute([$userId]);
$clears = [];
foreach ($stmt->fetchAll() as $r) {
    $clears[(int)$r['quest_id']] = $r['cleared_at'];
}

// 章ごとにグループ化
$chapters = [];
foreach ($quests as $q) {
    $ch = (int)$q['chapter'];
    if (!isset($chapters[$ch])) {
        $chapters[$ch] = ['chapter' => $ch, 'stages' => []];
    }
    $chapters[$ch]['stages'][] = [
        'id'                      => (int)$q['id'],
        'stage'                   => (int)$q['stage'],
        'name'                    => $q['name'],
        'description'             => $q['description'],
        'difficulty'              => $q['difficulty'],
        'cpu_deck_class'          => $q['cpu_deck_class'],
        'reward_gem'              => (int)$q['reward_gem'],
        'reward_character_id'     => $q['reward_character_id'] !== null ? (int)$q['reward_character_id'] : null,
        'reward_character_name'   => $q['reward_character_name'],
        'reward_character_rarity' => $q['reward_character_rarity'],
        'cleared'                 => isset($clears[(int)$q['id']]),
        'cleared_at'              => $clears[(int)$q['id']] ?? null,
    ];
}

echo json_encode([
    'success'  => true,
    'chapters' => array_values($chapters),
]);
