/* gacha.js — ガチャ画面 */

const params  = new URLSearchParams(location.search);
const USER_ID = parseInt(params.get('user_id'), 10) || 1;

const CLASS_ICON = {
  pawn: '♟', knight: '♞', bishop: '♝',
  rook: '♜', queen: '♛', king: '♚',
};

let currentResults = [];

/* ─── 初期化 ─────────────────────────────────────────── */
async function init() {
  applyUserIdToLinks();
  await loadInfo();

  document.getElementById('btn-single').addEventListener('click', () => pull('single'));
  document.getElementById('btn-multi').addEventListener('click',  () => pull('multi'));
  document.getElementById('btn-open').addEventListener('click',   openAll);
  document.getElementById('btn-again').addEventListener('click',  resetToTop);
}

function applyUserIdToLinks() {
  document.querySelectorAll('a[href]').forEach(a => {
    const href = a.getAttribute('href');
    if (href && !href.startsWith('http') && !href.includes('user_id=')) {
      a.setAttribute('href', `${href}${href.includes('?') ? '&' : '?'}user_id=${USER_ID}`);
    }
  });
}

async function loadInfo() {
  try {
    const res  = await fetch(`../api/gacha/info.php?user_id=${USER_ID}`);
    const data = await res.json();
    document.getElementById('stones-value').textContent = data.stones.toLocaleString();
    document.getElementById('owned-display').textContent = `${data.owned}/${data.total} 所持`;
    updateBtnState(data.stones);
  } catch (_) {}
}

function updateBtnState(stones) {
  document.getElementById('btn-single').disabled = stones < 100;
  document.getElementById('btn-multi').disabled  = stones < 1000;
}

/* ─── ガチャを引く ───────────────────────────────────── */
async function pull(mode) {
  const btn = mode === 'single'
    ? document.getElementById('btn-single')
    : document.getElementById('btn-multi');

  btn.disabled = true;
  btn.classList.add('loading');

  try {
    const res  = await fetch('../api/gacha/pull.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ user_id: USER_ID, mode }),
    });
    const data = await res.json();
    if (data.error) throw new Error(data.error);

    currentResults = data.results;
    showStage(data);

    // 石残高を更新
    document.getElementById('stones-value').textContent =
      data.stones_remaining.toLocaleString();
    updateBtnState(data.stones_remaining);
  } catch (e) {
    alert('エラー: ' + e.message);
    btn.disabled = false;
  }
  btn.classList.remove('loading');
}

/* ─── 演出 ───────────────────────────────────────────── */
function showStage(data) {
  document.getElementById('gacha-btns').style.display   = 'none';
  document.getElementById('result-summary').style.display = 'none';
  document.getElementById('gacha-stage').style.display  = '';

  const isSingle = data.results.length === 1;
  const cardsEl  = document.getElementById('gacha-cards');
  cardsEl.innerHTML = '';

  data.results.forEach((r, i) => {
    const card = document.createElement('div');
    card.className = 'gacha-card';
    card.dataset.index = i;

    card.innerHTML = `
      <div class="card-inner">
        <div class="card-front">
          <div class="card-glow rarity-glow-${r.rarity}"></div>
          <div class="card-icon">${CLASS_ICON[r.piece_class]}</div>
          <div class="card-name">${esc(r.name)}</div>
          <span class="rarity-badge rarity-${r.rarity}">${r.rarity}</span>
          ${r.is_new ? '<div class="new-badge">NEW</div>' : '<div class="dupe-badge">+50💎</div>'}
        </div>
        <div class="card-back">
          <div class="card-back-icon">✦</div>
        </div>
      </div>
    `;
    cardsEl.appendChild(card);
  });

  if (isSingle) {
    // 1回: 即フリップ
    setTimeout(() => flipCard(0), 300);
    document.getElementById('btn-open').style.display = 'none';
  } else {
    // 10連: まずカード裏面表示 → オープンボタン
    document.getElementById('btn-open').style.display = '';
  }

  // 結果サマリー準備
  buildSummary(data);
}

function flipCard(index) {
  const card = document.querySelector(`.gacha-card[data-index="${index}"]`);
  if (!card) return;
  card.classList.add('flipped');

  const r = currentResults[index];
  if (r.rarity === 'SSR') triggerSSREffect(card);
  else if (r.rarity === 'SR') card.classList.add('sr-flash');
}

function openAll() {
  document.getElementById('btn-open').style.display = 'none';
  const cards = document.querySelectorAll('.gacha-card');
  cards.forEach((card, i) => {
    setTimeout(() => {
      card.classList.add('flipped');
      const r = currentResults[i];
      if (r.rarity === 'SSR') setTimeout(() => triggerSSREffect(card), 200);
      else if (r.rarity === 'SR') card.classList.add('sr-flash');
    }, i * 120);
  });

  // 全カード表示後にサマリー表示
  setTimeout(() => {
    document.getElementById('result-summary').style.display = '';
  }, cards.length * 120 + 600);
}

function triggerSSREffect(card) {
  card.classList.add('ssr-effect');
  const burst = document.createElement('div');
  burst.className = 'ssr-burst';
  card.appendChild(burst);
  setTimeout(() => burst.remove(), 800);
}

function buildSummary(data) {
  const newChars  = data.results.filter(r => r.is_new);
  const dupeCount = data.results.filter(r => !r.is_new).length;

  const newEl = document.getElementById('summary-new');
  newEl.innerHTML = newChars.length
    ? newChars.map(r =>
        `<span class="summary-char rarity-bg-${r.rarity}">${esc(r.name)}</span>`
      ).join('')
    : '<span class="summary-none">新キャラなし</span>';

  const refEl = document.getElementById('summary-refund');
  refEl.textContent = dupeCount > 0
    ? `重複 ${dupeCount}体 → 💎 ${dupeCount * 50}石 返還`
    : '';
}

/* 演出後にガチャ画面に戻る */
function resetToTop() {
  document.getElementById('gacha-stage').style.display   = 'none';
  document.getElementById('result-summary').style.display = 'none';
  document.getElementById('gacha-btns').style.display    = '';
  // ボタン状態を再チェック
  loadInfo();
}

function esc(s) {
  return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

init();
