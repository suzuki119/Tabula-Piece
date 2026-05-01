/**
 * Tabula-Piece チェスエンジン（フロントエンド用）
 * Phase 4: スキルシステム対応
 */

const PIECE_VALUES = { pawn: 1, knight: 3, bishop: 3, rook: 5, queen: 9, king: 0 };
const COLS = ['a', 'b', 'c', 'd', 'e', 'f'];

// スキルID定数
const SKILL = {
  // オリジナル
  REMATCH:     1,
  TELEPORT:    2,
  FIRST_MOVE:  3,
  SHIELD:      4,
  ENHANCE:     5,
  COUNTER:     6,
  VALUE_UP:    7,
  DEATH_CURSE: 8,
  TRAP:        9,
  SANCTUARY:   10,
  // クラス: 支援・回復系
  HOLY_WALL:          11,
  HOLY_WALL_2:        12,
  HOLY_AURA:          13,
  DIVINE_PROTECTION:  14,
  REVIVE:             15,
  REVIVE_SHIELD:      16,
  SACRIFICE:          17,
  // クラス: 移動バフ系
  INSPIRE:            18,
  INSPIRE_STRONG:     19,
  INSPIRE_ALL:        20,
  // クラス: 攻撃・貫通系
  PENETRATE:          21,
  CLEAVE:             22,
  DOUBLE_STRIKE:      23,
  PIERCING_SLASH:     24,
  SHADOW_CUT:         25,
  SWAMP_WAVE:         26,
  AREA_THRUST:        27,
  EXPLOSION:          28,
  LIGHTNING_SLASH:    29,
  // クラス: 価値操作系
  VALUE_DOWN_SM:      30,
  VALUE_DOWN_MD:      31,
  VALUE_DOWN_LG:      32,
  DECOY:              33,
  CURSED_TRADE:       34,
  VALUE_GAIN_SM:      35,
  VALUE_GAIN_MD:      36,
  VALUE_GRANT_MD:     37,
  VALUE_GRANT_LG:     38,
  FULL_CURSE:         39,
  SWAMP_GRACE:        40,
  // クラス: 罠・設置系
  STUN_TRAP:          41,
  STUN_TRAP_STRONG:   42,
  FORCE_MOVE_TRAP:    43,
  MAZE_TRAP:          44,
  PHANTOM_TRAP:       45,
  WIDE_TRAP:          46,
  FORTRESS:           47,
  FORTRESS_LARGE:     48,
  FORTRESS_SELF:      49,
  TRAP_SENSE:         50,
  // クラス: 支配・コピー系
  DOMINATE:           51,
  CHARM_LONG:         52,
  CHARM:              53,
  DISABLE:            54,
  HINDER:             55,
  CHAIN_STRONG:       56,
  CHAIN:              57,
  FORCE_MOVE:         58,
  DOMINATION_MEMORY:  59,
  SKILL_STEAL:        60,
  MIMIC_WEAK:         61,
  FULL_MIMIC:         62,
  WAR_TROPHY:         63,
  DOMINATION_ECHO:    64,
  ASSAULT_DOMINATE:   65,
  INFECT:             66,
  CURSE_REFLECT:      67,
  STUN:               68,
  STORM_STRIKE:       69,
  HINDER_STRONG:      70,
  TIMED_SANCTUARY:    71,
  GUARD_REACTION:     72,
  COUNTER_MOVE:       73,
};

// ─── ボードヘルパー ────────────────────────────────────────────

function colIdx(sq)      { return COLS.indexOf(sq[0]); }
function rowNum(sq)      { return parseInt(sq[1]); }
function toSq(c, r)      { return COLS[c] + r; }
function inBounds(c, r)  { return c >= 0 && c < 6 && r >= 1 && r <= 6; }
function opponent(color) { return color === 'white' ? 'black' : 'white'; }

function isAdjacent(sq1, sq2) {
  const dc = Math.abs(colIdx(sq1) - colIdx(sq2));
  const dr = Math.abs(rowNum(sq1) - rowNum(sq2));
  return sq1 !== sq2 && dc <= 1 && dr <= 1;
}

// ─── 初期ボード ───────────────────────────────────────────────

