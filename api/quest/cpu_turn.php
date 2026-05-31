<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Methods: POST, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST')    { http_response_code(405); echo json_encode(['error' => 'Method Not Allowed']); exit; }

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../game/Chess.php';
require_once __DIR__ . '/../game/CpuAi.php';

const CPU_USER_ID = 99999;
const CPU_MAX_ITERATIONS = 50;

function jsonError(int $code, string $msg): void {
    http_response_code($code);
    echo json_encode(['error' => $msg]);
    exit;
}

$userId = requireAuth();

$body = json_decode(file_get_contents('php://input'), true);
if (!$body) jsonError(400, 'リクエストボディが不正です');

$matchId = (int)($body['match_id'] ?? 0);
if (!$matchId) jsonError(400, 'match_id は必須です');

$db = getDb();

// ─── 試合取得 ────────────────────────────────────────────────
$stmt = $db->prepare('SELECT * FROM matches WHERE id = ? LIMIT 1');
$stmt->execute([$matchId]);
$match = $stmt->fetch();
if (!$match) jsonError(404, '試合が見つかりません');
if ((int)$match['player1_id'] !== $userId) jsonError(403, 'この試合の参加者ではありません');
if ((int)($match['is_cpu_match'] ?? 0) !== 1) jsonError(422, 'CPU対戦ではありません');

if ($match['status'] !== 'in_progress') {
    // 既に終了している場合は現状を返す（フロントの再呼び出しを許容）
    echo json_encode([
        'success'  => true,
        'finished' => true,
        'noop'     => true,
    ]);
    exit;
}

// ─── 最新盤面取得 ────────────────────────────────────────────
$bsStmt = $db->prepare('SELECT board_json FROM board_states WHERE match_id = ? ORDER BY turn DESC, id DESC LIMIT 1');
$bsStmt->execute([$matchId]);
$bs = $bsStmt->fetch();
if (!$bs) jsonError(500, '盤面データが見つかりません');

$gameData = Chess::decodeGameData($bs['board_json']);

$state = [
    'board'            => $gameData['board'],
    'traps'            => $gameData['traps'],
    'timedTraps'       => $gameData['timedTraps'],
    'timedSanctuaries' => $gameData['timedSanctuaries'],
    'captured'         => $gameData['captured'],
    'rematchPending'   => $gameData['rematchPending'],
    'skillOpportunity' => $gameData['skillOpportunity'],
    'currentPlayer'    => $match['current_player'],
    'turn'             => (int)$match['current_turn'],
    'maxTurns'         => 30,
    'status'           => $match['status'],
    'winner'           => null,
    'endReason'        => null,
];

// ─── CPUターンを進める ───────────────────────────────────────
$moves = []; // 実行した手のログ
$iterations = 0;

while ($state['status'] === 'in_progress' && $iterations < CPU_MAX_ITERATIONS) {
    $iterations++;

    // スキル機会 (CPUは常にスキップ)
    if ($state['skillOpportunity'] && $state['skillOpportunity']['player'] === 'player2') {
        $nextTurn = $state['turn'] + 1; // player2のスキップは次ターンへ
        $state = array_merge($state, [
            'currentPlayer'    => 'player1',
            'turn'             => $nextTurn,
            'skillOpportunity' => null,
        ]);
        persistState($db, $matchId, $state);
        break; // スキップで自分の手番に渡る
    }

    // 再移動ペンディング (CPUがスキル使わない設計なので基本起こらないが、安全側で同駒に対し再選択)
    if ($state['rematchPending'] && $state['rematchPending']['player'] === 'player2') {
        $rematchSq = $state['rematchPending']['sq'];
        $legal = Chess::getLegalMoves($state['board'], $rematchSq, $state['traps'] ?? [], $state['timedSanctuaries'] ?? []);
        if (!$legal) {
            // 動けないなら強制的に rematch を解除
            $state['rematchPending'] = null;
            $state['currentPlayer']  = 'player1';
            $state['turn']           = $state['turn'] + 1;
            persistState($db, $matchId, $state);
            break;
        }
        $to = $legal[array_rand($legal)];
        try {
            $state = Chess::executeMove($state, 'player2', $rematchSq, $to);
        } catch (RuntimeException $e) {
            jsonError(500, 'CPU再移動失敗: ' . $e->getMessage());
        }
        persistState($db, $matchId, $state);
        $moves[] = ['from' => $rematchSq, 'to' => $to];
        continue;
    }

    if ($state['currentPlayer'] !== 'player2') break;

    $pick = CpuAi::pickMove($state, 'black');
    if (!$pick) {
        // 合法手が無い → ポイント判定で強制終了
        $state['status']    = 'finished';
        $state['winner']    = 'player1';
        $state['endReason'] = 'timeout';
        persistState($db, $matchId, $state);
        break;
    }

    try {
        $state = Chess::executeMove($state, 'player2', $pick['from'], $pick['to']);
    } catch (RuntimeException $e) {
        jsonError(500, 'CPU手番失敗: ' . $e->getMessage());
    }
    persistState($db, $matchId, $state);
    $moves[] = $pick;
}

