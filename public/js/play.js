/* play.js — マッチング画面 */

const params  = new URLSearchParams(location.search);
const DECK_ID = parseInt(params.get('deck_id'), 10) || 0;

let pollTimer    = null;
let currentMatchId = null;

/* ─── 初期化 ─────────────────────────────────────────── */
async function init() {
  const user = await requireLogin();
  if (!user) return;

  document.getElementById('user-name').textContent = user.name;

  if (!DECK_ID) {
    show('no-deck');
    hide('mode-select');
    return;
  }

  try {
    const res  = await apiFetch('../api/decks/list.php');
    if (!res) return;
    const data = await res.json();
    const deck = data.find(d => d.id === DECK_ID);
    document.getElementById('deck-info').textContent =
      deck ? `デッキ：${deck.name}` : `デッキID: ${DECK_ID}`;
  } catch (_) {}

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

  document.getElementById('logout-btn').addEventListener('click', async () => {
    await fetch('../api/auth/logout.php', { method: 'POST', credentials: 'same-origin' });
    location.href = 'login.html';
  });
}

/* ─── オンラインマッチング ───────────────────────────── */
async function startOnline() {
  hideAll();
  show('state-online');

  try {
    const res  = await apiFetch('../api/matching/online.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ deck_id: DECK_ID }),
    });
    if (!res) { showModeSelect(); return; }
    const data = await res.json();
    if (data.error) throw new Error(data.error);

    currentMatchId = data.match_id;

    if (data.status === 'matched') {
      navigateToMatch(data.match_id);
    } else {
      pollStatus();
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
    const res  = await apiFetch('../api/matching/room_create.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ deck_id: DECK_ID }),
    });
    if (!res) { showModeSelect(); return; }
    const data = await res.json();
    if (data.error) throw new Error(data.error);

    currentMatchId = data.match_id;
    document.getElementById('room-code-display').textContent = data.room_code;
    pollStatus();
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
    const res  = await apiFetch('../api/matching/room_join.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ deck_id: DECK_ID, room_code: code }),
    });
    if (!res) { btn.disabled = false; btn.textContent = '参加する'; return; }
    const data = await res.json();
    if (data.error) throw new Error(data.error);

    navigateToMatch(data.match_id);
  } catch (e) {
    errEl.textContent = e.message;
    btn.disabled = false;
    btn.textContent = '参加する';
  }
}

/* ─── ポーリング ─────────────────────────────────────── */
function pollStatus() {
  clearPoll();
  pollTimer = setInterval(async () => {
    try {
      const res  = await apiFetch(`../api/matching/status.php?match_id=${currentMatchId}`);
      if (!res) { clearPoll(); return; }
      const data = await res.json();
      if (data.status === 'in_progress') {
        clearPoll();
        navigateToMatch(data.match_id);
      }
    } catch (_) {}
  }, 2500);
}

function clearPoll() {
  if (pollTimer) { clearInterval(pollTimer); pollTimer = null; }
}

/* ─── ナビゲーション ─────────────────────────────────── */
function navigateToMatch(matchId) {
  clearPoll();
  location.href = `match.html?id=${matchId}`;
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
