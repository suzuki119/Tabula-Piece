<?php
/**
 * Tabula-Piece CPU AI
 * Phase 15: クエストモード用シンプルAI
 *
 * 戦略: 全合法手を列挙し、評価関数で best move を選択。
 *   - 相手キング捕獲は即勝ち扱い
 *   - 駒の捕獲は捕獲対象の価値ぶん加算
 *   - 前進・中央寄せ・小ランダムでタイブレーク
 *   - スタン/要塞/スキル機会は呼び出し側で処理
 */

require_once __DIR__ . '/Chess.php';

class CpuAi {

    public static function pickMove(array $state, string $color): ?array {
        $board            = $state['board'] ?? [];
        $traps            = $state['traps'] ?? [];
        $timedSanctuaries = $state['timedSanctuaries'] ?? [];

        $candidates = [];

        foreach ($board as $sq => $piece) {
            if (!$piece || $piece['color'] !== $color) continue;
            // スタン中・要塞中は動けない
            if ((int)($piece['stunned_turns'] ?? 0) > 0) continue;
            if ($piece['fortress'] ?? false) continue;

            $legal = Chess::getLegalMoves($board, $sq, $traps, $timedSanctuaries);
            foreach ($legal as $to) {
                $candidates[] = [
                    'from'  => $sq,
                    'to'    => $to,
                    'score' => self::scoreMove($board, $piece, $sq, $to, $color),
                ];
            }
        }

        if (!$candidates) return null;

        usort($candidates, fn($a, $b) => $b['score'] <=> $a['score']);

        $best = $candidates[0];
        return ['from' => $best['from'], 'to' => $best['to']];
    }

    private static function scoreMove(array $board, array $piece, string $from, string $to, string $color): float {
        $score = 0.0;
        $target = $board[$to] ?? null;

        if ($target && $target['color'] !== $color) {
            if ($target['piece'] === 'king') {
                $score += 10000;
            } else {
                $base  = Chess::PIECE_VALUES[$target['piece']] ?? 0;
                $bonus = (int)($target['value_bonus'] ?? 0);
                $score += ($base + $bonus) * 10;
                // シールド持ちは捕獲できないので減点
                if ($target['shield'] ?? false) $score -= 8;
            }
        }

        // 前進評価（黒は row が小さい方向へ、白は大きい方向へ）
        $fromR = Chess::rowNum($from);
        $toR   = Chess::rowNum($to);
        $forward = $color === 'black' ? ($fromR - $toR) : ($toR - $fromR);
        if ($piece['piece'] === 'pawn') {
            $score += $forward * 0.8;
        } else if ($piece['piece'] !== 'king') {
            $score += $forward * 0.3;
        } else {
            // キングは前に出すぎない（後退の方が安全）
            $score -= $forward * 0.5;
        }

        // 中央寄せ（c/d 列、3/4 行を加点）
        $toC = Chess::colIdx($to);
        $centerCol = ($toC === 2 || $toC === 3) ? 0.4 : 0.0;
        $centerRow = ($toR === 3 || $toR === 4) ? 0.3 : 0.0;
        $score += $centerCol + $centerRow;

        // 価値の高い駒は無闇に動かさない（キング以外）
        if ($piece['piece'] === 'queen' && !$target) {
            $score -= 0.5;
        }

        // タイブレーク用の小ランダム
        $score += mt_rand(0, 100) / 1000.0;

        return $score;
    }
}
