/**
 * Tabula-Piece 対戦盤面UIコントローラー
 * state.php から状態を取得し、move.php へ移動を送信する
 */

const PIECE_SYMBOLS = {
  white: { pawn:'♙', knight:'♘', bishop:'♗', rook:'♖', queen:'♕', king:'♔' },
  black: { pawn:'♟', knight:'♞', bishop:'♝', rook:'♜', queen:'♛', king:'♚' },
};

const PIECE_NAMES_JA = {
  pawn:'ポーン', knight:'ナイト', bishop:'ビショップ',
  rook:'ルーク', queen:'クイーン', king:'キング',
};

const END_REASON_JA = {
  checkmate: 'キング撃破',
  points:    'ポイント勝負',
  timeout:   'タイムアウト',
};

const API_BASE = '../api/game';
const POLL_INTERVAL  = 3000;  // 相手ターン中のポーリング間隔 (ms)
const TURN_TIME_LIMIT = 60;   // 1手の制限時間 (秒)

class BoardController {
  constructor({ matchId, userId }) {
    this.matchId    = matchId;
    this.userId     = userId;
    this.state      = null;   // サーバーから取得した最新状態
    this.selected   = null;   // 選択中のマス座標
    this.legalMoves = [];
    this.pollTimer  = null;
    this.countdown  = null;
    this.timeLeft   = TURN_TIME_LIMIT;

    this.$board      = document.getElementById('board-grid');
    this.$myBar      = document.getElementById('my-bar');
    this.$oppBar     = document.getElementById('opp-bar');
    this.$myScore    = document.getElementById('my-score');
    this.$oppScore   = document.getElementById('opp-score');
    this.$myName     = document.getElementById('my-name');
    this.$oppName    = document.getElementById('opp-name');
    this.$myColor    = document.getElementById('my-color');
    this.$oppColor   = document.getElementById('opp-color');
    this.$turnNum    = document.getElementById('turn-num');
    this.$maxTurns   = document.getElementById('max-turns');
    this.$timerVal   = document.getElementById('timer-value');
    this.$banner     = document.getElementById('turn-banner');
    this.$piecePanel = document.getElementById('piece-panel');
    this.$overlay    = document.getElementById('result-overlay');
    this.$resultIcon  = document.getElementById('result-icon');
    this.$resultTitle = document.getElementById('result-title');
    this.$resultReason= document.getElementById('result-reason');
  }

  // ─── 起動 ────────────────────────────────────────────────

  async init() {
    await this.fetchState();
    this.startPollingOrTimer();
  }

  // ─── 状態取得 ─────────────────────────────────────────────

  async fetchState() {
    try {
      const res = await fetch(`${API_BASE}/state.php?match_id=${this.matchId}&user_id=${this.userId}`);
      if (!res.ok) throw new Error(`HTTP ${res.status}`);
      this.state = await res.json();
      this.render();
    } catch (e) {
      console.error('状態取得失敗:', e);
    }
  }

  // ─── ポーリング / タイマー制御 ───────────────────────────

  startPollingOrTimer() {
    this.stopAll();
    if (!this.state || this.state.status === 'finished') return;

    if (this.state.is_my_turn) {
      this.startCountdown();
    } else {
      this.pollTimer = setInterval(async () => {
        await this.fetchState();
        if (this.state.is_my_turn || this.state.status === 'finished') {
          clearInterval(this.pollTimer);
          this.pollTimer = null;
          if (this.state.is_my_turn) this.startCountdown();
        }
      }, POLL_INTERVAL);
    }
  }

  startCountdown() {
    this.timeLeft = TURN_TIME_LIMIT;
    this.updateTimerDisplay();
    this.countdown = setInterval(() => {
      this.timeLeft--;
      this.updateTimerDisplay();
      if (this.timeLeft <= 0) {
        clearInterval(this.countdown);
        this.countdown = null;
        this.onTimeout();
      }
    }, 1000);
  }

  stopAll() {
    if (this.pollTimer)  { clearInterval(this.pollTimer);  this.pollTimer  = null; }
    if (this.countdown)  { clearInterval(this.countdown);  this.countdown  = null; }
  }

  updateTimerDisplay() {
    const t = this.$timerVal;
    t.textContent = this.timeLeft;
    t.className = '';
    if (this.timeLeft <= 10)      t.classList.add('danger');
    else if (this.timeLeft <= 20) t.classList.add('warn');
  }

  // タイムアウト: 合法手がある駒の最初の手を自動選択して送信
  async onTimeout() {
    if (!this.state || !this.state.is_my_turn) return;
    const myColor = this.state.my_color;
    for (const [sq, p] of Object.entries(this.state.board)) {
      if (!p || p.color !== myColor) continue;
      const moves = Chess.getLegalMoves(this.state.board, sq);
      if (moves.length > 0) {
        await this.submitMove(sq, moves[0]);
        return;
      }
    }
  }

  // ─── 移動送信 ─────────────────────────────────────────────

