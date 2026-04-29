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
