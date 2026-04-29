/**
 * Tabula-Piece チェスエンジン
 * 6×6ボード、キング捕獲で即勝利、ターン切れはポイント比較で判定
 */

const PIECE_VALUES = { pawn: 1, knight: 3, bishop: 3, rook: 5, queen: 9, king: 0 };
const COLS = ['a', 'b', 'c', 'd', 'e', 'f'];

// ─── ボードヘルパー ────────────────────────────────────────────

function colIdx(sq)      { return COLS.indexOf(sq[0]); }
function rowNum(sq)      { return parseInt(sq[1]); }
function toSq(c, r)      { return COLS[c] + r; }
function inBounds(c, r)  { return c >= 0 && c < 6 && r >= 1 && r <= 6; }
function opponent(color) { return color === 'white' ? 'black' : 'white'; }

// ─── 初期ボード ───────────────────────────────────────────────
// 後列: a=Rook, b=Knight, c=Bishop, d=Queen, e=King, f=空
// 白: 後列=row1、前列(ポーン)=row2
// 黒: 前列(ポーン)=row5、後列=row6

function createInitialBoard() {
  const board = {};
  const backPieces = ['rook', 'knight', 'bishop', 'queen', 'king'];

  backPieces.forEach((piece, i) => {
    board[toSq(i, 1)] = { piece, color: 'white', character_id: null, active_used: 0 };
    board[toSq(i, 6)] = { piece, color: 'black', character_id: null, active_used: 0 };
  });

  COLS.forEach((_, i) => {
    board[toSq(i, 2)] = { piece: 'pawn', color: 'white', character_id: null, active_used: 0 };
    board[toSq(i, 5)] = { piece: 'pawn', color: 'black', character_id: null, active_used: 0 };
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
      // 前進
      if (inBounds(c, nr) && !board[toSq(c, nr)]) moves.push(toSq(c, nr));
      // 斜め捕獲
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

// このゲームはキング捕獲=即勝利なので王手回避は強制しない（getLegalMoves = getPseudoMoves）
function getLegalMoves(board, sq) {
  return getPseudoMoves(board, sq);
}

// ─── 移動適用 ─────────────────────────────────────────────────

function applyMove(board, from, to) {
  const newBoard = Object.assign({}, board);
  const piece = Object.assign({}, newBoard[from]);
  delete newBoard[from];

  // ポーン成り（最終段に達したらクイーンに昇格）
  if (piece.piece === 'pawn') {
    if ((piece.color === 'white' && rowNum(to) === 6) ||
        (piece.color === 'black' && rowNum(to) === 1)) {
      piece.piece = 'queen';
    }
  }

  newBoard[to] = piece;
  return newBoard;
}

// ─── ポイント計算 ─────────────────────────────────────────────

function calcPoints(board, color) {
  return Object.values(board).reduce((sum, p) => {
    if (p && p.color === color && p.piece !== 'king') {
      return sum + (PIECE_VALUES[p.piece] || 0);
    }
    return sum;
  }, 0);
}

function hasKing(board, color) {
  return Object.values(board).some(p => p && p.piece === 'king' && p.color === color);
}

// ─── ゲーム状態 ───────────────────────────────────────────────

function createGameState(player1Id, player2Id) {
  return {
    board: createInitialBoard(),
    currentPlayer: 'player1',  // player1 = 白
    turn: 1,
    maxTurns: 30,
    status: 'in_progress',     // 'in_progress' | 'finished'
    winner: null,              // null | 'player1' | 'player2' | 'draw'
    endReason: null,           // null | 'checkmate' | 'points' | 'timeout'
    player1Id,
    player2Id,
  };
}

function playerColor(player) {
  return player === 'player1' ? 'white' : 'black';
}

// ─── 移動検証 ─────────────────────────────────────────────────

function validateMove(state, player, from, to) {
  if (state.status !== 'in_progress') return { valid: false, reason: '試合は終了しています' };
  if (state.currentPlayer !== player)  return { valid: false, reason: 'あなたのターンではありません' };

  const piece = state.board[from];
  if (!piece) return { valid: false, reason: `${from} に駒がありません` };

  const color = playerColor(player);
  if (piece.color !== color) return { valid: false, reason: '自分の駒ではありません' };

  const legal = getLegalMoves(state.board, from);
  if (!legal.includes(to)) return { valid: false, reason: `${from}→${to} は不正な移動です` };

  return { valid: true };
}

// ─── 移動実行 → 新しいゲーム状態を返す ────────────────────────

function executeMove(state, player, from, to) {
  const check = validateMove(state, player, from, to);
  if (!check.valid) throw new Error(check.reason);

  const newBoard = applyMove(state.board, from, to);
  const opponentPlayer = player === 'player1' ? 'player2' : 'player1';
  const opponentColor  = playerColor(opponentPlayer);

  let status    = 'in_progress';
  let winner    = null;
  let endReason = null;

  // 勝利条件1: キング捕獲
  if (!hasKing(newBoard, opponentColor)) {
    status    = 'finished';
    winner    = player;
    endReason = 'checkmate';
  }

  // ターンカウント（黒が手番を終えたらターン+1）
  const nextTurn = player === 'player2' ? state.turn + 1 : state.turn;

  // 勝利条件2: ターン上限
  if (status === 'in_progress' && nextTurn > state.maxTurns) {
    const p1pts = calcPoints(newBoard, 'white');
    const p2pts = calcPoints(newBoard, 'black');
    status    = 'finished';
    endReason = 'points';
    if (p1pts > p2pts)      winner = 'player1';
    else if (p2pts > p1pts) winner = 'player2';
    else                    winner = 'draw';
  }

  return {
    ...state,
    board:         newBoard,
    currentPlayer: status === 'finished' ? null : opponentPlayer,
    turn:          nextTurn,
    status,
    winner,
    endReason,
  };
}

// ─── JSON変換（DB保存用） ─────────────────────────────────────

function serializeBoard(board)  { return JSON.stringify(board); }
function deserializeBoard(json) { return JSON.parse(json); }

// ─── エクスポート（Node.js / ブラウザ両対応） ─────────────────

const Chess = {
  PIECE_VALUES, COLS,
  createInitialBoard, createGameState,
  getLegalMoves, validateMove, executeMove,
  calcPoints, hasKing, applyMove,
  serializeBoard, deserializeBoard,
  playerColor,
};

if (typeof module !== 'undefined' && module.exports) {
  module.exports = Chess;
} else {
  window.Chess = Chess;
}
