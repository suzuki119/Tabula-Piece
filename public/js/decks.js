/* decks.js — デッキ編成画面（クラスシステム対応） */

/* ─── 定数 ─────────────────────────────────────────────── */
const PIECE_TYPES = ['pawn','knight','bishop','rook','queen','king'];
const PIECE_LABEL = { pawn:'ポーン', knight:'ナイト', bishop:'ビショップ', rook:'ルーク', queen:'クイーン', king:'キング' };
const PIECE_ICON  = { pawn:'♟', knight:'♞', bishop:'♝', rook:'♜', queen:'♛', king:'♚' };

const DECK_CLASSES = ['neutral','witch','blade','architect','paladin','dominant'];
const CLASS_LABEL  = { neutral:'ニュートラル', witch:'ウィッチ', blade:'ブレード', architect:'アーキテクト', paladin:'パラディン', dominant:'ドミナント' };
const CLASS_COLOR  = { neutral:'#a7a9be', witch:'#9c6ddc', blade:'#e94560', architect:'#f5a623', paladin:'#5dc8c8', dominant:'#dc6d6d' };

// 白陣の標準駒初期位置 (col:0-5, row:0=後列 1=前列)
const STD_POSITIONS = [
  {col:0,row:0,icon:'♜'}, {col:1,row:0,icon:'♞'}, {col:2,row:0,icon:'♝'},
  {col:3,row:0,icon:'♛'}, {col:4,row:0,icon:'♚'},
  {col:0,row:1,icon:'♟'}, {col:1,row:1,icon:'♟'}, {col:2,row:1,icon:'♟'},
  {col:3,row:1,icon:'♟'}, {col:4,row:1,icon:'♟'}, {col:5,row:1,icon:'♟'},
];

/* ─── 状態 ─────────────────────────────────────────────── */
let allCharacters = [];
let selectedClass = 'neutral';
let currentSlots  = { pawn:null, knight:null, bishop:null, rook:null, queen:null, king:null };
let classPieces   = []; // [{character, col, row}]
let currentDeckId = null;

// モーダル用
let modalMode     = 'slot';   // 'slot' | 'board'
let activeSlot    = null;
let activeBoardCell = null;   // {col, row}
let modalFilter   = 'all';    // 'all' | 'neutral' | selectedClass

/* ─── 初期化 ────────────────────────────────────────────── */
async function init() {
  const user = await requireLogin();
  if (!user) return;
  document.getElementById('user-name').textContent = user.name;

  const [charsRes, decksRes] = await Promise.all([
    apiFetch('../api/characters/list.php'),
    apiFetch('../api/decks/list.php'),
  ]);
  if (!charsRes || !decksRes) return;

  allCharacters = await charsRes.json();
  const decks   = await decksRes.json();

  if (decks && decks.length > 0) {
    const deck = decks[0];
    currentDeckId = deck.id;
    document.getElementById('deck-name').value = deck.name;
    selectedClass = deck.class || 'neutral';
    for (const type of PIECE_TYPES) {
      currentSlots[type] = deck.slots[type]?.character ?? null;
    }
    classPieces = (deck.class_pieces || []).map(cp => ({
      character: {
        id: cp.character_id, name: cp.name, piece_class: cp.piece_class,
        class: cp.class, rarity: cp.rarity,
        active_skill:  cp.active_skill_name  ? {name: cp.active_skill_name}  : null,
        passive_skill: cp.passive_skill_name ? {name: cp.passive_skill_name} : null,
      },
      col: cp.col,
      row: cp.row,
    }));
  }

  renderClassSelector();
  renderSlots();
  renderBoard();
  setupModal();
  setupButtons();

  document.getElementById('logout-btn').addEventListener('click', async () => {
    await fetch('../api/auth/logout.php', { method:'POST', credentials:'same-origin' });
    location.href = 'login.html';
  });
}

/* ─── STEP 1: クラス選択 ────────────────────────────────── */
function renderClassSelector() {
  const container = document.getElementById('class-selector');
  container.innerHTML = DECK_CLASSES.map(cls => {
    const active = cls === selectedClass ? ' active' : '';
    return `<button class="class-btn${active}" data-class="${cls}"
              style="--cls-color:${CLASS_COLOR[cls]}">${CLASS_LABEL[cls]}</button>`;
  }).join('');

  container.querySelectorAll('.class-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      selectedClass = btn.dataset.class;
      classPieces = classPieces.filter(cp => isClassChar(cp.character, selectedClass));
      renderClassSelector();
      renderSlots();
      renderBoard();
    });
  });
}

/* ─── STEP 2: 標準スロット ──────────────────────────────── */
function renderSlots() {
  const container = document.getElementById('deck-slots');
  container.innerHTML = PIECE_TYPES.map(type => slotHTML(type)).join('');
  container.querySelectorAll('.deck-slot').forEach(el => {
    el.addEventListener('click', () => openSlotModal(el.dataset.type));
  });
}