// ─── matches テーブル更新 ───────────────────────────────────
$db->beginTransaction();
try {
    if ($state['status'] === 'finished') {
        $winnerId = null;
        if ($state['winner'] === 'player1') $winnerId = (int)$match['player1_id'];
        elseif ($state['winner'] === 'player2') $winnerId = (int)$match['player2_id'];

        $upd = $db->prepare('UPDATE matches SET status="finished", winner_id=?, end_reason=?, updated_at=NOW() WHERE id=?');
        $upd->execute([$winnerId, $state['endReason'], $matchId]);
    } else {
        $upd = $db->prepare('UPDATE matches SET current_turn=?, current_player=?, updated_at=NOW() WHERE id=?');
        $upd->execute([$state['turn'], $state['currentPlayer'], $matchId]);
    }
    $db->commit();
} catch (Exception $e) {
    $db->rollBack();
    jsonError(500, 'DB更新に失敗しました: ' . $e->getMessage());
}

// ─── クエスト報酬 ────────────────────────────────────────────
$reward = null;
if ($state['status'] === 'finished' && $state['winner'] === 'player1' && $match['quest_id']) {
    $reward = grantQuestReward($db, $userId, (int)$match['quest_id']);
}

// ─── レスポンス ──────────────────────────────────────────────
$visibleTimedTraps = [];
foreach (($state['timedTraps'] ?? []) as $sq => $info) {
    if (($info['color'] ?? '') === 'white') $visibleTimedTraps[$sq] = $info;
}

echo json_encode([
    'success'           => true,
    'finished'          => $state['status'] === 'finished',
    'winner'            => $state['winner'],
    'end_reason'        => $state['endReason'],
    'turn'              => $state['turn'],
    'current_player'    => $state['currentPlayer'],
    'is_my_turn'        => $state['currentPlayer'] === 'player1',
    'board'             => $state['board'],
    'traps'             => $state['traps'],
    'timed_traps'       => $visibleTimedTraps,
    'timed_sanctuaries' => $state['timedSanctuaries'] ?? [],
    'cpu_moves'         => $moves,
    'reward'            => $reward,
]);

// ─── ヘルパー ────────────────────────────────────────────────

function persistState(PDO $db, int $matchId, array $state): void {
    $ins = $db->prepare('INSERT INTO board_states (match_id, turn, board_json) VALUES (?, ?, ?)');
    $ins->execute([$matchId, $state['turn'], Chess::encodeGameData($state)]);
}

function grantQuestReward(PDO $db, int $userId, int $questId): array {
    $stmt = $db->prepare('SELECT reward_gem, reward_character_id FROM quests WHERE id = ? LIMIT 1');
    $stmt->execute([$questId]);
    $q = $stmt->fetch();
    if (!$q) return ['error' => 'quest not found'];

    $isFirstClear = false;
    $grantedCharacterId = null;
    $grantedCharacterName = null;

    $db->beginTransaction();
    try {
        // クリア記録（重複は無視 = 初回のみ true）
        $stmt = $db->prepare('INSERT IGNORE INTO quest_clears (user_id, quest_id) VALUES (?, ?)');
        $stmt->execute([$userId, $questId]);
        $isFirstClear = $stmt->rowCount() > 0;

        // ガチャ石付与（毎回）
        $gem = (int)$q['reward_gem'];
        if ($gem > 0) {
            $stmt = $db->prepare('UPDATE users SET stones = stones + ? WHERE id = ?');
            $stmt->execute([$gem, $userId]);
        }

        // キャラ解放（初回のみ・未所持の場合）
        if ($isFirstClear && $q['reward_character_id']) {
            $charId = (int)$q['reward_character_id'];
            $stmt = $db->prepare('SELECT 1 FROM user_characters WHERE user_id = ? AND character_id = ? LIMIT 1');
            $stmt->execute([$userId, $charId]);
            if (!$stmt->fetch()) {
                $ins = $db->prepare('INSERT INTO user_characters (user_id, character_id) VALUES (?, ?)');
                $ins->execute([$userId, $charId]);
                $grantedCharacterId = $charId;

                $stmt = $db->prepare('SELECT name FROM characters WHERE id = ? LIMIT 1');
                $stmt->execute([$charId]);
                $grantedCharacterName = $stmt->fetch()['name'] ?? null;
            }
        }

        $db->commit();
    } catch (Exception $e) {
        $db->rollBack();
        return ['error' => $e->getMessage()];
    }

    return [
        'first_clear'           => $isFirstClear,
        'gem'                   => (int)$q['reward_gem'],
        'character_id'          => $grantedCharacterId,
        'character_name'        => $grantedCharacterName,
    ];
}
