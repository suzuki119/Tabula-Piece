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

$stmt = $db->prepare('SELECT stones FROM users WHERE id = ?');
$stmt->execute([$userId]);
$user = $stmt->fetch();
if (!$user) jsonError(404, 'ユーザーが見つかりません');

// 所持キャラ数
$stmt = $db->prepare('SELECT COUNT(*) FROM user_characters WHERE user_id = ?');
$stmt->execute([$userId]);
$ownedCount = (int)$stmt->fetchColumn();

// 全キャラ数
$stmt = $db->query('SELECT COUNT(*) FROM characters');
$totalCount = (int)$stmt->fetchColumn();

echo json_encode([
    'stones'      => (int)$user['stones'],
    'owned'       => $ownedCount,
    'total'       => $totalCount,
    'cost_single' => 100,
    'cost_multi'  => 1000,
    'rates'       => ['SSR' => 3, 'SR' => 12, 'R' => 35, 'N' => 50],
]);
