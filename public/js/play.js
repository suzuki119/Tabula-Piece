/* play.js — マッチング画面 */

const params  = new URLSearchParams(location.search);
const USER_ID = parseInt(params.get('user_id'), 10) || 1;
const DECK_ID = parseInt(params.get('deck_id'), 10) || 0;

let pollTimer    = null;
let currentMatchId = null;

function applyUserIdToLinks() {
  document.querySelectorAll('a[href]').forEach(a => {
    const href = a.getAttribute('href');
    if (href && !href.startsWith('http') && !href.includes('user_id=')) {
      const sep = href.includes('?') ? '&' : '?';
      a.setAttribute('href', `${href}${sep}user_id=${USER_ID}`);
    }
  });
}

/* ─── 初期化 ─────────────────────────────────────────── */
async function init() {
  applyUserIdToLinks();
  if (!DECK_ID) {
    show('no-deck');
    hide('mode-select');
    return;
  }

  // デッキ名を表示
  try {
    const res  = await fetch(`../api/decks/list.php?user_id=${USER_ID}`);
    const data = await res.json();
    const deck = data.find(d => d.id === DECK_ID);
    document.getElementById('deck-info').textContent =
      deck ? `デッキ：${deck.name}` : `デッキID: ${DECK_ID}`;
  } catch (_) { /* 無視 */ }

  show('mode-select');

  document.getElementById('btn-online').addEventListener('click', startOnline);
  document.getElementById('btn-room-create').addEventListener('click', startRoomCreate);
  document.getElementById('btn-room-join').addEventListener('click', () => {
    hideAll();
    show('state-room-join');
  });

  document.getElementById('btn-cancel-online').addEventListener('click', cancelAndGoHome);
  document.getElementById('btn-cancel-room').addEventListener('click', cancelAndGoHome);
  document.getElementById('btn-cancel-join').addEventListener('click', () => {
    hideAll();
    show('mode-select');
  });

  document.getElementById('btn-do-join').addEventListener('click', doRoomJoin);

  const codeInput = document.getElementById('room-code-input');
  codeInput.addEventListener('input', () => {
    codeInput.value = codeInput.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
  });
  codeInput.addEventListener('keydown', e => {
    if (e.key === 'Enter') doRoomJoin();
  });
}

/* ─── オンラインマッチング ───────────────────────────── */
async function startOnline() {
  hideAll();
  show('state-online');

  try {
    const res  = await fetch('../api/matching/online.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ user_id: USER_ID, deck_id: DECK_ID }),
    });
    const data = await res.json();
    if (data.error) throw new Error(data.error);

    currentMatchId = data.match_id;

    if (data.status === 'matched') {
      navigateToMatch(data.match_id, 'player2');
    } else {
      // 待機中 → ポーリング
      pollStatus('online');
    }
  } catch (e) {
    alert('エラー: ' + e.message);
    showModeSelect();
  }
}

/* ─── ルーム作成 ─────────────────────────────────────── */
async function startRoomCreate() {
  hideAll();
  show('state-room-host');

  try {
    const res  = await fetch('../api/matching/room_create.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ user_id: USER_ID, deck_id: DECK_ID }),
    });
    const data = await res.json();
    if (data.error) throw new Error(data.error);

    currentMatchId = data.match_id;
    document.getElementById('room-code-display').textContent = data.room_code;
    pollStatus('room');
  } catch (e) {
    alert('エラー: ' + e.message);
    showModeSelect();
  }
}

/* ─── ルーム参加 ─────────────────────────────────────── */
async function doRoomJoin() {
  const code  = document.getElementById('room-code-input').value.trim();
  const errEl = document.getElementById('join-error');
  errEl.textContent = '';

  if (code.length !== 6) {
    errEl.textContent = '6文字のコードを入力してください';
    return;
  }

  const btn = document.getElementById('btn-do-join');
  btn.disabled = true;
  btn.textContent = '参加中…';

  try {
    const res  = await fetch('../api/matching/room_join.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ user_id: USER_ID, deck_id: DECK_ID, room_code: code }),
    });
    const data = await res.json();
    if (data.error) throw new Error(data.error);

    navigateToMatch(data.match_id, 'player2');
  } catch (e) {
    errEl.textContent = e.message;
    btn.disabled = false;
    btn.textContent = '参加する';
  }
}

/* ─── ポーリング ─────────────────────────────────────── */
function pollStatus(mode) {
  clearPoll();
  pollTimer = setInterval(async () => {
    try {
      const res  = await fetch(`../api/matching/status.php?match_id=${currentMatchId}&user_id=${USER_ID}`);
      const data = await res.json();
      if (data.status === 'in_progress') {
        clearPoll();
        navigateToMatch(data.match_id, data.player_role ?? 'player1');
      }
    } catch (_) { /* 無視してリトライ */ }
  }, 2500);
}

function clearPoll() {
  if (pollTimer) { clearInterval(pollTimer); pollTimer = null; }
}

/* ─── ナビゲーション ─────────────────────────────────── */
function navigateToMatch(matchId, role) {
  clearPoll();
  location.href = `match.html?id=${matchId}&user_id=${USER_ID}`;
}

function cancelAndGoHome() {
  clearPoll();
  location.href = 'home.html';
}

function showModeSelect() {
  hideAll();
  show('mode-select');
}

/* ─── 表示制御 ───────────────────────────────────────── */
const PANELS = ['no-deck', 'mode-select', 'state-online', 'state-room-host', 'state-room-join'];
function hideAll() { PANELS.forEach(id => hide(id)); }
function show(id) { const el = document.getElementById(id); if (el) el.style.display = ''; }
function hide(id) { const el = document.getElementById(id); if (el) el.style.display = 'none'; }

init();