function slotHTML(type) {
  const char = currentSlots[type];
  const charContent = char
    ? `<div class="slot-char-name">${esc(char.name)}
         <span class="rarity-badge rarity-${char.rarity}">${char.rarity}</span>
       </div>
       <div class="slot-char-skills">${skillsText(char)}</div>`
    : `<div class="slot-char-empty">未設定</div>`;
  return `
    <div class="deck-slot" data-type="${type}">
      <div class="slot-piece">${PIECE_ICON[type]}</div>
      <div class="slot-type-name">${PIECE_LABEL[type]}</div>
      <div class="slot-char">${charContent}</div>
      <div class="slot-change-hint">▶</div>
    </div>`;
}

function skillsText(char) {
  const parts = [];
  if (char.active_skill)  parts.push(`A: ${char.active_skill.name}`);
  if (char.passive_skill) parts.push(`P: ${char.passive_skill.name}`);
  return parts.length ? esc(parts.join(' / ')) : 'スキルなし';
}

/* ─── STEP 3: 盤面配置 ──────────────────────────────────── */
function renderBoard() {
  const board = document.getElementById('class-board');
  const isNeutral = selectedClass === 'neutral';
  board.style.opacity = isNeutral ? '0.4' : '1';
  board.style.pointerEvents = isNeutral ? 'none' : '';

  // row 2 (最前線) → row 0 (後列) の順に表示
  let html = '';
  for (let row = 2; row >= 0; row--) {
    html += `<div class="board-row">`;
    for (let col = 0; col < 6; col++) {
      const std = STD_POSITIONS.find(p => p.col === col && p.row === row);
      const cp  = classPieces.find(p => p.col === col && p.row === row);
      const rowLabel = row === 0 ? '後列' : row === 1 ? '前列' : '空き';
      html += cellHTML(col, row, std, cp, rowLabel);
    }
    html += `</div>`;
  }
  board.innerHTML = html;

  board.querySelectorAll('.board-cell').forEach(cell => {
    cell.addEventListener('click', () => openBoardModal(+cell.dataset.col, +cell.dataset.row));
  });
}

function cellHTML(col, row, std, cp, rowLabel) {
  let inner = '';
  if (cp) {
    inner = `<div class="cell-class-piece" title="${esc(cp.character.name)}">
               <div class="cell-piece-name">${esc(cp.character.name.split('・').pop() || cp.character.name)}</div>
               <span class="rarity-badge rarity-${cp.character.rarity}" style="font-size:8px">${cp.character.rarity}</span>
               <div class="cell-remove">✕</div>
             </div>`;
  } else if (std) {
    inner = `<div class="cell-std-piece">${std.icon}</div>`;
  } else {
    inner = `<div class="cell-empty">＋</div>`;
  }
  return `<div class="board-cell ${cp ? 'has-class' : std ? 'has-std' : ''}"
               data-col="${col}" data-row="${row}">${inner}</div>`;
}

/* ─── モーダル（共通） ──────────────────────────────────── */
function setupModal() {
  document.getElementById('modal-close').addEventListener('click', closeModal);
  document.getElementById('modal-overlay').addEventListener('click', e => {
    if (e.target === document.getElementById('modal-overlay')) closeModal();
  });
}

function closeModal() {
  document.getElementById('modal-overlay').classList.remove('show');
  activeSlot = null;
  activeBoardCell = null;
  modalMode = 'slot';
}

/* ─── スロットモーダル ──────────────────────────────────── */
function openSlotModal(type) {
  modalMode = 'slot';
  activeSlot = type;
  modalFilter = 'all';
  document.getElementById('modal-title').textContent =
    `${PIECE_ICON[type]} ${PIECE_LABEL[type]} のキャラクターを選択`;
  renderModalFilter(type);
  renderSlotModalBody(type);
  document.getElementById('modal-overlay').classList.add('show');
}

function renderModalFilter(type) {
  const filterEl = document.getElementById('modal-filter');
  if (selectedClass === 'neutral') {
    filterEl.innerHTML = '';
    return;
  }
  filterEl.innerHTML = ['all','neutral', selectedClass].map(f => {
    const label = f === 'all' ? 'すべて' : CLASS_LABEL[f];
    return `<button class="filter-btn${modalFilter === f ? ' active' : ''}" data-filter="${f}">${label}</button>`;
  }).join('');
  filterEl.querySelectorAll('.filter-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      modalFilter = btn.dataset.filter;
      renderModalFilter(type);
      renderSlotModalBody(type);
    });
  });
}

function renderSlotModalBody(type) {
  const candidates = allCharacters.filter(c => {
    if (c.piece_class !== type) return false;
    return filterChar(c);
  });

  const body = document.getElementById('modal-body');
  const emptyItem = `<div class="modal-empty-option" data-char-id="">未設定（スキルなし）</div>`;
  const items = candidates.map(c => charCardHTML(c, currentSlots[type]?.id === c.id)).join('');
  body.innerHTML = emptyItem + items;

  body.querySelectorAll('[data-char-id]').forEach(el => {
    el.addEventListener('click', () => {
      if (!activeSlot) return;
      const charId = el.dataset.charId ? parseInt(el.dataset.charId, 10) : null;
      currentSlots[activeSlot] = charId ? (allCharacters.find(c => c.id === charId) ?? null) : null;
      renderSlots();
      closeModal();
    });
  });
}

