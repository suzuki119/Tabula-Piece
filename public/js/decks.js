/* decks.js — デッキ編成画面 */

const params  = new URLSearchParams(location.search);
const USER_ID = parseInt(params.get('user_id'), 10) || 1;

const CLASSES = ['pawn','knight','bishop','rook','queen','king'];

const CLASS_LABEL = {
  pawn:   'ポーン',
  knight: 'ナイト',
  bishop: 'ビショップ',
  rook:   'ルーク',
  queen:  'クイーン',
  king:   'キング',
};

const CLASS_ICON = {
  pawn: '♟', knight: '♞', bishop: '♝',
  rook: '♜', queen: '♛', king: '♚',
};

let allCharacters = [];  // 全所持キャラ
let currentDeck   = {    // 編集中のスロット {class -> character|null}
  pawn: null, knight: null, bishop: null,
  rook: null, queen: null,  king: null,
};
let currentDeckId   = null;
let activeSlot      = null; // モーダル表示中のクラス名

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
  const [chars, decks] = await Promise.all([
    fetch(`../api/characters/list.php?user_id=${USER_ID}`).then(r => r.json()),
    fetch(`../api/decks/list.php?user_id=${USER_ID}`).then(r => r.json()),
  ]);

  allCharacters = chars;

  // 最初のデッキを読み込む（なければ空）
  if (decks && decks.length > 0) {
    const deck = decks[0];
    currentDeckId = deck.id;
    document.getElementById('deck-name').value = deck.name;
    for (const cls of CLASSES) {
      currentDeck[cls] = deck.slots[cls]?.character ?? null;
    }
  }

  renderSlots();
  setupModal();
  setupButtons();
}

/* ─── スロット描画 ──────────────────────────────────── */
function renderSlots() {
  const container = document.getElementById('deck-slots');
  container.innerHTML = CLASSES.map(cls => slotHTML(cls)).join('');

  container.querySelectorAll('.deck-slot').forEach(el => {
    el.addEventListener('click', () => openModal(el.dataset.class));
  });
}

function slotHTML(cls) {
  const char = currentDeck[cls];
  const charContent = char
    ? `<div class="slot-char-name">${esc(char.name)}
         <span class="rarity-badge rarity-${char.rarity}" style="margin-left:6px">${char.rarity}</span>
       </div>
       <div class="slot-char-skills">${slotSkillsText(char)}</div>`
    : `<div class="slot-char-empty">未設定</div>`;

  return `
    <div class="deck-slot" data-class="${cls}">
      <div class="slot-piece">${CLASS_ICON[cls]}</div>
      <div class="slot-class-name">${CLASS_LABEL[cls]}</div>
      <div class="slot-char">${charContent}</div>
      <div class="slot-change-hint">▶</div>
    </div>
  `;
}

function slotSkillsText(char) {
  const parts = [];
  if (char.active_skill)  parts.push(`A: ${char.active_skill.name}`);
  if (char.passive_skill) parts.push(`P: ${char.passive_skill.name}`);
  return parts.length ? esc(parts.join(' / ')) : 'スキルなし';
}

/* ─── モーダル ──────────────────────────────────────── */
function setupModal() {
  document.getElementById('modal-close').addEventListener('click', closeModal);
  document.getElementById('modal-overlay').addEventListener('click', e => {
    if (e.target === document.getElementById('modal-overlay')) closeModal();
  });
}

function openModal(cls) {
  activeSlot = cls;
  document.getElementById('modal-title').textContent =
    `${CLASS_ICON[cls]} ${CLASS_LABEL[cls]} のキャラクターを選択`;

  const candidates = allCharacters.filter(c => c.piece_class === cls);
  const body = document.getElementById('modal-body');

  const emptyItem = `<div class="modal-empty-option" data-char-id="">未設定（スキルなし）</div>`;

  const items = candidates.map(c => {
    const skills = [
      c.active_skill  ? `<div class="skill-tag"><div class="skill-tag-badge a">A</div><div class="skill-tag-name">${esc(c.active_skill.name)}</div></div>` : '',
      c.passive_skill ? `<div class="skill-tag"><div class="skill-tag-badge p">P</div><div class="skill-tag-name">${esc(c.passive_skill.name)}</div></div>` : '',
    ].join('');

    const isSelected = currentDeck[cls]?.id === c.id ? ' selected' : '';
    return `
      <div class="char-card selectable${isSelected}" data-char-id="${c.id}">
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
  }).join('');

  body.innerHTML = emptyItem + items;

  body.querySelectorAll('[data-char-id]').forEach(el => {
    el.addEventListener('click', () => selectChar(el.dataset.charId));
  });

  document.getElementById('modal-overlay').classList.add('show');
}

function closeModal() {
  document.getElementById('modal-overlay').classList.remove('show');
  activeSlot = null;
}

function selectChar(charIdStr) {
  if (!activeSlot) return;
  const charId = charIdStr ? parseInt(charIdStr, 10) : null;
  currentDeck[activeSlot] = charId
    ? (allCharacters.find(c => c.id === charId) ?? null)
    : null;
  renderSlots();
  closeModal();
}

/* ─── 保存・対戦開始 ─────────────────────────────────── */
function setupButtons() {
  document.getElementById('btn-save').addEventListener('click', saveDeck);
  document.getElementById('btn-start').addEventListener('click', startMatch);
}

async function saveDeck() {
  const name  = document.getElementById('deck-name').value.trim() || 'マイデッキ';
  const slots = {};
  for (const cls of CLASSES) {
    slots[cls] = currentDeck[cls]?.id ?? null;
  }

  const btn = document.getElementById('btn-save');
  btn.textContent = '保存中…';
  btn.disabled = true;

  try {
    const res  = await fetch('../api/decks/save.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ user_id: USER_ID, deck_id: currentDeckId, name, slots }),
    });
    const data = await res.json();
    if (data.error) throw new Error(data.error);
    currentDeckId = data.deck_id;
    btn.textContent = '保存済 ✓';
    setTimeout(() => { btn.textContent = '保存'; btn.disabled = false; }, 1500);
  } catch (e) {
    alert('保存失敗: ' + e.message);
    btn.textContent = '保存';
    btn.disabled = false;
  }
}

async function startMatch() {
  // 未保存なら先に保存
  if (!currentDeckId) {
    await saveDeck();
    if (!currentDeckId) return;
  }

  // マッチング画面へ（ユーザーID・デッキIDを渡す）
  location.href = `play.html?user_id=${USER_ID}&deck_id=${currentDeckId}`;
}

function esc(s) {
  return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

init();
