/* characters.js — キャラクター一覧画面 */

const params  = new URLSearchParams(location.search);
const USER_ID = parseInt(params.get('user_id'), 10) || 1;

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
  const res  = await fetch(`../api/characters/list.php?user_id=${USER_ID}`);
  allCharacters = await res.json();
  renderGrid();
  setupFilters();
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
    <div class="char-card">
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

function esc(s) {
  return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

init();
