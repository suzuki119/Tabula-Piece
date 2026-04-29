<?php
/**
 * Tabula-Piece チェスロジック
 * Phase 4: スキルシステム追加
 */

class Chess {

    const COLS = ['a','b','c','d','e','f'];

    const PIECE_VALUES = [
        'pawn'   => 1,
        'knight' => 3,
        'bishop' => 3,
        'rook'   => 5,
        'queen'  => 9,
        'king'   => 0,
    ];

    // スキルID（seeds の INSERT 順と一致）
    const SKILL_REMATCH     = 1;  // 再移動
    const SKILL_TELEPORT    = 2;  // テレポート
    const SKILL_FIRST_MOVE  = 3;  // 先手（Phase 5 以降）
    const SKILL_SHIELD      = 4;  // シールド
    const SKILL_ENHANCE     = 5;  // 駒強化
    const SKILL_COUNTER     = 6;  // 反撃
    const SKILL_VALUE_UP    = 7;  // 価値上昇
    const SKILL_DEATH_CURSE = 8;  // 死に際の呪い
    const SKILL_TRAP        = 9;  // トラップ設置
    const SKILL_SANCTUARY   = 10; // 聖域

    const SKILL_MAX_USES = [
        self::SKILL_REMATCH  => 3,
        self::SKILL_TELEPORT => 1,
        self::SKILL_SHIELD   => 2,
        self::SKILL_ENHANCE  => 2,
        self::SKILL_VALUE_UP => 1,
        self::SKILL_TRAP     => 1,
    ];

    // フロントエンド参照用スキルマスター
    const SKILL_DATA = [
        1  => ['name' => '再移動',       'type' => 'active',  'category' => 'move',   'description' => '駒を移動した後、もう1マス追加で移動できる',           'max_uses' => 3],
        2  => ['name' => 'テレポート',   'type' => 'active',  'category' => 'move',   'description' => '盤面上の任意の空きマスに瞬間移動する',               'max_uses' => 1],
        3  => ['name' => '先手',         'type' => 'passive', 'category' => 'move',   'description' => 'ターン開始時に1マス先に移動してからターンを行える',   'max_uses' => null],
        4  => ['name' => 'シールド',     'type' => 'active',  'category' => 'combat', 'description' => '次に受ける捕獲を1回無効化する',                       'max_uses' => 2],
        5  => ['name' => '駒強化',       'type' => 'active',  'category' => 'combat', 'description' => '隣接する味方駒1体の価値をそのターン中+2する',         'max_uses' => 2],
        6  => ['name' => '反撃',         'type' => 'passive', 'category' => 'combat', 'description' => '捕獲される直前に、攻撃してきた駒を除去する',          'max_uses' => 1],
        7  => ['name' => '価値上昇',     'type' => 'active',  'category' => 'point',  'description' => 'この駒の価値をターン切れまで+3する',                  'max_uses' => 1],
        8  => ['name' => '死に際の呪い', 'type' => 'passive', 'category' => 'point',  'description' => '取られたとき、攻撃した駒の価値を-2する',              'max_uses' => null],
        9  => ['name' => 'トラップ設置', 'type' => 'active',  'category' => 'board',  'description' => '指定マスにトラップを置く。踏んだ相手の駒を除去する', 'max_uses' => 1],
        10 => ['name' => '聖域',         'type' => 'passive', 'category' => 'board',  'description' => 'この駒が乗っているマスに相手は進入できない',          'max_uses' => null],
    ];

    // ─── ボードヘルパー ──────────────────────────────────────

    public static function colIdx(string $sq): int {
        return (int)array_search($sq[0], self::COLS, true);
    }

    public static function rowNum(string $sq): int {
        return (int)$sq[1];
    }

    public static function toSq(int $c, int $r): string {
        return self::COLS[$c] . $r;
    }

    public static function inBounds(int $c, int $r): bool {
        return $c >= 0 && $c < 6 && $r >= 1 && $r <= 6;
    }

    public static function opponent(string $color): string {
        return $color === 'white' ? 'black' : 'white';
    }

    public static function isAdjacent(string $sq1, string $sq2): bool {
        $c1 = self::colIdx($sq1); $r1 = self::rowNum($sq1);
        $c2 = self::colIdx($sq2); $r2 = self::rowNum($sq2);
        return $sq1 !== $sq2 && abs($c1 - $c2) <= 1 && abs($r1 - $r2) <= 1;
    }

