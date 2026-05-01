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

$body = json_decode(file_get_contents('php://input'), true);
if (!$body) jsonError(400, 'リクエストボディが不正です');

$deckId      = isset($body['deck_id']) ? (int)$body['deck_id'] : null;
$name        = trim($body['name'] ?? 'マイデッキ');
$deckClass   = $body['class'] ?? 'neutral';
$slots       = $body['slots'] ?? [];
$classPieces = $body['class_pieces'] ?? [];

$validClasses = ['neutral','witch','blade','architect','paladin','dominant'];
if (!in_array($deckClass, $validClasses)) $deckClass = 'neutral';

$pieceTypes = ['pawn','knight','bishop','rook','queen','king'];
$charIds = [];
foreach ($pieceTypes as $type) {
    $charIds[$type] = isset($slots[$type]) && $slots[$type] ? (int)$slots[$type] : null;
}

$db = getDb();

// 所持チェック: 標準スロット
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

// 所持チェック: クラス駒
$classPieceIds = array_filter(array_column($classPieces, 'character_id'));
if ($classPieceIds) {
    $placeholders = implode(',', array_fill(0, count($classPieceIds), '?'));
    $stmt = $db->prepare(
        "SELECT character_id FROM user_characters WHERE user_id = ? AND character_id IN ($placeholders)"
    );
    $stmt->execute(array_merge([$userId], array_values($classPieceIds)));
    $owned = array_column($stmt->fetchAll(), 'character_id');
    foreach ($classPieceIds as $id) {
        if (!in_array($id, $owned)) jsonError(403, "クラス駒(id:{$id})を所持していません");
    }
}

$db->beginTransaction();
try {
    if ($deckId) {
        $stmt = $db->prepare('SELECT id FROM decks WHERE id = ? AND user_id = ? LIMIT 1');
        $stmt->execute([$deckId, $userId]);
        if (!$stmt->fetch()) jsonError(404, 'デッキが見つかりません');

        $db->prepare('
            UPDATE decks SET
                name           = ?,
                class          = ?,
                pawn_char_id   = ?,
                knight_char_id = ?,
                bishop_char_id = ?,
                rook_char_id   = ?,
                queen_char_id  = ?,
                king_char_id   = ?
            WHERE id = ? AND user_id = ?
        ')->execute([
            $name, $deckClass,
            $charIds['pawn'], $charIds['knight'], $charIds['bishop'],
            $charIds['rook'],  $charIds['queen'],  $charIds['king'],
            $deckId, $userId,
        ]);
    } else {
        $ins = $db->prepare('
            INSERT INTO decks (user_id, name, class, pawn_char_id, knight_char_id, bishop_char_id, rook_char_id, queen_char_id, king_char_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ');
        $ins->execute([
            $userId, $name, $deckClass,
            $charIds['pawn'], $charIds['knight'], $charIds['bishop'],
            $charIds['rook'],  $charIds['queen'],  $charIds['king'],
        ]);
        $deckId = (int)$db->lastInsertId();
    }

    // クラス駒配置を入れ替え
    $db->prepare('DELETE FROM deck_class_pieces WHERE deck_id = ?')->execute([$deckId]);
    if ($classPieces) {
        $ins = $db->prepare('INSERT INTO deck_class_pieces (deck_id, character_id, board_col, board_row) VALUES (?,?,?,?)');
        foreach ($classPieces as $cp) {
            $cid = isset($cp['character_id']) ? (int)$cp['character_id'] : 0;
            $col = isset($cp['col']) ? (int)$cp['col'] : 0;
            $row = isset($cp['row']) ? (int)$cp['row'] : 0;
            if ($cid && $col >= 0 && $col <= 5 && $row >= 0 && $row <= 2) {
                $ins->execute([$deckId, $cid, $col, $row]);
            }
        }
    }

    $db->commit();
} catch (Exception $e) {
    $db->rollBack();
    jsonError(500, 'デッキ保存に失敗しました: ' . $e->getMessage());
}

echo json_encode(['success' => true, 'deck_id' => $deckId]);
