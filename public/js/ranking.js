/* ranking.js — ランキング画面 */

async function init() {
  const user = await requireLogin();
  if (!user) return;

  document.getElementById('user-name').textContent = user.name;

  const res = await apiFetch('../api/ranking/list.php');
  if (!res) return;
  const data = await res.json();

  renderMyStats(data.my_stats);
  renderRanking(data.ranking, user.user_id);

  document.getElementById('logout-btn').addEventListener('click', async () => {
    await fetch('../api/auth/logout.php', { method: 'POST', credentials: 'same-origin' });
    location.href = 'login.html';
  });
}

function renderMyStats(stats) {
  const el = document.getElementById('my-stats');
  if (!stats || stats.total === 0) {
    el.innerHTML = '<div class="empty-state">まだ試合がありません</div>';
    return;
  }

  const rate = stats.total > 0 ? Math.round(stats.wins / stats.total * 100) : 0;

  el.innerHTML = `
    <div class="stat-card">
      <div class="stat-value win-color">${stats.wins}</div>
      <div class="stat-label">勝利</div>
    </div>
    <div class="stat-card">
      <div class="stat-value loss-color">${stats.losses}</div>
      <div class="stat-label">敗北</div>
    </div>
    <div class="stat-card">
      <div class="stat-value">${stats.draws}</div>
      <div class="stat-label">引き分け</div>
    </div>
    <div class="stat-card">
      <div class="stat-value rate-color">${rate}%</div>
      <div class="stat-label">勝率</div>
    </div>
  `;
}

function renderRanking(ranking, myUserId) {
  const el = document.getElementById('ranking-list');
  if (!ranking || ranking.length === 0) {
    el.innerHTML = '<div class="empty-state">まだ戦績データがありません</div>';
    return;
  }

  el.innerHTML = `
    <table class="ranking-table">
      <thead>
        <tr>
          <th class="rank-col">順位</th>
          <th class="name-col">プレイヤー</th>
          <th>勝</th>
          <th>敗</th>
          <th>分</th>
          <th>勝率</th>
        </tr>
      </thead>
      <tbody>
        ${ranking.map(r => rankRowHTML(r, myUserId)).join('')}
      </tbody>
    </table>
  `;
}

function rankRowHTML(r, myUserId) {
  const rate = r.total > 0 ? Math.round(r.wins / r.total * 100) : 0;
  const isMe = r.user_id === myUserId;
  const rankIcon = r.rank === 1 ? '🥇' : r.rank === 2 ? '🥈' : r.rank === 3 ? '🥉' : r.rank;

  return `
    <tr class="${isMe ? 'my-row' : ''}">
      <td class="rank-col">${rankIcon}</td>
      <td class="name-col">${esc(r.name)}${isMe ? ' <span class="me-badge">あなた</span>' : ''}</td>
      <td class="win-color">${r.wins}</td>
      <td class="loss-color">${r.losses}</td>
      <td>${r.draws}</td>
      <td>${rate}%</td>
    </tr>
  `;
}

function esc(s) {
  return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

init();