    // ─── 駒データ生成 ────────────────────────────────────────

    public static function makePiece(string $piece, string $color, array $extra = []): array {
        return array_merge([
            'piece'            => $piece,
            'color'            => $color,
            'character_id'     => null,
            'active_skill_id'  => null,
            'passive_skill_id' => null,
            'active_used'      => 0,
            'passive_used'     => 0,
            'shield'           => false,
            'value_bonus'      => 0,
        ], $extra);
    }

    // ─── 初期ボード ──────────────────────────────────────────

    public static function createInitialBoard(): array {
        $board      = [];
        $backPieces = ['rook','knight','bishop','queen','king'];

        foreach ($backPieces as $i => $piece) {
            $board[self::toSq($i, 1)] = self::makePiece($piece, 'white');
            $board[self::toSq($i, 6)] = self::makePiece($piece, 'black');
        }

        for ($i = 0; $i < 6; $i++) {
            $board[self::toSq($i, 2)] = self::makePiece('pawn', 'white');
            $board[self::toSq($i, 5)] = self::makePiece('pawn', 'black');
        }

        return $board;
    }

    // ─── board_json シリアライズ / デシリアライズ ─────────────

    public static function encodeGameData(array $state): string {
        return json_encode([
            'squares'          => $state['board'],
            'traps'            => $state['traps'] ?? [],
            'rematch_pending'  => $state['rematchPending'] ?? null,
            'skill_opportunity'=> $state['skillOpportunity'] ?? null,
        ]);
    }

    public static function decodeGameData(string $json): array {
        $data = json_decode($json, true);
        // 旧フォーマット（フラットな squares）との互換
        if (isset($data['squares'])) {
            return [
                'board'            => $data['squares'],
                'traps'            => $data['traps'] ?? [],
                'rematchPending'   => $data['rematch_pending'] ?? null,
                'skillOpportunity' => $data['skill_opportunity'] ?? null,
            ];
        }
        return [
            'board'            => $data,
            'traps'            => [],
            'rematchPending'   => null,
            'skillOpportunity' => null,
        ];
    }

    // ─── デッキを盤面に適用 ──────────────────────────────────
    // $deck = ['pawn' => charId, 'knight' => charId, ...]
    // $chars = [charId => ['active_skill_id' => ..., 'passive_skill_id' => ...]]

    public static function applyDeckToBoard(array $board, array $deck, array $chars, string $color): array {
        $backRow = $color === 'white' ? 1 : 6;
        $pawnRow = $color === 'white' ? 2 : 5;
        $colMap  = ['rook' => 'a', 'knight' => 'b', 'bishop' => 'c', 'queen' => 'd', 'king' => 'e'];

        foreach ($colMap as $class => $col) {
            $charId = $deck[$class] ?? null;
            if (!$charId || !isset($chars[$charId])) continue;
            $ch = $chars[$charId];
            $sq = $col . $backRow;
            if (isset($board[$sq])) {
                $board[$sq]['character_id']     = $charId;
                $board[$sq]['active_skill_id']  = $ch['active_skill_id'];
                $board[$sq]['passive_skill_id'] = $ch['passive_skill_id'];
            }
        }

        $pawnCharId = $deck['pawn'] ?? null;
        if ($pawnCharId && isset($chars[$pawnCharId])) {
            $ch = $chars[$pawnCharId];
            foreach (self::COLS as $col) {
                $sq = $col . $pawnRow;
                if (isset($board[$sq])) {
                    $board[$sq]['character_id']     = $pawnCharId;
                    $board[$sq]['active_skill_id']  = $ch['active_skill_id'];
                    $board[$sq]['passive_skill_id'] = $ch['passive_skill_id'];
                }
            }
        }

        return $board;
    }

    // ─── 移動後スキル機会チェック ─────────────────────────────

