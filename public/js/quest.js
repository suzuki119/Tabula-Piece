/* quest.js — クエストモード一覧画面 */

let selectedQuestId = null;
let userDecks = [];

const CLASS_LABEL = {
  neutral:   'ニュートラル',
  witch:     'ウィッチ',
  blade:     'ブレード',
  architect: 'アーキテクト',
  paladin:   'パラディン',
  dominant:  'ドミナント',
};

const DIFFICULTY_LABEL = {
  easy:   '★',
  normal: '★★',
  hard:   '★★★',
};

async function init() {
  const user = await requireLogin();
  if (!user) return;
  document.getElementById('user-name').textContent = user.name;

  document.getElementById('logout-btn').addEventListener('click', async () => {
    await fetch('../api/auth/logout.php', { method: 'POST', credentials: 'same-origin' });
    location.href = 'login.html';
  });

  document.getElementById('modal-close').addEventListener('click', closeModal);
  document.getElementById('deck-modal').addEventListener('click', e => {
    if (e.target.id === 'deck-modal') closeModal();
  });

  await loadDecks();
  await loadQuests();
}

async function loadDecks() {
  try {
    const res = await apiFetch('../api/decks/list.php');
    if (!res) return;
    userDecks = await res.json();
  } catch (_) {
    userDecks = [];
  }
}

async function loadQuests() {
  const container = document.getElementById('quest-list');
  try {
    const res = await apiFetch('../api/quest/list.php');
    if (!res) return;
    const data = await res.json();
    renderQuests(data.chapters || []);
  } catch (e) {
    container.innerHTML = '<div class="empty-state">クエストの読み込みに失敗しました</div>';
  }
}

function renderQuests(chapters) {
  const container = document.getElementById('quest-list');
  if (!chapters.length) {
    container.innerHTML = '<div class="empty-state">クエストはまだありません</div>';
    return;
  }

  container.innerHTML = chapters.map(ch => `
    <section class="quest-chapter">
      <h2 class="quest-chapter-title">Chapter ${ch.chapter}</h2>
      <div class="quest-stages">
        ${ch.stages.map(renderStage).join('')}
      </div>
    </section>
  `).join('');

  container.querySelectorAll('.quest-stage').forEach(el => {
    el.addEventListener('click', () => openDeckModal(parseInt(el.dataset.questId, 10)));
  });
}

function renderStage(s) {
  const clearedBadge = s.cleared ? '<span class="quest-cleared-badge">クリア済</span>' : '';
  const rewardChar   = s.reward_character_name
    ? `<span class="quest-reward-char">★ ${escapeHtml(s.reward_character_name)} (${s.reward_character_rarity})</span>`
    : '';
  return `
    <button class="quest-stage" data-quest-id="${s.id}">
      <div class="quest-stage-head">
        <span class="quest-stage-num">Stage ${s.stage}</span>
        <span class="quest-difficulty">${DIFFICULTY_LABEL[s.difficulty] || ''}</span>
        ${clearedBadge}
      </div>
      <div class="quest-stage-name">${escapeHtml(s.name)}</div>
      <div class="quest-stage-desc">${escapeHtml(s.description || '')}</div>
      <div class="quest-stage-meta">
        <span class="quest-class-tag">CPU: ${CLASS_LABEL[s.cpu_deck_class] || s.cpu_deck_class}</span>
        <span class="quest-reward-gem">💎 ${s.reward_gem}</span>
        ${rewardChar}
      </div>
    </button>
  `;
}

function openDeckModal(questId) {
  selectedQuestId = questId;
  const body = document.getElementById('deck-modal-body');

  if (!userDecks.length) {
    body.innerHTML = `
      <div class="empty-state">デッキがありません。<br>
        <a href="decks.html" style="color:var(--accent);text-decoration:underline;">デッキを作成する</a>
      </div>`;
  } else {
    body.innerHTML = userDecks.map(d => `
      <button class="quest-deck-item" data-deck-id="${d.id}">
        <span class="quest-deck-name">${escapeHtml(d.name)}</span>
        <span class="quest-deck-class">${CLASS_LABEL[d.class] || d.class}</span>
      </button>
    `).join('');

    body.querySelectorAll('.quest-deck-item').forEach(el => {
      el.addEventListener('click', () => startQuest(parseInt(el.dataset.deckId, 10)));
    });
  }

  document.getElementById('deck-modal').classList.add('show');
}

function closeModal() {
  document.getElementById('deck-modal').classList.remove('show');
  selectedQuestId = null;
}

async function startQuest(deckId) {
  if (!selectedQuestId) return;
  try {
    const res = await apiFetch('../api/quest/start.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ quest_id: selectedQuestId, deck_id: deckId }),
    });
    if (!res) return;
    const data = await res.json();
    if (!data.success) {
      showToast(data.error || 'クエスト開始に失敗しました');
      return;
    }
    location.href = `match.html?id=${data.match_id}`;
  } catch (e) {
    showToast('通信エラーが発生しました');
  }
}

function escapeHtml(s) {
  return String(s ?? '').replace(/[&<>"']/g, c => ({
    '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'
  }[c]));
}

init();
