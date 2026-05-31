<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Methods: POST, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST')    { http_response_code(405); echo json_encode(['error' => 'Method Not Allowed']); exit; }

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../game/Chess.php';

const CPU_USER_ID = 99999;

function jsonError(int $code, string $msg): void {
    http_response_code($code);
    echo json_encode(['error' => $msg]);
    exit;
}

$userId = requireAuth();

$body = json_decode(file_get_contents('php://input'), true);
if (!$body) jsonError(400, 'リクエストボディが不正です');

$questId = (int)($body['quest_id'] ?? 0);
$deckId  = (int)($body['deck_id']  ?? 0);

if (!$questId) jsonError(400, 'quest_id は必須です');
if (!$deckId)  jsonError(400, 'deck_id は必須です');

$db = getDb();

// ─── クエスト取得 ────────────────────────────────────────────
$stmt = $db->prepare('SELECT * FROM quests WHERE id = ? LIMIT 1');
$stmt->execute([$questId]);
$quest = $stmt->fetch();
if (!$quest) jsonError(404, 'クエストが見つかりません');

$cpuDeck = json_decode($quest['cpu_deck_json'], true);
if (!is_array($cpuDeck)) jsonError(500, 'クエストのCPUデッキ設定が不正です');

// ─── 自分のデッキ取得 ────────────────────────────────────────
$stmt = $db->prepare('SELECT * FROM decks WHERE id = ? AND user_id = ? LIMIT 1');
$stmt->execute([$deckId, $userId]);
$myDeck = $stmt->fetch();
if (!$myDeck) jsonError(404, 'デッキが見つかりません');

// ─── 自分のクラス配置駒 ──────────────────────────────────────
$cpStmt = $db->prepare('SELECT character_id, board_col, board_row FROM deck_class_pieces WHERE deck_id = ?');
$cpStmt->execute([$deckId]);
$myClassPieces = $cpStmt->fetchAll();

// ─── 必要なキャラ情報を一括取得 ───────────────────────────────
$allCharIds = [];
$deckCols = ['pawn_char_id','knight_char_id','bishop_char_id','rook_char_id','queen_char_id','king_char_id'];
foreach ($deckCols as $col) {
    if ($myDeck[$col]) $allCharIds[] = (int)$myDeck[$col];
}
foreach ($myClassPieces as $cp) $allCharIds[] = (int)$cp['character_id'];
foreach ($cpuDeck as $cid) $allCharIds[] = (int)$cid;

$charMap = [];
if ($allCharIds) {
    $unique = array_values(array_unique($allCharIds));
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

// CPU デッキを {piece => char_id} 形式に整える
$cpuClassMap = [
    'pawn'   => isset($cpuDeck['pawn'])   ? (int)$cpuDeck['pawn']   : null,
    'knight' => isset($cpuDeck['knight']) ? (int)$cpuDeck['knight'] : null,
    'bishop' => isset($cpuDeck['bishop']) ? (int)$cpuDeck['bishop'] : null,
    'rook'   => isset($cpuDeck['rook'])   ? (int)$cpuDeck['rook']   : null,
    'queen'  => isset($cpuDeck['queen'])  ? (int)$cpuDeck['queen']  : null,
    'king'   => isset($cpuDeck['king'])   ? (int)$cpuDeck['king']   : null,
];

// ─── 初期盤面組み立て ────────────────────────────────────────
$board = Chess::createInitialBoard();
$board = Chess::applyDeckToBoard($board, deckToClassMap($myDeck), $charMap, 'white');
$board = Chess::applyDeckToBoard($board, $cpuClassMap,            $charMap, 'black');
$board = Chess::applyClassPiecesToBoard($board, $myClassPieces, $charMap, 'white');

$initState = [
    'board'            => $board,
    'traps'            => [],
    'rematchPending'   => null,
    'skillOpportunity' => null,
];

// ─── DBに試合を登録 ──────────────────────────────────────────
$db->beginTransaction();
try {
    $ins = $db->prepare('
        INSERT INTO matches
          (player1_id, player2_id, player1_deck_id, player2_deck_id, status, current_turn, current_player, quest_id, is_cpu_match)
        VALUES
          (?, ?, ?, NULL, "in_progress", 1, "player1", ?, 1)
    ');
    $ins->execute([$userId, CPU_USER_ID, $deckId, $questId]);
    $matchId = (int)$db->lastInsertId();

    $ins = $db->prepare('INSERT INTO board_states (match_id, turn, board_json) VALUES (?, 1, ?)');
    $ins->execute([$matchId, Chess::encodeGameData($initState)]);

    $db->commit();
} catch (Exception $e) {
    $db->rollBack();
    jsonError(500, '試合作成に失敗しました: ' . $e->getMessage());
}

echo json_encode([
    'success'   => true,
    'match_id'  => $matchId,
    'quest_id'  => $questId,
    'match_url' => "../match.html?id={$matchId}",
]);