    public static function checkSkillOpportunity(array $board, string $sq, string $player): ?array {
        $piece = $board[$sq] ?? null;
        if (!$piece) return null;

        $skillId = $piece['active_skill_id'] ?? null;
        if ($skillId === null) return null;

        $used    = (int)($piece['active_used'] ?? 0);
        $maxUses = self::SKILL_MAX_USES[$skillId] ?? null;
        if ($maxUses !== null && $used >= $maxUses) return null;

        return ['player' => $player, 'sq' => $sq, 'skill_id' => $skillId];
    }

    // ─── 疑似合法手生成 ──────────────────────────────────────

    public static function getPseudoMoves(array $board, string $sq): array {
        $p = $board[$sq] ?? null;
        if (!$p) return [];

        $c     = self::colIdx($sq);
        $r     = self::rowNum($sq);
        $moves = [];

        $addSlide = function(int $dc, int $dr) use ($board, $c, $r, $p, &$moves) {
            $nc = $c + $dc; $nr = $r + $dr;
            while (self::inBounds($nc, $nr)) {
                $t = $board[self::toSq($nc, $nr)] ?? null;
                if ($t) {
                    if ($t['color'] !== $p['color']) $moves[] = self::toSq($nc, $nr);
                    break;
                }
                $moves[] = self::toSq($nc, $nr);
                $nc += $dc; $nr += $dr;
            }
        };

        $addStep = function(int $dc, int $dr) use ($board, $c, $r, $p, &$moves) {
            $nc = $c + $dc; $nr = $r + $dr;
            if (!self::inBounds($nc, $nr)) return;
            $t = $board[self::toSq($nc, $nr)] ?? null;
            if (!$t || $t['color'] !== $p['color']) $moves[] = self::toSq($nc, $nr);
        };

        switch ($p['piece']) {
            case 'pawn':
                $dir = $p['color'] === 'white' ? 1 : -1;
                $nr  = $r + $dir;
                if (self::inBounds($c, $nr) && empty($board[self::toSq($c, $nr)])) {
                    $moves[] = self::toSq($c, $nr);
                }
                foreach ([-1, 1] as $dc) {
                    if (!self::inBounds($c + $dc, $nr)) continue;
                    $t = $board[self::toSq($c + $dc, $nr)] ?? null;
                    if ($t && $t['color'] !== $p['color']) $moves[] = self::toSq($c + $dc, $nr);
                }
                break;
            case 'knight':
                foreach ([[-2,-1],[-2,1],[-1,-2],[-1,2],[1,-2],[1,2],[2,-1],[2,1]] as [$dc,$dr]) {
                    $addStep($dc, $dr);
                }
                break;
            case 'bishop':
                foreach ([[-1,-1],[-1,1],[1,-1],[1,1]] as [$dc,$dr]) $addSlide($dc, $dr);
                break;
            case 'rook':
                foreach ([[-1,0],[1,0],[0,-1],[0,1]] as [$dc,$dr]) $addSlide($dc, $dr);
                break;
            case 'queen':
                foreach ([[-1,-1],[-1,1],[1,-1],[1,1],[-1,0],[1,0],[0,-1],[0,1]] as [$dc,$dr]) {
                    $addSlide($dc, $dr);
                }
                break;
            case 'king':
                foreach ([[-1,-1],[-1,0],[-1,1],[0,-1],[0,1],[1,-1],[1,0],[1,1]] as [$dc,$dr]) {
                    $addStep($dc, $dr);
                }
                break;
        }

        return $moves;
    }

    // ─── 合法手（聖域スキル考慮）────────────────────────────

    public static function getLegalMoves(array $board, string $sq, array $traps = []): array {
        $piece = $board[$sq] ?? null;
        if (!$piece) return [];

        $moves         = self::getPseudoMoves($board, $sq);
        $opponentColor = self::opponent($piece['color']);

        // 聖域: 相手の駒が聖域パッシブを持つマスは進入不可
        $sanctified = [];
        foreach ($board as $oSq => $op) {
            if ($op && $op['color'] === $opponentColor &&
                ($op['passive_skill_id'] ?? null) === self::SKILL_SANCTUARY) {
                $sanctified[$oSq] = true;
            }
        }

        if ($sanctified) {
            $moves = array_values(array_filter($moves, fn($m) => !isset($sanctified[$m])));
        }

        return $moves;
    }

    // ─── 移動適用（パッシブスキル発動）──────────────────────

