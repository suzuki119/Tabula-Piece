/**
 * sound.js — Web Audio API を使ったサウンドエフェクト（外部ファイル不要）
 */

const SoundManager = (() => {
  let ctx = null;
  let enabled = localStorage.getItem('sound_enabled') !== 'false';

  function getCtx() {
    if (!ctx) ctx = new (window.AudioContext || window.webkitAudioContext)();
    return ctx;
  }

  function tone(freq, dur, type = 'sine', gain = 0.15, delay = 0) {
    if (!enabled) return;
    try {
      const ac = getCtx();
      if (ac.state === 'suspended') ac.resume();
      const osc = ac.createOscillator();
      const env = ac.createGain();
      osc.type = type;
      osc.frequency.value = freq;
      env.gain.setValueAtTime(gain, ac.currentTime + delay);
      env.gain.exponentialRampToValueAtTime(0.0001, ac.currentTime + delay + dur);
      osc.connect(env);
      env.connect(ac.destination);
      osc.start(ac.currentTime + delay);
      osc.stop(ac.currentTime + delay + dur);
    } catch (_) {}
  }

  return {
    isEnabled: () => enabled,

    toggle() {
      enabled = !enabled;
      localStorage.setItem('sound_enabled', enabled);
      return enabled;
    },

    // 駒移動
    move() {
      tone(320, 0.07, 'triangle', 0.12);
    },

    // 駒取り
    capture() {
      tone(200, 0.06, 'sawtooth', 0.14);
      tone(150, 0.12, 'sine', 0.08, 0.06);
    },

    // スキル発動
    skill() {
      tone(660, 0.1,  'sine', 0.12);
      tone(880, 0.15, 'sine', 0.08, 0.08);
    },

    // 勝利
    win() {
      [523, 659, 784, 1047].forEach((f, i) => tone(f, 0.25, 'sine', 0.15, i * 0.12));
    },

    // 敗北
    lose() {
      [300, 250, 200].forEach((f, i) => tone(f, 0.3, 'sawtooth', 0.1, i * 0.2));
    },

    // タイマー警告
    timerWarn() {
      tone(880, 0.05, 'square', 0.08);
    },

    // タイマー危険（毎秒）
    timerDanger() {
      tone(1100, 0.05, 'square', 0.1);
    },
  };
})();