/* ─── 盤面モーダル ──────────────────────────────────────── */
function openBoardModal(col, row) {
  modalMode = 'board';
  activeBoardCell = {col, row};
  const existing = classPieces.find(p => p.col === col && p.row === row);
  document.getElementById('modal-title').textContent =
    `クラス駒を配置（${String.fromCharCode(97+col)}${row+1}）`;
  document.getElementById('modal-filter').innerHTML = '';

  const candidates = allCharacters.filter(c =>
    c.class === selectedClass && selectedClass !== 'neutral'
  );

  const body = document.getElementById('modal-body');
  const removeItem = existing
    ? `<div class="modal-empty-option modal-remove" data-char-id="">駒を取り除く</div>`
    : '';
  const items = candidates.map(c => {
    const placed = classPieces.find(p => p.character.id === c.id && !(p.col === col && p.row === row));
    const label  = placed ? ` <span style="color:var(--text-muted);font-size:11px">(${String.fromCharCode(97+placed.col)}${placed.row+1}に配置中)</span>` : '';
    return charCardHTML(c, existing?.character.id === c.id, label);
  }).join('');

  body.innerHTML = removeItem + items;

  body.querySelectorAll('[data-char-id]').forEach(el => {
    el.addEventListener('click', () => {
      if (!activeBoardCell) return;
      const {col, row} = activeBoardCell;
      classPieces = classPieces.filter(p => !(p.col === col && p.row === row));
      const charId = el.dataset.charId ? parseInt(el.dataset.charId, 10) : null;
      if (charId) {
        // 同じキャラが他のセルにいれば移動
        classPieces = classPieces.filter(p => p.character.id !== charId);
        const char = allCharacters.find(c => c.id === charId);
        if (char) classPieces.push({character: char, col, row});
      }
      renderBoard();
      closeModal();
    });
  });

  document.getElementById('modal-overlay').classList.add('show');
}

/* ─── 共通ヘルパー ──────────────────────────────────────── */
function filterChar(c) {
  const cls = c.class ?? null;
  if (modalFilter === 'neutral') return cls === 'neutral' || cls === null;
  if (modalFilter === selectedClass) return cls === selectedClass;
  // 'all': neutral + selectedClass
  return cls === null || cls === 'neutral' || cls === selectedClass;
}

function isClassChar(c, cls) {
  const cc = c.class ?? null;
  return cc === null || cc === 'neutral' || cc === cls;
}

function charCardHTML(c, isSelected, extraLabel = '') {
  const skills = [
    c.active_skill  ? `<div class="skill-tag"><div class="skill-tag-badge a">A</div><div class="skill-tag-name">${esc(c.active_skill.name)}</div></div>` : '',
    c.passive_skill ? `<div class="skill-tag"><div class="skill-tag-badge p">P</div><div class="skill-tag-name">${esc(c.passive_skill.name)}</div></div>` : '',
  ].join('');
  const clsBadge = c.class && c.class !== 'neutral' && c.class !== null
    ? `<span class="class-badge" style="--cls-color:${CLASS_COLOR[c.class]}">${CLASS_LABEL[c.class]}</span>` : '';

  return `
    <div class="char-card selectable${isSelected ? ' selected' : ''}" data-char-id="${c.id}">
      <div class="char-card-top">
        <div class="char-card-icon">${PIECE_ICON[c.piece_class]}</div>
        <div class="char-card-meta">
          <div class="char-card-name">${esc(c.name)}${extraLabel}</div>
          <div class="char-card-class">${PIECE_LABEL[c.piece_class]} ${clsBadge}</div>
        </div>
        <span class="rarity-badge rarity-${c.rarity}">${c.rarity}</span>
      </div>
      <div class="char-card-skills">
        ${skills || '<div class="skill-tag-name" style="margin-top:4px">スキルなし</div>'}
      </div>
    </div>`;
}

/* ─── 保存・対戦開始 ─────────────────────────────────────── */
function setupButtons() {
  document.getElementById('btn-save').addEventListener('click', saveDeck);
  document.getElementById('btn-start').addEventListener('click', startMatch);
}

async function saveDeck() {
  const name = document.getElementById('deck-name').value.trim() || 'マイデッキ';
  const slots = {};
  for (const type of PIECE_TYPES) slots[type] = currentSlots[type]?.id ?? null;

  const classPiecesPayload = classPieces.map(cp => ({
    character_id: cp.character.id,
    col: cp.col,
    row: cp.row,
  }));

  const btn = document.getElementById('btn-save');
  btn.textContent = '保存中…';
  btn.disabled = true;

  try {
    const res = await apiFetch('../api/decks/save.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({
        deck_id:      currentDeckId,
        name,
        class:        selectedClass,
        slots,
        class_pieces: classPiecesPayload,
      }),
    });
    if (!res) { btn.textContent = '保存'; btn.disabled = false; return; }
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
  if (!currentDeckId) {
    await saveDeck();
    if (!currentDeckId) return;
  }
  location.href = `play.html?deck_id=${currentDeckId}`;
}

function esc(s) {
  return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

init();
