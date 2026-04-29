/* login.js — ログイン・新規登録 */

function showLogin() {
  document.getElementById('section-login').classList.remove('hidden');
  document.getElementById('section-register').classList.add('hidden');
}

function showRegister() {
  document.getElementById('section-login').classList.add('hidden');
  document.getElementById('section-register').classList.remove('hidden');
}

async function doLogin() {
  const email    = document.getElementById('login-email').value.trim();
  const password = document.getElementById('login-password').value;
  const errEl    = document.getElementById('login-error');
  const btn      = document.getElementById('btn-login');

  errEl.textContent = '';
  if (!email || !password) { errEl.textContent = 'メールアドレスとパスワードを入力してください'; return; }

  btn.disabled = true;
  btn.textContent = 'ログイン中…';

  try {
    const res  = await fetch('../api/auth/login.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ email, password }),
      credentials: 'same-origin',
    });
    const data = await res.json();
    if (!res.ok) { errEl.textContent = data.error || 'ログインに失敗しました'; return; }
    location.href = 'home.html';
  } catch (_) {
    errEl.textContent = 'ネットワークエラーが発生しました';
  } finally {
    btn.disabled = false;
    btn.textContent = 'ログイン';
  }
}

async function doRegister() {
  const name     = document.getElementById('reg-name').value.trim();
  const email    = document.getElementById('reg-email').value.trim();
  const password = document.getElementById('reg-password').value;
  const errEl    = document.getElementById('reg-error');
  const btn      = document.getElementById('btn-register');

  errEl.textContent = '';
  if (!name || !email || !password) { errEl.textContent = 'すべての項目を入力してください'; return; }

  btn.disabled = true;
  btn.textContent = '作成中…';

  try {
    const res  = await fetch('../api/auth/register.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ name, email, password }),
      credentials: 'same-origin',
    });
    const data = await res.json();
    if (!res.ok) { errEl.textContent = data.error || '登録に失敗しました'; return; }
    location.href = 'home.html';
  } catch (_) {
    errEl.textContent = 'ネットワークエラーが発生しました';
  } finally {
    btn.disabled = false;
    btn.textContent = 'アカウント作成';
  }
}

document.getElementById('btn-login').addEventListener('click', doLogin);
document.getElementById('btn-register').addEventListener('click', doRegister);

document.getElementById('login-password').addEventListener('keydown', e => {
  if (e.key === 'Enter') doLogin();
});
document.getElementById('reg-password').addEventListener('keydown', e => {
  if (e.key === 'Enter') doRegister();
});