function makePiece(piece, color, extra = {}) {
  return {
    piece,
    color,
    character_id:     null,
    active_skill_id:  null,
    passive_skill_id: null,
    active_used:      0,
    passive_used:     0,
    shield:           false,
    value_bonus:      0,
    ...extra,
  };
}

function createInitialBoard() {
  const board = {};
  const backPieces = ['rook', 'knight', 'bishop', 'queen', 'king'];

  backPieces.forEach((piece, i) => {
    board[toSq(i, 1)] = makePiece(piece, 'white');
    board[toSq(i, 6)] = makePiece(piece, 'black');
  });

  COLS.forEach((_, i) => {
    board[toSq(i, 2)] = makePiece('pawn', 'white');
    board[toSq(i, 5)] = makePiece('pawn', 'black');
  });

  return board;
}

// ─── 疑似合法手生成 ────────────────────────────────────────────

function getPseudoMoves(board, sq) {
  const p = board[sq];
  if (!p) return [];

  const c = colIdx(sq);
  const r = rowNum(sq);
  const moves = [];

  const addSlide = (dc, dr) => {
    let nc = c + dc, nr = r + dr;
    while (inBounds(nc, nr)) {
      const target = board[toSq(nc, nr)];
      if (target) {
        if (target.color !== p.color) moves.push(toSq(nc, nr));
        break;
      }
      moves.push(toSq(nc, nr));
      nc += dc; nr += dr;
    }
  };

  const addStep = (dc, dr) => {
    const nc = c + dc, nr = r + dr;
    if (!inBounds(nc, nr)) return;
    const target = board[toSq(nc, nr)];
    if (!target || target.color !== p.color) moves.push(toSq(nc, nr));
  };

  switch (p.piece) {
    case 'pawn': {
      const dir = p.color === 'white' ? 1 : -1;
      const nr = r + dir;
      if (inBounds(c, nr) && !board[toSq(c, nr)]) moves.push(toSq(c, nr));
      [-1, 1].forEach(dc => {
        if (!inBounds(c + dc, nr)) return;
        const t = board[toSq(c + dc, nr)];
        if (t && t.color !== p.color) moves.push(toSq(c + dc, nr));
      });
      break;
    }
    case 'knight':
      [[-2,-1],[-2,1],[-1,-2],[-1,2],[1,-2],[1,2],[2,-1],[2,1]]
        .forEach(([dc, dr]) => addStep(dc, dr));
      break;
    case 'bishop':
      [[-1,-1],[-1,1],[1,-1],[1,1]].forEach(([dc, dr]) => addSlide(dc, dr));
      break;
    case 'rook':
      [[-1,0],[1,0],[0,-1],[0,1]].forEach(([dc, dr]) => addSlide(dc, dr));
      break;
    case 'queen':
      [[-1,-1],[-1,1],[1,-1],[1,1],[-1,0],[1,0],[0,-1],[0,1]]
        .forEach(([dc, dr]) => addSlide(dc, dr));
      break;
    case 'king':
      [[-1,-1],[-1,0],[-1,1],[0,-1],[0,1],[1,-1],[1,0],[1,1]]
        .forEach(([dc, dr]) => addStep(dc, dr));
      break;
  }
  return moves;
}

// 聖域スキル考慮
function getLegalMoves(board, sq, traps = {}) {
  const piece = board[sq];
  if (!piece) return [];

  let moves = getPseudoMoves(board, sq);
  const opColor = opponent(piece.color);

  // 聖域: 相手の駒が聖域パッシブを持つマスは進入不可
  Object.entries(board).forEach(([oSq, op]) => {
    if (op && op.color === opColor && op.passive_skill_id === SKILL.SANCTUARY) {
      moves = moves.filter(m => m !== oSq);
    }
  });

  return moves;
}

// ─── ポイント計算（value_bonus 考慮）─────────────────────────

function calcPoints(board, color) {
  return Object.values(board).reduce((sum, p) => {
    if (!p || p.color !== color || p.piece === 'king') return sum;
    const base  = PIECE_VALUES[p.piece] || 0;
    const bonus = p.value_bonus || 0;
    return sum + Math.max(0, base + bonus);
  }, 0);
}

function hasKing(board, color) {
  return Object.values(board).some(p => p && p.piece === 'king' && p.color === color);
}

// ─── テレポートの移動先候補（全空きマス）────────────────────

