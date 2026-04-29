/* characters.js — キャラクター一覧画面 */

const CLASS_LABEL = {
  pawn:   '♟ ポーン',
  knight: '♞ ナイト',
  bishop: '♝ ビショップ',
  rook:   '♜ ルーク',
  queen:  '♛ クイーン',
  king:   '♚ キング',
};

const CLASS_ICON = {
  pawn: '♟', knight: '♞', bishop: '♝',
  rook: '♜', queen: '♛', king: '♚',
};

let allCharacters = [];
let activeFilter  = 'all';

async function init() {
  const user = await requireLogin();
  if (!user) return;

  document.getElementById('user-name').textContent = user.name;

  const res  = await apiFetch('../api/characters/list.php');
  if (!res) return;
  allCharacters = await res.json();
  renderGrid();
  setupFilters();

  document.getElementById('logout-btn').addEventListener('click', async () => {
    await fetch('../api/auth/logout.php', { method: 'POST', credentials: 'same-origin' });
    location.href = 'login.html';
  });

  document.getElementById('char-modal').addEventListener('click', e => {
    if (e.target === document.getElementById('char-modal')) {
      document.getElementById('char-modal').classList.remove('show');
    }
  });
}

function setupFilters() {
  document.getElementById('filter-bar').addEventListener('click', e => {
    const btn = e.target.closest('.filter-btn');
    if (!btn) return;
    document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    activeFilter = btn.dataset.class;
    renderGrid();
  });
}

function renderGrid() {
  const grid = document.getElementById('char-grid');
  const list = activeFilter === 'all'
    ? allCharacters
    : allCharacters.filter(c => c.piece_class === activeFilter);

  if (!list.length) {
    grid.innerHTML = '<div class="loading">キャラクターがいません</div>';
    return;
  }

  grid.innerHTML = list.map(c => charCardHTML(c)).join('');
}

function charCardHTML(c) {
  const skills = [
    c.active_skill  ? skillTagHTML('A', c.active_skill)  : '',
    c.passive_skill ? skillTagHTML('P', c.passive_skill) : '',
  ].join('');

  return `
    <div class="char-card" data-id="${c.id}" style="cursor:pointer" onclick="showDetail(${c.id})">
      <div class="char-card-top">
        <div class="char-card-icon">${CLASS_ICON[c.piece_class]}</div>
        <div class="char-card-meta">
          <div class="char-card-name">${esc(c.name)}</div>
          <div class="char-card-class">${CLASS_LABEL[c.piece_class]}</div>
        </div>
        <span class="rarity-badge rarity-${c.rarity}">${c.rarity}</span>
      </div>
      <div class="char-card-skills">
        ${skills || '<div class="skill-tag-name" style="margin-top:4px">スキルなし</div>'}
      </div>
    </div>
  `;
}

function skillTagHTML(type, skill) {
  const cls = type === 'A' ? 'a' : 'p';
  const uses = skill.max_uses != null ? ` (${skill.max_uses}回)` : '';
  return `
    <div class="skill-tag" title="${esc(skill.description || '')}">
      <div class="skill-tag-badge ${cls}">${type}</div>
      <div class="skill-tag-name">${esc(skill.name)}${uses}</div>
    </div>
  `;
}

function showDetail(id) {
  const c = allCharacters.find(x => x.id === id);
  if (!c) return;

  const active  = c.active_skill;
  const passive = c.passive_skill;

  document.getElementById('modal-icon').textContent  = CLASS_ICON[c.piece_class];
  document.getElementById('modal-name').textContent  = c.name;
  document.getElementById('modal-class').textContent = CLASS_LABEL[c.piece_class];
  document.getElementById('modal-rarity').textContent = c.rarity;
  document.getElementById('modal-rarity').className   = `rarity-badge rarity-${c.rarity}`;

  const skillsEl = document.getElementById('modal-skills');
  skillsEl.innerHTML = '';

  if (active) {
    skillsEl.innerHTML += `
      <div class="modal-skill">
        <div class="skill-header"><span class="skill-tag-badge a">A</span><strong>${esc(active.name)}</strong>${active.max_uses != null ? `<span class="skill-uses">${active.max_uses}回</span>` : ''}</div>
        <div class="skill-desc">${esc(active.description || '')}</div>
      </div>`;
  }
  if (passive) {
    skillsEl.innerHTML += `
      <div class="modal-skill">
        <div class="skill-header"><span class="skill-tag-badge p">P</span><strong>${esc(passive.name)}</strong></div>
        <div class="skill-desc">${esc(passive.description || '')}</div>
      </div>`;
  }
  if (!active && !passive) {
    skillsEl.innerHTML = '<div class="skill-desc">スキルなし</div>';
  }

  document.getElementById('char-modal').classList.add('show');
}

function esc(s) {
  return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

init();
