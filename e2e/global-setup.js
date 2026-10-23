// コールドスタート直後(コンテナ再作成後など)の初回リクエストが遅く、最初のテストだけ
// タイムアウトすることがあったため、全テストの前に主要ページを一度開いて温めておく。
const { spawnSync } = require('child_process');
const path = require('path');

module.exports = async (config) => {
  // 毎回同じ状態から始める(画面比較の基準が実行回数に依存しないように)。
  // legacy は既定で setup-test-store.sh を実行。v2 など別の対象は E2E_RESET_CMD で指定。E2E_RESET_CMD='' で無効。
  const target = process.env.E2E_TARGET || 'legacy';
  const reset = process.env.E2E_RESET_CMD !== undefined
    ? process.env.E2E_RESET_CMD
    : (target === 'legacy' ? `bash "${path.join(__dirname, 'setup-test-store.sh')}"` : '');
  if (reset) {
    const r = spawnSync(reset, { shell: true, stdio: 'inherit', cwd: path.join(__dirname, '..') });
    if (r.status !== 0) throw new Error(`E2E データのリセットに失敗: ${reset}`);
  }

  const base = process.env.E2E_BASE_URL || config.projects[0].use.baseURL || 'http://localhost:8000';
  for (const path of ['/login', '/customer_display']) {
    try { await fetch(base + path, { signal: AbortSignal.timeout(60000) }); } catch (e) { /* 無視: 本テストで検知する */ }
  }
};
