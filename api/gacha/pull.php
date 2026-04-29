<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST')    { http_response_code(405); echo json_encode(['error' => 'Method Not Allowed']); exit; }

require_once __DIR__ . '/../config/db.php';

function jsonError(int $code, string $msg): void {
    http_response_code($code);
    echo json_encode(['error' => $msg]);
    exit;
}

// レアリティ確率（cumulative）
const RARITY_WEIGHTS = ['SSR' => 3, 'SR' => 12, 'R' => 35, 'N' => 50];
const COST_SINGLE = 100;
const COST_MULTI  = 1000;
const DUPE_RETURN = 50;

function rollRarity(): string {
    $roll = random_int(1, 100);
    $cum  = 0;
    foreach (RARITY_WEIGHTS as $rarity => $weight) {
        $cum += $weight;
        if ($roll <= $cum) return $rarity;
    }
    return 'N';
}

$body   = json_decode(file_get_contents('php://input'), true);
$userId = (int)($body['user_id'] ?? 0);
$mode   = $body['mode'] ?? 'single'; // 'single' or 'multi'

if (!$userId) jsonError(400, 'user_id は必須です');
if (!in_array($mode, ['single', 'multi'])) jsonError(400, 'mode は single または multi です');

$cost    = $mode === 'multi' ? COST_MULTI : COST_SINGLE;
$pullCount = $mode === 'multi' ? 10 : 1;

$db = getDb();

// 石残高確認
$stmt = $db->prepare('SELECT stones FROM users WHERE id = ? FOR UPDATE');
$db->beginTransaction();
$stmt->execute([$userId]);
$user = $stmt->fetch();
if (!$user) { $db->rollBack(); jsonError(404, 'ユーザーが見つかりません'); }
if ((int)$user['stones'] < $cost) { $db->rollBack(); jsonError(422, 'ガチャ石が足りません'); }

// 全キャラをレアリティ別に取得
$stmt = $db->prepare('SELECT id, name, piece_class, rarity, active_skill_id, passive_skill_id FROM characters ORDER BY id');
$stmt->execute();
$allChars = $stmt->fetchAll();

$byRarity = ['N' => [], 'R' => [], 'SR' => [], 'SSR' => []];
foreach ($allChars as $c) {
    $byRarity[$c['rarity']][] = $c;
}

// 所持済みキャラ確認
$stmt = $db->prepare('SELECT character_id FROM user_characters WHERE user_id = ?');
$stmt->execute([$userId]);
$owned = array_flip(array_column($stmt->fetchAll(), 'character_id'));

// ガチャを引く
$results = [];
$stonesRefunded = 0;

for ($i = 0; $i < $pullCount; $i++) {
    $rarity = rollRarity();

    // 10連保証: 最後の1枠がN/Rなら SR に格上げ
    if ($mode === 'multi' && $i === $pullCount - 1) {
        $hasHighRarity = false;
        foreach ($results as $r) {
            if (in_array($r['rarity'], ['SR', 'SSR'])) { $hasHighRarity = true; break; }
        }
        if (!$hasHighRarity && in_array($rarity, ['N', 'R'])) $rarity = 'SR';
    }

    // レアリティのキャラからランダム選択（候補がなければ下位レアに fallback）
    $candidates = $byRarity[$rarity];
    if (empty($candidates)) {
        foreach (['SR','R','N'] as $fb) {
            if (!empty($byRarity[$fb])) { $candidates = $byRarity[$fb]; break; }
        }
    }
    $char = $candidates[array_rand($candidates)];

    $isNew = !isset($owned[(int)$char['id']]);
    if ($isNew) {
        $owned[(int)$char['id']] = true;
    } else {
        $stonesRefunded += DUPE_RETURN;
    }

    $results[] = [
        'character_id' => (int)$char['id'],
        'name'         => $char['name'],
        'piece_class'  => $char['piece_class'],
        'rarity'       => $char['rarity'],
        'is_new'       => $isNew,
    ];
}

// DB更新
try {
    // 石消費 + 重複返還
    $netCost = $cost - $stonesRefunded;
    $stmt = $db->prepare('UPDATE users SET stones = stones - ? WHERE id = ?');
    $stmt->execute([$netCost, $userId]);

    // 新規キャラを所持に追加
    $insStmt = $db->prepare('INSERT IGNORE INTO user_characters (user_id, character_id) VALUES (?, ?)');
    // ガチャログ挿入
    $logStmt = $db->prepare('INSERT INTO gacha_logs (user_id, character_id, rarity, is_new, stones_spent) VALUES (?, ?, ?, ?, ?)');
    $stonesPerPull = (int)($cost / $pullCount);
    foreach ($results as $r) {
        if ($r['is_new']) $insStmt->execute([$userId, $r['character_id']]);
        $logStmt->execute([$userId, $r['character_id'], $r['rarity'], $r['is_new'] ? 1 : 0, $stonesPerPull]);
    }

    // 残石取得
    $stmt = $db->prepare('SELECT stones FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    $newStones = (int)$stmt->fetchColumn();

    $db->commit();
} catch (Exception $e) {
    $db->rollBack();
    jsonError(500, 'ガチャ処理に失敗しました: ' . $e->getMessage());
}

echo json_encode([
    'results'          => $results,
    'stones_spent'     => $cost,
    'stones_refunded'  => $stonesRefunded,
    'stones_remaining' => $newStones,
]);