    public static function applyMove(array $board, string $from, string $to, array &$traps = []): array {
        $piece  = $board[$from];
        $target = $board[$to] ?? null;

        // シールド: 相手の駒がシールド発動中 → 捕獲無効、シールド消費、移動もキャンセル
        if ($target && $target['color'] !== $piece['color'] && ($target['shield'] ?? false)) {
            $board[$to]['shield'] = false;
            return $board; // 攻撃者はfromに留まる
        }

        // 反撃: 捕獲される直前に攻撃者を除去
        if ($target && $target['color'] !== $piece['color']) {
            $passiveId   = $target['passive_skill_id'] ?? null;
            $passiveUsed = (int)($target['passive_used'] ?? 0);
            $maxUses     = self::SKILL_MAX_USES[self::SKILL_COUNTER] ?? 1;
            if ($passiveId === self::SKILL_COUNTER && $passiveUsed < $maxUses) {
                unset($board[$from]);
                $board[$to]['passive_used'] = $passiveUsed + 1;
                return $board; // 攻撃者が消え、守備側が生き残る
            }
        }

        // 通常の捕獲・移動
        unset($board[$from]);

        // 死に際の呪い: 取られる駒がこのパッシブを持つ場合、攻撃者の value_bonus を -2
        if ($target && $target['color'] !== $piece['color']) {
            if (($target['passive_skill_id'] ?? null) === self::SKILL_DEATH_CURSE) {
                $piece['value_bonus'] = (int)($piece['value_bonus'] ?? 0) - 2;
            }
        }

        // ポーン成り
        if ($piece['piece'] === 'pawn') {
            if (($piece['color'] === 'white' && self::rowNum($to) === 6) ||
                ($piece['color'] === 'black' && self::rowNum($to) === 1)) {
                $piece['piece'] = 'queen';
            }
        }

        $board[$to] = $piece;

        // トラップ: 着地マスに相手のトラップがある場合、駒を除去
        if (isset($traps[$to])) {
            if ($traps[$to] !== $piece['color']) {
                unset($board[$to]);
            }
            unset($traps[$to]);
        }

        return $board;
    }

    // ─── スキル発動検証 ──────────────────────────────────────

    public static function validateSkill(array $state, string $player, string $from, int $skillId, ?string $target): array {
        if ($state['status'] !== 'in_progress') {
            return ['valid' => false, 'reason' => '試合は終了しています'];
        }

        $opportunity = $state['skillOpportunity'] ?? null;

        if ($opportunity) {
            // スキル機会モード: そのプレイヤー・駒・スキルのみ許可
            if ($opportunity['player'] !== $player) {
                return ['valid' => false, 'reason' => 'あなたのターンではありません'];
            }
            if ($opportunity['sq'] !== $from) {
                return ['valid' => false, 'reason' => 'スキル機会の駒のみ発動できます'];
            }
            if ($opportunity['skill_id'] !== $skillId) {
                return ['valid' => false, 'reason' => 'そのスキルは今発動できません'];
            }
        } else {
            // 通常モード
            $rematch = $state['rematchPending'] ?? null;
            if ($rematch) {
                return ['valid' => false, 'reason' => '再移動が保留中です。移動を行ってください'];
            }
            if ($state['currentPlayer'] !== $player) {
                return ['valid' => false, 'reason' => 'あなたのターンではありません'];
            }
        }

        $board = $state['board'];
        $piece = $board[$from] ?? null;
        if (!$piece) return ['valid' => false, 'reason' => "{$from} に駒がありません"];

        $color = $player === 'player1' ? 'white' : 'black';
        if ($piece['color'] !== $color) return ['valid' => false, 'reason' => '自分の駒ではありません'];

        if (($piece['active_skill_id'] ?? null) !== $skillId) {
            return ['valid' => false, 'reason' => 'その駒はそのスキルを持っていません'];
        }

        $used    = (int)($piece['active_used'] ?? 0);
        $maxUses = self::SKILL_MAX_USES[$skillId] ?? null;
        if ($maxUses !== null && $used >= $maxUses) {
            return ['valid' => false, 'reason' => 'スキルの使用回数が上限に達しています'];
        }

        return ['valid' => true];
    }

    // ─── スキル実行 ──────────────────────────────────────────

