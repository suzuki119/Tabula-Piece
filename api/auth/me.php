<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';

$userId = requireAuth();

$db   = getDb();
$stmt = $db->prepare('SELECT id, name, stones FROM users WHERE id = ? LIMIT 1');
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    http_response_code(404);
    echo json_encode(['error' => 'ユーザーが見つかりません']);
    exit;
}

echo json_encode([
    'user_id' => (int)$user['id'],
    'name'    => $user['name'],
    'stones'  => (int)$user['stones'],
]);
