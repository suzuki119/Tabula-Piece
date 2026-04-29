<?php
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';

startSession();

function jsonError(int $code, string $msg): void {
    http_response_code($code);
    echo json_encode(['error' => $msg]);
    exit;
}

$body     = json_decode(file_get_contents('php://input'), true);
$email    = trim($body['email']    ?? '');
$password = trim($body['password'] ?? '');

if (!$email || !$password) {
    jsonError(400, 'メールアドレスとパスワードは必須です');
}

$db = getDb();

$stmt = $db->prepare('SELECT id, name, password_hash, stones FROM users WHERE email = ? LIMIT 1');
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password_hash'] ?? '')) {
    jsonError(401, 'メールアドレスまたはパスワードが違います');
}

session_regenerate_id(true);
$_SESSION['user_id'] = (int)$user['id'];

echo json_encode([
    'user_id' => (int)$user['id'],
    'name'    => $user['name'],
    'stones'  => (int)$user['stones'],
]);
