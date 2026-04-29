/**
 * Tabula-Piece 対戦盤面UIコントローラー
 * Phase 4: スキルシステム対応
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

// スキルモードの種類
const SKILL_MODE = {
  TELEPORT: 'teleport', // 任意の空きマスを選択
  ENHANCE:  'enhance',  // 隣接する味方駒を選択
  TRAP:     'trap',     // 任意の空きマスを選択
};

const API_BASE       = '../api/game';
const POLL_INTERVAL  = 3000;
const TURN_TIME_LIMIT = 60;

class BoardController {
  constructor({ matchId, userId }) {
    this.matchId      = matchId;
    this.userId       = userId;
    this.state        = null;
    this.selected     = null;
    this.legalMoves   = [];
    this.skillMode    = null;  // { type, from, skillId } or null
    this.skillTargets = [];    // 選択可能なターゲットマス
    this.pollTimer    = null;
    this.countdown    = null;
    this.timeLeft     = TURN_TIME_LIMIT;

    this.$board       = document.getElementById('board-grid');
    this.$myBar       = document.getElementById('my-bar');
    this.$oppBar      = document.getElementById('opp-bar');
    this.$myScore     = document.getElementById('my-score');
    this.$oppScore    = document.getElementById('opp-score');
    this.$myName      = document.getElementById('my-name');
    this.$oppName     = document.getElementById('opp-name');
    this.$myColor     = document.getElementById('my-color');
    this.$oppColor    = document.getElementById('opp-color');
    this.$turnNum     = document.getElementById('turn-num');
    this.$maxTurns    = document.getElementById('max-turns');
    this.$timerVal    = document.getElementById('timer-value');
    this.$banner      = document.getElementById('turn-banner');
    this.$piecePanel  = document.getElementById('piece-panel');
    this.$overlay     = document.getElementById('result-overlay');
    this.$resultIcon  = document.getElementById('result-icon');
    this.$resultTitle = document.getElementById('result-title');
    this.$resultReason= document.getElementById('result-reason');

    const homeBtn = document.getElementById('home-btn');
    if (homeBtn) homeBtn.href = 'home.html';

    // 降参ボタン
    const surrenderBtn = document.getElementById('surrender-btn');
    if (surrenderBtn) surrenderBtn.addEventListener('click', () => this.surrender());
  }

  // ─── 起動 ────────────────────────────────────────────────

  async init() {
    await this.fetchState();
    this.startPollingOrTimer();
  }

  // ─── 状態取得 ─────────────────────────────────────────────

  async fetchState() {
    try {
      const res = await fetch(`${API_BASE}/state.php?match_id=${this.matchId}`, { credentials: 'same-origin' });
      if (res.status === 401) { location.href = 'login.html'; return; }
      if (!res.ok) throw new Error(`HTTP ${res.status}`);
      this.state = await res.json();
      this._syncPendingSelection();
      this.render();
    } catch (e) {
      console.error('状態取得失敗:', e);
    }
  }

  // 再移動・スキル機会に応じた選択状態を同期
  _syncPendingSelection() {
    const s = this.state;
    if (s.rematch_sq) {
      this.selected   = s.rematch_sq;
      this.legalMoves = Chess.getLegalMoves(s.board, s.rematch_sq, s.traps || {});
    } else if (s.skill_opportunity) {
      this.selected   = s.skill_opportunity.sq;
      this.legalMoves = [];
    } else {
      this.selected   = null;
      this.legalMoves = [];
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
    if (this.pollTimer) { clearInterval(this.pollTimer); this.pollTimer = null; }
    if (this.countdown) { clearInterval(this.countdown); this.countdown = null; }
  }

  updateTimerDisplay() {
    const t = this.$timerVal;
    t.textContent = this.timeLeft;
    t.className = '';
    if (this.timeLeft <= 10)      t.classList.add('danger');
    else if (this.timeLeft <= 20) t.classList.add('warn');
  }

  async onTimeout() {
    if (!this.state || !this.state.is_my_turn) return;
    const myColor = this.state.my_color;
    for (const [sq, p] of Object.entries(this.state.board)) {
      if (!p || p.color !== myColor) continue;
      const moves = Chess.getLegalMoves(this.state.board, sq, this.state.traps || {});
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
        body: JSON.stringify({ match_id: this.matchId, from, to }),
      });
      const data = await res.json();
      if (!res.ok) { alert(data.error || '移動に失敗しました'); this.startPollingOrTimer(); return; }

      this._applyServerResponse(data);
    } catch (e) {
      console.error('移動送信失敗:', e);
      this.startPollingOrTimer();
    }
  }

  // ─── スキル発動送信 ───────────────────────────────────────

  async submitSkill(from, skillId, target = null) {
    this.stopAll();
    try {
      const body = { match_id: this.matchId, from, skill_id: skillId };
      if (target) body.target = target;

      const res = await fetch(`${API_BASE}/skill.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(body),
      });
      const data = await res.json();
      if (!res.ok) { alert(data.error || 'スキル発動に失敗しました'); this.startPollingOrTimer(); return; }

      // スキルアニメーション的なフィードバック
      if (data.skill_name) {
        this.showSkillNotice(data.skill_name);
      }

      this._applyServerResponse(data);
    } catch (e) {
      console.error('スキル送信失敗:', e);
      this.startPollingOrTimer();
    }
  }

  showSkillNotice(skillName) {
    const el = document.getElementById('skill-notice');
    if (!el) return;
    el.textContent = `${skillName} 発動！`;
    el.classList.add('show');
    setTimeout(() => el.classList.remove('show'), 2000);
  }

  _applyServerResponse(data) {
    this.state = {
      ...this.state,
      board:             data.board,
      traps:             data.traps ?? this.state.traps ?? {},
      turn:              data.turn,
      status:            data.status,
      is_my_turn:        data.is_my_turn ?? false,
      current_player:    data.current_player ?? (this.state.my_role === 'player1' ? 'player2' : 'player1'),
      rematch_sq:        data.rematch_pending ? data.rematch_pending.sq : null,
      skill_opportunity: data.skill_opportunity ?? null,
    };

    this.skillMode    = null;
    this.skillTargets = [];
    this._syncPendingSelection();
    this.render();

    if (data.status === 'finished') {
      this.showResult(data);
    } else if (this.state.is_my_turn) {
      this.startCountdown();
      // 再移動ペンディング中はバナー表示
      if (this.state.rematch_sq) {
        this.$banner.textContent = '再移動：同じ駒をもう一度動かせます';
        this.$banner.className   = 'turn-banner my-turn rematch';
      }
    } else {
      this.startPollingOrTimer();
    }
  }

  // ─── セルクリック ─────────────────────────────────────────

  onCellClick(sq) {
    if (!this.state || !this.state.is_my_turn) return;

    // ─ スキル機会中は移動不可 ─
    if (this.state.skill_opportunity) return;

    // ─ スキルターゲット選択モード ─
    if (this.skillMode) {
      if (this.skillTargets.includes(sq)) {
        const { from, skillId } = this.skillMode;
        this.skillMode    = null;
        this.skillTargets = [];
        this.submitSkill(from, skillId, sq);
      } else {
        // ターゲット外クリックでキャンセル
        this.skillMode    = null;
        this.skillTargets = [];
        this.renderBoard();
        this.renderPiecePanel(null, null);
      }
      return;
    }

    // ─ 再移動ペンディング中 ─
    if (this.state.rematch_sq) {
      if (this.selected === this.state.rematch_sq && this.legalMoves.includes(sq)) {
        this.submitMove(this.selected, sq);
        return;
      }
      // 再移動中は指定された駒しか選べない
      if (sq === this.state.rematch_sq) {
        this.selected   = sq;
        this.legalMoves = Chess.getLegalMoves(this.state.board, sq, this.state.traps || {});
        this.renderBoard();
        this.renderPiecePanel(sq, this.state.board[sq]);
      }
      return;
    }

    // ─ 通常モード ─
    if (this.selected && this.legalMoves.includes(sq)) {
      this.submitMove(this.selected, sq);
      return;
    }

    const piece   = this.state.board[sq];
    const myColor = this.state.my_color;

    if (piece && piece.color === myColor) {
      this.selected   = sq;
      this.legalMoves = Chess.getLegalMoves(this.state.board, sq, this.state.traps || {});
      this.renderBoard();
      this.renderPiecePanel(sq, piece);
    } else {
      this.selected   = null;
      this.legalMoves = [];
      this.renderBoard();
      this.renderPiecePanel(null, null);
    }
  }

  // ─── スキルボタン処理 ─────────────────────────────────────

  onSkillClick(from, skillId) {
    const { SKILL } = Chess;

    switch (skillId) {
      case SKILL.SHIELD:
      case SKILL.VALUE_UP:
        // ターゲット不要: 即発動
        this.submitSkill(from, skillId);
        break;

      case SKILL.TELEPORT:
        this.enterSkillTargetMode(SKILL_MODE.TELEPORT, from, skillId,
          Chess.getTeleportTargets(this.state.board));
        break;

      case SKILL.ENHANCE:
        this.enterSkillTargetMode(SKILL_MODE.ENHANCE, from, skillId,
          Chess.getEnhanceTargets(this.state.board, from, this.state.my_color));
        break;

      case SKILL.TRAP:
        this.enterSkillTargetMode(SKILL_MODE.TRAP, from, skillId,
          Chess.getTrapTargets(this.state.board, from));
        break;

      case SKILL.REMATCH:
        // 再移動: 先に通常移動してから使う
        this.submitSkill(from, skillId);
        break;

      default:
        alert('このスキルはまだ実装されていません');
    }
  }

  enterSkillTargetMode(modeType, from, skillId, targets) {
    if (targets.length === 0) {
      alert('対象となるマスがありません');
      return;
    }
    this.skillMode    = { type: modeType, from, skillId };
    this.skillTargets = targets;
    this.selected     = from;
    this.legalMoves   = [];
    this.renderBoard();

    const hint = {
      [SKILL_MODE.TELEPORT]: '移動先の空きマスを選択',
      [SKILL_MODE.ENHANCE]:  '強化する隣接の味方駒を選択',
      [SKILL_MODE.TRAP]:     'トラップを置くマスを選択',
    }[modeType] || 'ターゲットを選択';

    this.$piecePanel.innerHTML = `
      <div class="skill-target-hint">
        <span>${hint}</span>
        <button class="skill-cancel-btn" onclick="window._boardCtrl.cancelSkillMode()">キャンセル</button>
      </div>
    `;
  }

  cancelSkillMode() {
    this.skillMode    = null;
    this.skillTargets = [];
    this.selected     = null;
    this.legalMoves   = [];
    this.renderBoard();
    this.renderPiecePanel(null, null);
  }

  // ─── 描画 ─────────────────────────────────────────────────

  render() {
    const s        = this.state;
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

    if (s.is_my_turn) {
      this.$myBar.classList.add('active');
      this.$oppBar.classList.remove('active');
      let bannerText = 'あなたの番です';
      let bannerClass = 'turn-banner my-turn';
      if (s.rematch_sq) {
        bannerText  = '再移動：同じ駒をもう一度動かせます';
        bannerClass += ' rematch';
      } else if (s.skill_opportunity) {
        bannerText  = 'スキル発動機会：発動またはスキップ';
        bannerClass += ' opportunity';
      }
      this.$banner.textContent = bannerText;
      this.$banner.className   = bannerClass;
    } else {
      this.$oppBar.classList.add('active');
      this.$myBar.classList.remove('active');
      this.$banner.textContent = `${s.opponent_name} の番です…`;
      this.$banner.className   = 'turn-banner opponent-turn';
    }

    this.renderBoard();
    if (!this.skillMode) {
      if (s.rematch_sq && s.board[s.rematch_sq]) {
        this.renderPiecePanel(s.rematch_sq, s.board[s.rematch_sq]);
      } else if (s.skill_opportunity) {
        this.renderSkillOpportunityPanel(s.skill_opportunity);
      } else {
        this.renderPiecePanel(null, null);
      }
    }

    if (s.status === 'finished') this.showResult(s);
  }

  renderBoard() {
    const s     = this.state;
    const board = s.board;
    const flip  = s.my_color === 'black';
    const myTraps = s.traps || {};

    this.$board.innerHTML = '';

    const rows = flip ? [1,2,3,4,5,6]            : [6,5,4,3,2,1];
    const cols = flip ? ['f','e','d','c','b','a'] : ['a','b','c','d','e','f'];

    rows.forEach(r => {
      cols.forEach(col => {
        const sq      = col + r;
        const piece   = board[sq];
        const cIdx    = Chess.COLS.indexOf(col);
        const isLight = (cIdx + r) % 2 === 0;

        const cell = document.createElement('div');
        cell.className = `cell ${isLight ? 'light' : 'dark'}`;
        cell.dataset.sq = sq;

        if (sq === this.selected)              cell.classList.add('selected');
        if (this.legalMoves.includes(sq))      cell.classList.add('movable');
        if (this.skillTargets.includes(sq))    cell.classList.add('skill-target');
        if (piece && this.legalMoves.includes(sq)) cell.classList.add('has-piece');
        if (piece && this.skillTargets.includes(sq)) cell.classList.add('has-piece');

        // 自分のトラップ表示
        if (myTraps[sq]) cell.classList.add('my-trap');

        if (piece) {
          const span = document.createElement('span');
          span.className = 'piece';
          span.textContent = PIECE_SYMBOLS[piece.color][piece.piece];
          cell.appendChild(span);

          // シールド発動中インジケーター
          if (piece.shield) {
            const shield = document.createElement('span');
            shield.className = 'piece-badge shield-badge';
            shield.textContent = '🛡';
            cell.appendChild(shield);
          }

          // value_bonus インジケーター
          if (piece.value_bonus > 0) {
            const badge = document.createElement('span');
            badge.className = 'piece-badge value-badge';
            badge.textContent = `+${piece.value_bonus}`;
            cell.appendChild(badge);
          } else if (piece.value_bonus < 0) {
            const badge = document.createElement('span');
            badge.className = 'piece-badge value-badge negative';
            badge.textContent = `${piece.value_bonus}`;
            cell.appendChild(badge);
          }

          // 聖域パッシブ表示
          if (piece.passive_skill_id === Chess.SKILL.SANCTUARY && piece.color === s.my_color) {
            cell.classList.add('sanctuary');
          }
        }

        // 再移動待機中のマス
        if (s.rematch_sq === sq) cell.classList.add('rematch-sq');

        cell.addEventListener('click', () => this.onCellClick(sq));
        this.$board.appendChild(cell);
      });
    });
  }

  renderPiecePanel(sq, piece) {
    if (!piece) {
      this.$piecePanel.innerHTML = `<span class="piece-hint">駒を選択してください</span>`;
      this.$piecePanel.className = 'piece-panel empty';
      return;
    }

    this.$piecePanel.className = 'piece-panel';

    const skillMaster = this.state.skill_master || {};
    const isMyPiece   = piece.color === this.state.my_color;
    const isMyTurn    = this.state.is_my_turn;

    // アクティブスキル情報
    let activeSkillHtml = '';
    if (piece.active_skill_id) {
      const sk      = skillMaster[piece.active_skill_id];
      const maxUses = sk?.max_uses ?? '∞';
      const used    = piece.active_used || 0;
      const remaining = maxUses === null ? '∞' : Math.max(0, maxUses - used);
      const canUse  = isMyPiece && isMyTurn && !this.state.rematch_sq &&
                      (maxUses === null || used < maxUses);

      activeSkillHtml = `
        <div class="skill-row active-skill">
          <span class="skill-type-badge active-badge">A</span>
          <div class="skill-detail">
            <div class="skill-name">${sk?.name ?? 'スキル' + piece.active_skill_id}</div>
            <div class="skill-desc">${sk?.description ?? ''}</div>
            <div class="skill-uses">残り ${remaining} 回</div>
          </div>
          ${canUse ? `<button class="skill-btn" data-sq="${sq}" data-skill="${piece.active_skill_id}">発動</button>` : ''}
        </div>
      `;
    }

    // パッシブスキル情報
    let passiveSkillHtml = '';
    if (piece.passive_skill_id) {
      const sk = skillMaster[piece.passive_skill_id];
      passiveSkillHtml = `
        <div class="skill-row passive-skill">
          <span class="skill-type-badge passive-badge">P</span>
          <div class="skill-detail">
            <div class="skill-name">${sk?.name ?? 'パッシブ' + piece.passive_skill_id}</div>
            <div class="skill-desc">${sk?.description ?? ''}</div>
          </div>
        </div>
      `;
    }

    // シールド・価値ボーナス表示
    let statusHtml = '';
    if (piece.shield) statusHtml += `<span class="piece-status shield">🛡 シールド発動中</span>`;
    if (piece.value_bonus > 0) statusHtml += `<span class="piece-status bonus">+${piece.value_bonus} 価値上昇中</span>`;
    if (piece.value_bonus < 0) statusHtml += `<span class="piece-status debuff">${piece.value_bonus} 呪い中</span>`;

    this.$piecePanel.innerHTML = `
      <div class="piece-panel-inner">
        <div class="piece-panel-header">
          <span class="piece-icon">${PIECE_SYMBOLS[piece.color][piece.piece]}</span>
          <div class="piece-info">
            <div class="piece-name">${PIECE_NAMES_JA[piece.piece]}（${sq}）</div>
            <div class="piece-hint">移動先: ${this.legalMoves.length}マス</div>
            ${statusHtml}
          </div>
        </div>
        ${activeSkillHtml}
        ${passiveSkillHtml}
      </div>
    `;

    // 発動ボタンのイベント
    this.$piecePanel.querySelectorAll('.skill-btn').forEach(btn => {
      btn.addEventListener('click', () => {
        const from    = btn.dataset.sq;
        const skillId = parseInt(btn.dataset.skill);
        this.onSkillClick(from, skillId);
      });
    });
  }

  renderSkillOpportunityPanel(opportunity) {
    const sq          = opportunity.sq;
    const piece       = this.state.board[sq];
    const skillMaster = this.state.skill_master || {};
    const sk          = skillMaster[opportunity.skill_id];

    if (!piece) { this.renderPiecePanel(null, null); return; }

    this.$piecePanel.className = 'piece-panel skill-opportunity-panel';
    this.$piecePanel.innerHTML = `
      <div class="piece-panel-inner">
        <div class="opportunity-label">スキル発動機会</div>
        <div class="piece-panel-header">
          <span class="piece-icon">${PIECE_SYMBOLS[piece.color][piece.piece]}</span>
          <div class="piece-info">
            <div class="piece-name">${PIECE_NAMES_JA[piece.piece]}（${sq}）</div>
            <div class="piece-hint">移動後スキルを発動できます</div>
          </div>
        </div>
        <div class="skill-row active-skill">
          <span class="skill-type-badge active-badge">A</span>
          <div class="skill-detail">
            <div class="skill-name">${sk?.name ?? ''}</div>
            <div class="skill-desc">${sk?.description ?? ''}</div>
          </div>
          <button class="skill-btn" id="opp-activate-btn">発動</button>
        </div>
        <button class="skip-btn" id="opp-skip-btn">スキップ（ターン終了）</button>
      </div>
    `;

    document.getElementById('opp-activate-btn').addEventListener('click', () => {
      this.onSkillClick(sq, opportunity.skill_id);
    });
    document.getElementById('opp-skip-btn').addEventListener('click', () => {
      this.skipSkillOpportunity();
    });
  }

  async skipSkillOpportunity() {
    this.stopAll();
    try {
      const res = await fetch(`${API_BASE}/skill.php`, {
        method:  'POST',
        headers: { 'Content-Type': 'application/json' },
        body:    JSON.stringify({ match_id: this.matchId, from: '', skill_id: 0 }),
      });
      const data = await res.json();
      if (!res.ok) { alert(data.error || 'スキップに失敗しました'); this.startPollingOrTimer(); return; }
      this._applyServerResponse(data);
    } catch (e) {
      console.error('スキップ失敗:', e);
      this.startPollingOrTimer();
    }
  }

  async surrender() {
    if (!confirm('本当に降参しますか？')) return;
    try {
      const res  = await fetch(`${API_BASE}/../matches/surrender.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ match_id: this.matchId }),
      });
      const data = await res.json();
      if (data.error) throw new Error(data.error);
      this.showResult({ winner_id: data.winner_id, end_reason: 'checkmate' });
    } catch (e) {
      alert('降参処理に失敗しました: ' + e.message);
    }
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

document.addEventListener('DOMContentLoaded', async () => {
  const params  = new URLSearchParams(location.search);
  const matchId = parseInt(params.get('id') || '0');

  if (!matchId) {
    document.body.innerHTML =
      '<p style="color:#e94560;padding:20px">URLに ?id=<試合ID> を指定してください</p>';
    return;
  }

  const user = await requireLogin();
  if (!user) return;

  const ctrl = new BoardController({ matchId, userId: user.user_id });
  window._boardCtrl = ctrl;
  ctrl.init();
});
