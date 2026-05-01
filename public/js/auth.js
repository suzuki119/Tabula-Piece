/* auth.js — セッション認証共通ヘルパー */

async function requireLogin() {
  try {
    const res = await fetch('../api/auth/me.php', { credentials: 'same-origin' });
    if (res.status === 401) {
      location.href = 'login.html';
      return null;
    }
    return await res.json();
  } catch (_) {
    location.href = 'login.html';
    return null;
  }
}

async function apiFetch(url, options = {}) {
  const res = await fetch(url, { credentials: 'same-origin', ...options });
  if (res.status === 401) {
    location.href = 'login.html';
    return null;
  }
  return res;
}

function showToast(msg, type = 'error') {
  const el = document.createElement('div');
  const bg = type === 'error' ? '#b71c1c' : '#2e7d32';
  Object.assign(el.style, {
    position: 'fixed',
    bottom: '24px',
    left: '50%',
    transform: 'translateX(-50%)',
    background: bg,
    color: '#fff',
    padding: '8px 20px',
    borderRadius: '8px',
    fontWeight: '600',
    fontSize: '13px',
    zIndex: '999',
    maxWidth: '90%',
    textAlign: 'center',
    boxShadow: '0 4px 16px rgba(0,0,0,0.4)',
    animation: 'none',
  });
  el.textContent = msg;
  document.body.appendChild(el);
  setTimeout(() => el.remove(), 3500);
}
