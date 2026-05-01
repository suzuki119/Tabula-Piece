<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Methods: POST, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST')    { http_response_code(405); echo json_encode(['error' => 'Method Not Allowed']); exit; }

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../game/Chess.php';

function jsonError(int $code, string $msg): void {
    http_response_code($code);
    echo json_encode(['error' => $msg]);
    exit;
}

$userId = requireAuth();

$body = json_decode(file_get_contents('php://input'), true);
if (!$body) jsonError(400, 'リクエストボディが不正です');

$deckId     = (int)($body['deck_id']          ?? 0);
$opponentId = (int)($body['opponent_user_id'] ?? 0);

if (!$deckId) jsonError(400, 'deck_id は必須です');

$db = getDb();

// 自分のデッキ取得
$stmt = $db->prepare('SELECT * FROM decks WHERE id = ? AND user_id = ? LIMIT 1');
$stmt->execute([$deckId, $userId]);
$myDeck = $stmt->fetch();
if (!$myDeck) jsonError(404, 'デッキが見つかりません');

// 相手ユーザー: 未指定なら自分以外の最初のユーザー
if (!$opponentId) {
    $stmt = $db->prepare('SELECT id FROM users WHERE id != ? LIMIT 1');
    $stmt->execute([$userId]);
    $row = $stmt->fetch();
    $opponentId = $row ? (int)$row['id'] : 0;
}
if (!$opponentId) jsonError(422, '対戦相手が見つかりません');

// 相手のデッキ（最新のもの、なければスキルなし）
$stmt = $db->prepare('SELECT * FROM decks WHERE user_id = ? ORDER BY id DESC LIMIT 1');
$stmt->execute([$opponentId]);
$oppDeck = $stmt->fetch() ?: null;

// 両デッキで使われているキャラクターIDを収集（通常スロット）
$allCharIds = [];
$deckCols = ['pawn_char_id','knight_char_id','bishop_char_id','rook_char_id','queen_char_id','king_char_id'];
foreach ($deckCols as $col) {
    if ($myDeck[$col])  $allCharIds[] = (int)$myDeck[$col];
    if ($oppDeck && $oppDeck[$col]) $allCharIds[] = (int)$oppDeck[$col];
}

// クラス配置駒を取得してキャラIDも収集
$cpStmt = $db->prepare('SELECT character_id, board_col, board_row FROM deck_class_pieces WHERE deck_id = ?');
$cpStmt->execute([$deckId]);
$myClassPieces = $cpStmt->fetchAll();
foreach ($myClassPieces as $cp) $allCharIds[] = (int)$cp['character_id'];

$oppClassPieces = [];
if ($oppDeck) {
    $cpStmt->execute([$oppDeck['id']]);
    $oppClassPieces = $cpStmt->fetchAll();
    foreach ($oppClassPieces as $cp) $allCharIds[] = (int)$cp['character_id'];
}

// キャラクター情報を一括取得
$charMap = [];
if ($allCharIds) {
    $unique = array_unique($allCharIds);
    $placeholders = implode(',', array_fill(0, count($unique), '?'));
    $stmt = $db->prepare("SELECT id, piece_class, active_skill_id, passive_skill_id FROM characters WHERE id IN ($placeholders)");
    $stmt->execute($unique);
    foreach ($stmt->fetchAll() as $c) {
        $charMap[(int)$c['id']] = [
            'piece_class'      => $c['piece_class'],
            'active_skill_id'  => $c['active_skill_id']  !== null ? (int)$c['active_skill_id']  : null,
            'passive_skill_id' => $c['passive_skill_id'] !== null ? (int)$c['passive_skill_id'] : null,
        ];
    }
}

// デッキ行をクラス別マップに変換
function deckToClassMap(array $deck): array {
    return [
        'pawn'   => $deck['pawn_char_id']   ? (int)$deck['pawn_char_id']   : null,
        'knight' => $deck['knight_char_id'] ? (int)$deck['knight_char_id'] : null,
        'bishop' => $deck['bishop_char_id'] ? (int)$deck['bishop_char_id'] : null,
        'rook'   => $deck['rook_char_id']   ? (int)$deck['rook_char_id']   : null,
        'queen'  => $deck['queen_char_id']  ? (int)$deck['queen_char_id']  : null,
        'king'   => $deck['king_char_id']   ? (int)$deck['king_char_id']   : null,
    ];
}

// 初期盤面にデッキを適用
$board = Chess::createInitialBoard();
$board = Chess::applyDeckToBoard($board, deckToClassMap($myDeck), $charMap, 'white');
if ($oppDeck) {
    $board = Chess::applyDeckToBoard($board, deckToClassMap($oppDeck), $charMap, 'black');
}
$board = Chess::applyClassPiecesToBoard($board, $myClassPieces,  $charMap, 'white');
$board = Chess::applyClassPiecesToBoard($board, $oppClassPieces, $charMap, 'black');

$initState = [
    'board'            => $board,
    'traps'            => [],
    'rematchPending'   => null,
    'skillOpportunity' => null,
];

// DBに試合を登録
$db->beginTransaction();
try {
    $ins = $db->prepare('
        INSERT INTO matches (player1_id, player2_id, player1_deck_id, player2_deck_id, status, current_turn, current_player)
        VALUES (?, ?, ?, ?, "in_progress", 1, "player1")
    ');
    $ins->execute([$userId, $opponentId, $deckId, $oppDeck['id'] ?? null]);
    $matchId = (int)$db->lastInsertId();

    $ins = $db->prepare('INSERT INTO board_states (match_id, turn, board_json) VALUES (?, 1, ?)');
    $ins->execute([$matchId, Chess::encodeGameData($initState)]);

    $db->commit();
} catch (Exception $e) {
    $db->rollBack();
    jsonError(500, '試合作成に失敗しました: ' . $e->getMessage());
}

echo json_encode([
    'success'    => true,
    'match_id'   => $matchId,
    'player1_url'=> "../match.html?id={$matchId}",
    'player2_url'=> "../match.html?id={$matchId}",
]);
