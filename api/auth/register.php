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
$name     = trim($body['name']     ?? '');
$email    = trim($body['email']    ?? '');
$password = trim($body['password'] ?? '');

if (!$name || !$email || !$password) {
    jsonError(400, '名前・メールアドレス・パスワードは必須です');
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonError(400, '有効なメールアドレスを入力してください');
}
if (mb_strlen($password) < 6) {
    jsonError(400, 'パスワードは6文字以上で設定してください');
}

$db = getDb();

$stmt = $db->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
$stmt->execute([$email]);
if ($stmt->fetch()) {
    jsonError(409, 'このメールアドレスはすでに使用されています');
}

$hash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $db->prepare('INSERT INTO users (name, email, password_hash, stones) VALUES (?, ?, ?, 3000)');
$stmt->execute([$name, $email, $hash]);
$userId = (int)$db->lastInsertId();

$_SESSION['user_id'] = $userId;

echo json_encode(['user_id' => $userId, 'name' => $name, 'stones' => 3000]);
