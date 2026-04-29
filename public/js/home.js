/* home.js — ホーム画面 */

async function init() {
  const user = await requireLogin();
  if (!user) return;

  document.getElementById('user-name').textContent = user.name;

  try {
    const res     = await apiFetch('../api/matches/list.php');
    if (!res) return;
    const matches = await res.json();

    const active   = matches.filter(m => m.status === 'in_progress');
    const finished = matches.filter(m => m.status === 'finished');

    renderMatchList('matches-list', active, 'active');
    if (finished.length > 0) {
      document.getElementById('finished-section').style.display = '';
      renderMatchList('finished-list', finished, 'finished');
    }
  } catch (e) {
    document.getElementById('matches-list').innerHTML =
      `<div class="loading">読み込みに失敗しました</div>`;
  }

  document.getElementById('logout-btn').addEventListener('click', async () => {
    await fetch('../api/auth/logout.php', { method: 'POST', credentials: 'same-origin' });
    location.href = 'login.html';
  });
}

function renderMatchList(containerId, matches, type) {
  const el = document.getElementById(containerId);
  if (!matches.length) {
    el.innerHTML = '<div class="empty-state">試合がありません</div>';
    return;
  }
  el.innerHTML = matches.map(m => matchCardHTML(m, type)).join('');
}

function matchCardHTML(m, type) {
  const opponent = m.opponent ?? '???';
  const turnBadge = type === 'active'
    ? (m.is_my_turn
        ? '<span class="turn-badge my-turn">あなたの番</span>'
        : '<span class="turn-badge opp-turn">相手の番</span>')
    : resultBadge(m);

  return `
    <a class="match-card" href="match.html?id=${m.id}">
      <div class="match-card-left">
        <div class="match-opponent">vs ${esc(opponent)}</div>
        <div class="match-turn">ターン ${m.current_turn}</div>
      </div>
      <div class="match-card-right">${turnBadge}</div>
    </a>
  `;
}

function resultBadge(m) {
  if (!m.winner_id) return '<span class="turn-badge draw">引き分け</span>';
  return m.my_role === 'player1'
    ? '<span class="turn-badge win">勝利</span>'
    : '<span class="turn-badge loss">敗北</span>';
}

function esc(s) {
  return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

init();