function getTeleportTargets(board) {
  const all = [];
  COLS.forEach(col => {
    for (let r = 1; r <= 6; r++) {
      const sq = col + r;
      if (!board[sq]) all.push(sq);
    }
  });
  return all;
}

// ─── 駒強化の対象候補（隣接する味方駒）──────────────────────

function getEnhanceTargets(board, from, color) {
  return Object.keys(board).filter(sq => {
    const p = board[sq];
    return p && p.color === color && isAdjacent(from, sq);
  });
}

// ─── トラップ設置の候補（隣接する空きマスのみ）──────────────

function getTrapTargets(board, from) {
  const c = colIdx(from);
  const r = rowNum(from);
  const targets = [];
  for (let dc = -1; dc <= 1; dc++) {
    for (let dr = -1; dr <= 1; dr++) {
      if (dc === 0 && dr === 0) continue;
      const nc = c + dc, nr = r + dr;
      if (!inBounds(nc, nr)) continue;
      const sq = toSq(nc, nr);
      if (!board[sq]) targets.push(sq);
    }
  }
  return targets;
}

// ─── クラススキル用ターゲット候補ヘルパー ────────────────────

function getAdjacentSquares(sq) {
  const c = colIdx(sq), r = rowNum(sq);
  const result = [];
  for (let dc = -1; dc <= 1; dc++) {
    for (let dr = -1; dr <= 1; dr++) {
      if (dc === 0 && dr === 0) continue;
      if (inBounds(c + dc, r + dr)) result.push(toSq(c + dc, r + dr));
    }
  }
  return result;
}

function getAdjacentAllyTargets(board, sq, color) {
  return getAdjacentSquares(sq).filter(s => board[s]?.color === color);
}

function getAdjacentEnemyTargets(board, sq, color) {
  const opp = opponent(color);
  return getAdjacentSquares(sq).filter(s => board[s]?.color === opp && board[s]?.piece !== 'king');
}

function getAdjacentEmptyTargets(board, sq) {
  return getAdjacentSquares(sq).filter(s => !board[s]);
}

function getAnyEnemyTargets(board, color, excludeKing = false) {
  const opp = opponent(color);
  return Object.keys(board).filter(s => {
    const p = board[s];
    return p && p.color === opp && (!excludeKing || p.piece !== 'king');
  });
}

function getAnyAllyTargets(board, color) {
  return Object.keys(board).filter(s => board[s]?.color === color);
}

function getRange2Targets(board, sq, color) {
  const opp = opponent(color);
  const c = colIdx(sq), r = rowNum(sq);
  const result = [];
  for (let dc = -2; dc <= 2; dc++) {
    for (let dr = -2; dr <= 2; dr++) {
      if (Math.max(Math.abs(dc), Math.abs(dr)) !== 2) continue;
      if (!inBounds(c + dc, r + dr)) continue;
      const s = toSq(c + dc, r + dr);
      const p = board[s];
      if (p && p.color === opp && p.piece !== 'king') result.push(s);
    }
  }
  return result;
}

function getLineTargets(board, sq) {
  const c = colIdx(sq), r = rowNum(sq);
  const dirs = [[-1,-1],[-1,1],[1,-1],[1,1],[-1,0],[1,0],[0,-1],[0,1]];
  const result = [];
  for (const [dc, dr] of dirs) {
    let nc = c + dc, nr = r + dr;
    while (inBounds(nc, nr)) {
      result.push(toSq(nc, nr));
      nc += dc; nr += dr;
    }
  }
  return [...new Set(result)];
}

// ─── エクスポート（ブラウザ / Node.js 両対応）────────────────

const Chess = {
  PIECE_VALUES, COLS, SKILL,
  makePiece, createInitialBoard,
  getLegalMoves, getPseudoMoves,
  calcPoints, hasKing, isAdjacent,
  getTeleportTargets, getEnhanceTargets, getTrapTargets,
  getAdjacentSquares, getAdjacentAllyTargets, getAdjacentEnemyTargets,
  getAdjacentEmptyTargets, getAnyEnemyTargets, getAnyAllyTargets,
  getRange2Targets, getLineTargets,
};

if (typeof module !== 'undefined' && module.exports) {
  module.exports = Chess;
} else {
  window.Chess = Chess;
}
