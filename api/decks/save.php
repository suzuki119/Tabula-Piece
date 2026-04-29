<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Methods: POST, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST')    { http_response_code(405); echo json_encode(['error' => 'Method Not Allowed']); exit; }

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';

function jsonError(int $code, string $msg): void {
    http_response_code($code);
    echo json_encode(['error' => $msg]);
    exit;
}

$userId = requireAuth();

$body   = json_decode(file_get_contents('php://input'), true);
if (!$body) jsonError(400, 'リクエストボディが不正です');

$deckId = isset($body['deck_id']) ? (int)$body['deck_id'] : null;
$name   = trim($body['name'] ?? 'マイデッキ');
$slots  = $body['slots'] ?? [];

$classes = ['pawn','knight','bishop','rook','queen','king'];
$charIds = [];
foreach ($classes as $class) {
    $charIds[$class] = isset($slots[$class]) && $slots[$class] ? (int)$slots[$class] : null;
}

$db = getDb();

// 所持チェック: 指定されたキャラが全てユーザーの所持品か確認
$ids = array_filter(array_values($charIds));
if ($ids) {
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $db->prepare(
        "SELECT character_id FROM user_characters WHERE user_id = ? AND character_id IN ($placeholders)"
    );
    $stmt->execute(array_merge([$userId], $ids));
    $owned = array_column($stmt->fetchAll(), 'character_id');
    foreach ($ids as $id) {
        if (!in_array($id, $owned)) jsonError(403, "キャラクター(id:{$id})を所持していません");
    }
}

if ($deckId) {
    // 既存デッキの更新
    $stmt = $db->prepare('SELECT id FROM decks WHERE id = ? AND user_id = ? LIMIT 1');
    $stmt->execute([$deckId, $userId]);
    if (!$stmt->fetch()) jsonError(404, 'デッキが見つかりません');

    $upd = $db->prepare('
        UPDATE decks SET
            name            = ?,
            pawn_char_id    = ?,
            knight_char_id  = ?,
            bishop_char_id  = ?,
            rook_char_id    = ?,
            queen_char_id   = ?,
            king_char_id    = ?
        WHERE id = ? AND user_id = ?
    ');
    $upd->execute([
        $name,
        $charIds['pawn'], $charIds['knight'], $charIds['bishop'],
        $charIds['rook'],  $charIds['queen'],  $charIds['king'],
        $deckId, $userId,
    ]);
} else {
    // 新規デッキ作成
    $ins = $db->prepare('
        INSERT INTO decks (user_id, name, pawn_char_id, knight_char_id, bishop_char_id, rook_char_id, queen_char_id, king_char_id)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ');
    $ins->execute([
        $userId, $name,
        $charIds['pawn'], $charIds['knight'], $charIds['bishop'],
        $charIds['rook'],  $charIds['queen'],  $charIds['king'],
    ]);
    $deckId = (int)$db->lastInsertId();
}

echo json_encode(['success' => true, 'deck_id' => $deckId]);