    public static function executeSkill(array $state, string $player, string $from, int $skillId, ?string $target): array {
        $check = self::validateSkill($state, $player, $from, $skillId, $target);
        if (!$check['valid']) throw new RuntimeException($check['reason']);

        $board          = $state['board'];
        $traps          = $state['traps'] ?? [];
        $color          = $player === 'player1' ? 'white' : 'black';
        $opponentPlayer = $player === 'player1' ? 'player2' : 'player1';
        $advanceTurn    = true;
        $rematchPending = null;

        switch ($skillId) {
            case self::SKILL_SHIELD:
                $board[$from]['shield'] = true;
                $board[$from]['active_used']++;
                break;

            case self::SKILL_VALUE_UP:
                $board[$from]['value_bonus'] = (int)($board[$from]['value_bonus'] ?? 0) + 3;
                $board[$from]['active_used']++;
                break;

            case self::SKILL_ENHANCE:
                if (!$target) throw new RuntimeException('強化対象のマスを指定してください');
                $tp = $board[$target] ?? null;
                if (!$tp) throw new RuntimeException('対象のマスに駒がありません');
                if ($tp['color'] !== $color) throw new RuntimeException('味方の駒のみ強化できます');
                if (!self::isAdjacent($from, $target)) throw new RuntimeException('対象が隣接していません');
                $board[$target]['value_bonus'] = (int)($board[$target]['value_bonus'] ?? 0) + 2;
                $board[$from]['active_used']++;
                break;

            case self::SKILL_TELEPORT:
                if (!$target) throw new RuntimeException('移動先のマスを指定してください');
                if (isset($board[$target])) throw new RuntimeException('移動先に駒があります');
                $p = $board[$from];
                $p['active_used']++;
                unset($board[$from]);
                $board[$target] = $p;
                break;

            case self::SKILL_TRAP:
                if (!$target) throw new RuntimeException('トラップを置くマスを指定してください');
                if (!self::isAdjacent($from, $target)) throw new RuntimeException('トラップは隣接するマスにのみ設置できます');
                if (isset($board[$target])) throw new RuntimeException('駒のあるマスにはトラップを置けません');
                $traps[$target] = $color;
                $board[$from]['active_used']++;
                break;

            case self::SKILL_REMATCH:
                // 再移動: 次の移動でターンを消費しない
                $board[$from]['active_used']++;
                $rematchPending = ['player' => $player, 'sq' => $from];
                $advanceTurn    = false;
                break;

            default:
                throw new RuntimeException('そのスキルはアクティブ発動できません');
        }

        $nextPlayer = $advanceTurn ? $opponentPlayer : $player;
        $nextTurn   = ($advanceTurn && $player === 'player2')
                        ? $state['turn'] + 1
                        : $state['turn'];

        return array_merge($state, [
            'board'            => $board,
            'traps'            => $traps,
            'currentPlayer'    => $nextPlayer,
            'turn'             => $nextTurn,
            'rematchPending'   => $rematchPending,
            'skillOpportunity' => null,  // スキル発動でスキル機会は常に解消
        ]);
    }

    // ─── 移動検証 ────────────────────────────────────────────

    public static function validateMove(array $state, string $player, string $from, string $to): array {
        if ($state['status'] !== 'in_progress') {
            return ['valid' => false, 'reason' => '試合は終了しています'];
        }

        // スキル機会中は移動不可（発動かスキップのみ）
        $opportunity = $state['skillOpportunity'] ?? null;
        if ($opportunity && $opportunity['player'] === $player) {
            return ['valid' => false, 'reason' => 'スキルを発動するかスキップしてください'];
        }

        $rematch = $state['rematchPending'] ?? null;
        if ($rematch) {
            // 再移動ペンディング中: 指定プレイヤー・指定マスの駒のみ動かせる
            if ($rematch['player'] !== $player) {
                return ['valid' => false, 'reason' => 'あなたのターンではありません'];
            }
            if ($rematch['sq'] !== $from) {
                return ['valid' => false, 'reason' => '再移動は同じ駒で行ってください'];
            }
        } else {
            if ($state['currentPlayer'] !== $player) {
                return ['valid' => false, 'reason' => 'あなたのターンではありません'];
            }
        }

        $piece = $state['board'][$from] ?? null;
        if (!$piece) return ['valid' => false, 'reason' => "{$from} に駒がありません"];

        $color = $player === 'player1' ? 'white' : 'black';
        if ($piece['color'] !== $color) return ['valid' => false, 'reason' => '自分の駒ではありません'];

        $legal = self::getLegalMoves($state['board'], $from, $state['traps'] ?? []);
        if (!in_array($to, $legal, true)) {
            return ['valid' => false, 'reason' => "{$from}→{$to} は不正な移動です"];
        }

        return ['valid' => true];
    }

