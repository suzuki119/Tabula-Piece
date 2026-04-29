/* home.js — ホーム画面 */

const params  = new URLSearchParams(location.search);
const USER_ID = parseInt(params.get('user_id'), 10) || 1;

function applyUserIdToLinks() {
  document.querySelectorAll('a[href]').forEach(a => {
    const href = a.getAttribute('href');
    if (href && !href.startsWith('http') && !href.includes('user_id=')) {
      const sep = href.includes('?') ? '&' : '?';
      a.setAttribute('href', `${href}${sep}user_id=${USER_ID}`);
    }
  });
}

async function init() {
  applyUserIdToLinks();
  try {
    const res     = await fetch(`../api/matches/list.php?user_id=${USER_ID}`);
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

  const href = `match.html?id=${m.id}&user_id=${USER_ID}`;

  return `
    <a class="match-card" href="${href}">
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
