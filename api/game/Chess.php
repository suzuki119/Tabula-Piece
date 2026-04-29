<?php
/**
 * Tabula-Piece チェスロジック (PHP版)
 * JS の chess.js と同じロジックをサーバーサイドで実装する
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

    // ─── ボードヘルパー ──────────────────────────────────────

    private static function colIdx(string $sq): int {
        return array_search($sq[0], self::COLS, true);
    }

    private static function rowNum(string $sq): int {
        return (int)$sq[1];
    }

    private static function toSq(int $c, int $r): string {
        return self::COLS[$c] . $r;
    }

    private static function inBounds(int $c, int $r): bool {
        return $c >= 0 && $c < 6 && $r >= 1 && $r <= 6;
    }

    private static function opponent(string $color): string {
        return $color === 'white' ? 'black' : 'white';
    }

    // ─── 初期ボード ──────────────────────────────────────────

    public static function createInitialBoard(): array {
        $board = [];
        $backPieces = ['rook','knight','bishop','queen','king'];

        foreach ($backPieces as $i => $piece) {
            $board[self::toSq($i, 1)] = ['piece' => $piece, 'color' => 'white', 'character_id' => null, 'active_used' => 0];
            $board[self::toSq($i, 6)] = ['piece' => $piece, 'color' => 'black', 'character_id' => null, 'active_used' => 0];
        }

        for ($i = 0; $i < 6; $i++) {
            $board[self::toSq($i, 2)] = ['piece' => 'pawn', 'color' => 'white', 'character_id' => null, 'active_used' => 0];
            $board[self::toSq($i, 5)] = ['piece' => 'pawn', 'color' => 'black', 'character_id' => null, 'active_used' => 0];
        }

        return $board;
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

    public static function getLegalMoves(array $board, string $sq): array {
        return self::getPseudoMoves($board, $sq);
    }

    // ─── 移動適用 ────────────────────────────────────────────

    public static function applyMove(array $board, string $from, string $to): array {
        $piece = $board[$from];
        unset($board[$from]);

        // ポーン成り
        if ($piece['piece'] === 'pawn') {
            if (($piece['color'] === 'white' && self::rowNum($to) === 6) ||
                ($piece['color'] === 'black' && self::rowNum($to) === 1)) {
                $piece['piece'] = 'queen';
            }
        }

        $board[$to] = $piece;
        return $board;
    }

    // ─── ポイント計算 ────────────────────────────────────────

    public static function calcPoints(array $board, string $color): int {
        $sum = 0;
        foreach ($board as $p) {
            if ($p && $p['color'] === $color && $p['piece'] !== 'king') {
                $sum += self::PIECE_VALUES[$p['piece']] ?? 0;
            }
        }
        return $sum;
    }

    public static function hasKing(array $board, string $color): bool {
        foreach ($board as $p) {
            if ($p && $p['piece'] === 'king' && $p['color'] === $color) return true;
        }
        return false;
    }

    // ─── 移動検証 ────────────────────────────────────────────

    public static function validateMove(array $state, string $player, string $from, string $to): array {
        if ($state['status'] !== 'in_progress') return ['valid' => false, 'reason' => '試合は終了しています'];
        if ($state['currentPlayer'] !== $player)  return ['valid' => false, 'reason' => 'あなたのターンではありません'];

        $piece = $state['board'][$from] ?? null;
        if (!$piece) return ['valid' => false, 'reason' => "{$from} に駒がありません"];

        $color = $player === 'player1' ? 'white' : 'black';
        if ($piece['color'] !== $color) return ['valid' => false, 'reason' => '自分の駒ではありません'];

        $legal = self::getLegalMoves($state['board'], $from);
        if (!in_array($to, $legal, true)) return ['valid' => false, 'reason' => "{$from}→{$to} は不正な移動です"];

        return ['valid' => true];
    }

    // ─── 移動実行 ────────────────────────────────────────────

    public static function executeMove(array $state, string $player, string $from, string $to): array {
        $check = self::validateMove($state, $player, $from, $to);
        if (!$check['valid']) throw new RuntimeException($check['reason']);

        $newBoard       = self::applyMove($state['board'], $from, $to);
        $opponentPlayer = $player === 'player1' ? 'player2' : 'player1';
        $opponentColor  = $opponentPlayer === 'player1' ? 'white' : 'black';

        $status    = 'in_progress';
        $winner    = null;
        $endReason = null;

        // 勝利条件1: キング捕獲
        if (!self::hasKing($newBoard, $opponentColor)) {
            $status    = 'finished';
            $winner    = $player;
            $endReason = 'checkmate';
        }

        // ターンカウント
        $nextTurn = $player === 'player2' ? $state['turn'] + 1 : $state['turn'];

        // 勝利条件2: ターン上限
        if ($status === 'in_progress' && $nextTurn > $state['maxTurns']) {
            $p1pts     = self::calcPoints($newBoard, 'white');
            $p2pts     = self::calcPoints($newBoard, 'black');
            $status    = 'finished';
            $endReason = 'points';
            if ($p1pts > $p2pts)      $winner = 'player1';
            elseif ($p2pts > $p1pts)  $winner = 'player2';
            else                      $winner = 'draw';
        }

        return array_merge($state, [
            'board'         => $newBoard,
            'currentPlayer' => $status === 'finished' ? null : $opponentPlayer,
            'turn'          => $nextTurn,
            'status'        => $status,
            'winner'        => $winner,
            'endReason'     => $endReason,
        ]);
    }
}