    // ─── 移動実行 ────────────────────────────────────────────

    public static function executeMove(array $state, string $player, string $from, string $to): array {
        $check = self::validateMove($state, $player, $from, $to);
        if (!$check['valid']) throw new RuntimeException($check['reason']);

        $traps    = $state['traps'] ?? [];
        $newBoard = self::applyMove($state['board'], $from, $to, $traps);

        $rematch   = $state['rematchPending'] ?? null;
        $isRematch = $rematch && $rematch['player'] === $player && $rematch['sq'] === $from;

        $opponentPlayer = $player === 'player1' ? 'player2' : 'player1';
        $opponentColor  = $opponentPlayer === 'player1' ? 'white' : 'black';

        $status    = 'in_progress';
        $winner    = null;
        $endReason = null;

        // キング捕獲チェック
        if (!self::hasKing($newBoard, $opponentColor)) {
            $status    = 'finished';
            $winner    = $player;
            $endReason = 'checkmate';
        }

        // ターンカウント（黒が手番を終えたときのみ +1、再移動は除く）
        $nextTurn = (!$isRematch && $player === 'player2')
                    ? $state['turn'] + 1
                    : $state['turn'];

        // ターン上限（再移動中は判定しない）
        if ($status === 'in_progress' && !$isRematch && $nextTurn > $state['maxTurns']) {
            $p1pts     = self::calcPoints($newBoard, 'white');
            $p2pts     = self::calcPoints($newBoard, 'black');
            $status    = 'finished';
            $endReason = 'points';
            if ($p1pts > $p2pts)     $winner = 'player1';
            elseif ($p2pts > $p1pts) $winner = 'player2';
            else                     $winner = 'draw';
        }

        // 移動後スキル機会チェック（再移動・試合終了時は除く）
        $skillOpportunity = null;
        if ($status === 'in_progress' && !$isRematch) {
            $movedPiece = $newBoard[$to] ?? null;
            // 自分の駒が $to に移動した場合のみ（シールドで弾かれた場合は移動していない）
            if ($movedPiece && $movedPiece['color'] === ($player === 'player1' ? 'white' : 'black')) {
                $skillOpportunity = self::checkSkillOpportunity($newBoard, $to, $player);
            }
        }

        // スキル機会がある場合: ターンを進めず待機
        if ($skillOpportunity) {
            return array_merge($state, [
                'board'            => $newBoard,
                'traps'            => $traps,
                'currentPlayer'    => $player,
                'turn'             => $state['turn'],
                'status'           => $status,
                'winner'           => $winner,
                'endReason'        => $endReason,
                'rematchPending'   => null,
                'skillOpportunity' => $skillOpportunity,
            ]);
        }

        return array_merge($state, [
            'board'            => $newBoard,
            'traps'            => $traps,
            'currentPlayer'    => $status === 'finished' ? null : $opponentPlayer,
            'turn'             => $nextTurn,
            'status'           => $status,
            'winner'           => $winner,
            'endReason'        => $endReason,
            'rematchPending'   => null,
            'skillOpportunity' => null,
        ]);
    }

    // ─── ポイント計算（value_bonus 考慮）────────────────────

    public static function calcPoints(array $board, string $color): int {
        $sum = 0;
        foreach ($board as $p) {
            if (!$p || $p['color'] !== $color || $p['piece'] === 'king') continue;
            $base  = self::PIECE_VALUES[$p['piece']] ?? 0;
            $bonus = (int)($p['value_bonus'] ?? 0);
            $sum  += max(0, $base + $bonus);
        }
        return $sum;
    }

    public static function hasKing(array $board, string $color): bool {
        foreach ($board as $p) {
            if ($p && $p['piece'] === 'king' && $p['color'] === $color) return true;
        }
        return false;
    }
}
