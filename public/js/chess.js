/**
 * Tabula-Piece チェスエンジン（フロントエンド用）
 * Phase 4: スキルシステム対応
 */

const PIECE_VALUES = { pawn: 1, knight: 3, bishop: 3, rook: 5, queen: 9, king: 0 };
const COLS = ['a', 'b', 'c', 'd', 'e', 'f'];

// スキルID定数
const SKILL = {
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

// ─── トラップ設置の候補（空きマス）──────────────────────────

function getTrapTargets(board) {
  return getTeleportTargets(board); // 空きマスならどこでも
}

// ─── エクスポート（ブラウザ / Node.js 両対応）────────────────

const Chess = {
  PIECE_VALUES, COLS, SKILL,
  makePiece, createInitialBoard,
  getLegalMoves, getPseudoMoves,
  calcPoints, hasKing, isAdjacent,
  getTeleportTargets, getEnhanceTargets, getTrapTargets,
};

if (typeof module !== 'undefined' && module.exports) {
  module.exports = Chess;
} else {
  window.Chess = Chess;
}
