// コールドスタート直後(コンテナ再作成後など)の初回リクエストが遅く、最初のテストだけ
// タイムアウトすることがあったため、全テストの前に主要ページを一度開いて温めておく。
module.exports = async (config) => {
  const base = process.env.E2E_BASE_URL || config.projects[0].use.baseURL || 'http://localhost:8000';
  for (const path of ['/login', '/customer_display']) {
    try { await fetch(base + path, { signal: AbortSignal.timeout(60000) }); } catch (e) { /* 無視: 本テストで検知する */ }
  }
};