  async submitMove(from, to) {
    this.stopAll();
    try {
      const res = await fetch(`${API_BASE}/move.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ match_id: this.matchId, user_id: this.userId, from, to }),
      });
      const data = await res.json();
      if (!res.ok) { alert(data.error || '移動に失敗しました'); this.startPollingOrTimer(); return; }

      // サーバー応答でローカル状態を更新
      this.state = {
        ...this.state,
        board:          data.board,
        turn:           data.turn,
        status:         data.status,
        is_my_turn:     false,
        current_player: this.state.my_role === 'player1' ? 'player2' : 'player1',
      };
      this.selected   = null;
      this.legalMoves = [];

      this.render();

      if (data.status === 'finished') {
        this.showResult(data);
      } else {
        this.startPollingOrTimer();
      }
    } catch (e) {
      console.error('移動送信失敗:', e);
      this.startPollingOrTimer();
    }
  }

  // ─── セルクリック ─────────────────────────────────────────

  onCellClick(sq) {
    if (!this.state || !this.state.is_my_turn) return;

    // 合法手の目的地をクリック → 移動送信
    if (this.selected && this.legalMoves.includes(sq)) {
      this.submitMove(this.selected, sq);
      return;
    }

    const piece    = this.state.board[sq];
    const myColor  = this.state.my_color;

    // 自分の駒を選択
    if (piece && piece.color === myColor) {
      this.selected   = sq;
      this.legalMoves = Chess.getLegalMoves(this.state.board, sq);
      this.renderBoard();
      this.renderPiecePanel(sq, piece);
    } else {
      // 選択解除
      this.selected   = null;
      this.legalMoves = [];
      this.renderBoard();
      this.renderPiecePanel(null, null);
    }
  }

  // ─── 描画 ─────────────────────────────────────────────────

  render() {
    const s = this.state;
    const myColor  = s.my_color;
    const oppColor = myColor === 'white' ? 'black' : 'white';

    this.$myName.textContent  = s.my_name;
    this.$oppName.textContent = s.opponent_name;
    this.$myColor.textContent  = myColor  === 'white' ? '白' : '黒';
    this.$oppColor.textContent = oppColor === 'white' ? '白' : '黒';

    this.$myScore.textContent  = Chess.calcPoints(s.board, myColor);
    this.$oppScore.textContent = Chess.calcPoints(s.board, oppColor);

    this.$turnNum.textContent  = s.turn;
    this.$maxTurns.textContent = s.max_turns;

    // 手番バー強調
    if (s.is_my_turn) {
      this.$myBar.classList.add('active');
      this.$oppBar.classList.remove('active');
      this.$banner.textContent = 'あなたの番です';
      this.$banner.className   = 'turn-banner my-turn';
    } else {
      this.$oppBar.classList.add('active');
      this.$myBar.classList.remove('active');
      this.$banner.textContent = `${s.opponent_name} の番です…`;
      this.$banner.className   = 'turn-banner opponent-turn';
    }

    this.renderBoard();
    this.renderPiecePanel(null, null);

    if (s.status === 'finished') this.showResult(s);
  }

  renderBoard() {
    const s     = this.state;
    const board = s.board;
    // 自分が黒の場合はボードを上下反転して表示（自分が手前）
    const flip  = s.my_color === 'black';

    this.$board.innerHTML = '';

    const rows = flip ? [1,2,3,4,5,6]        : [6,5,4,3,2,1];
    const cols = flip ? ['f','e','d','c','b','a'] : ['a','b','c','d','e','f'];

    rows.forEach(r => {
      cols.forEach(col => {
        const sq    = col + r;
        const piece = board[sq];
        const cIdx  = Chess.COLS.indexOf(col);
        const isLight = (cIdx + r) % 2 === 0;

        const cell = document.createElement('div');
        cell.className = `cell ${isLight ? 'light' : 'dark'}`;
        cell.dataset.sq = sq;

        if (sq === this.selected)          cell.classList.add('selected');
        if (this.legalMoves.includes(sq))  cell.classList.add('movable');
        if (piece && this.legalMoves.includes(sq)) cell.classList.add('has-piece');

        if (piece) {
          const span = document.createElement('span');
          span.className   = 'piece';
          span.textContent = PIECE_SYMBOLS[piece.color][piece.piece];
          cell.appendChild(span);
        }

        cell.addEventListener('click', () => this.onCellClick(sq));
        this.$board.appendChild(cell);
      });
    });
  }

  renderPiecePanel(sq, piece) {
    if (!piece) {
      this.$piecePanel.innerHTML =
        `<span class="piece-hint">駒を選択してください</span>`;
      this.$piecePanel.className = 'piece-panel empty';
      return;
    }
    this.$piecePanel.className = 'piece-panel';
    this.$piecePanel.innerHTML = `
      <span class="piece-icon">${PIECE_SYMBOLS[piece.color][piece.piece]}</span>
      <div class="piece-info">
        <div class="piece-name">${PIECE_NAMES_JA[piece.piece]}（${sq}）</div>
        <div class="piece-hint">移動先: ${this.legalMoves.length}マス</div>
      </div>
    `;
  }

  showResult(data) {
    this.stopAll();
    const isWin  = data.winner === this.state?.my_role || data.winner_id == this.userId;
    const isDraw = data.winner === 'draw' || data.winner === null;

    this.$resultIcon.textContent  = isDraw ? '🤝' : isWin ? '🏆' : '💀';
    this.$resultTitle.textContent = isDraw ? '引き分け' : isWin ? '勝利！' : '敗北…';
    this.$resultReason.textContent =
      END_REASON_JA[data.end_reason || data.endReason] || '';

    this.$overlay.classList.add('show');
  }
}

// ─── エントリポイント ─────────────────────────────────────

document.addEventListener('DOMContentLoaded', () => {
  const params  = new URLSearchParams(location.search);
  const matchId = parseInt(params.get('id')      || '0');
  const userId  = parseInt(params.get('user_id') || '0');

  if (!matchId || !userId) {
    document.body.innerHTML =
      '<p style="color:#e94560;padding:20px">URLに ?id=<試合ID>&user_id=<ユーザーID> を指定してください</p>';
    return;
  }

  const ctrl = new BoardController({ matchId, userId });
  ctrl.init();
});
