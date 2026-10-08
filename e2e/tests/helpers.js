// @ts-check
// E2E 共通ヘルパー。
const { expect } = require('@playwright/test');

const EMAIL = process.env.E2E_EMAIL || 'test@gmail.com';
const PASSWORD = process.env.E2E_PASSWORD || 'Test123456';

// 外部(EPSONプリンタ/Pusher)への接続失敗は環境依存なので無視する
const IGNORED_ERRORS = [/epson/i, /pusher/i, /WebSocket/i, /ERR_CONNECTION/i, /ERR_NAME_NOT_RESOLVED/i, /Failed to load resource/i];

async function login(page) {
  await page.goto('/login');
  await page.fill('input[name="email"]', EMAIL);
  await page.fill('input[name="password"]', PASSWORD);
  await Promise.all([
    page.waitForURL((u) => !u.pathname.startsWith('/login'), { timeout: 15000 }),
    page.click('button[type="submit"]'),
  ]);
}

/** ページの pageerror / console.error を集める(外部接続系は除外) */
function collectErrors(page) {
  const errors = [];
  page.on('pageerror', (e) => errors.push(`pageerror: ${e.message}`));
  page.on('console', (m) => {
    if (m.type() === 'error' && !IGNORED_ERRORS.some((r) => r.test(m.text()))) errors.push(`console: ${m.text()}`);
  });
  return errors;
}

/** blade 出力(&quot; と \uXXXX)を元の文字列に戻す */
function decodeHtml(html) {
  return html
    .replace(/&quot;/g, '"').replace(/&amp;/g, '&').replace(/&#039;/g, "'")
    .replace(/\\u([0-9a-fA-F]{4})/g, (_, h) => String.fromCharCode(parseInt(h, 16)));
}

/** 担当者「E2E担当」を選ぶ。顧客名を渡すとその顧客も選ぶ */
async function selectManagerAndCustomer(page, customerName) {
  const home = decodeHtml(await (await page.request.get('/')).text());
  const m = home.match(/"id":(\d+),"store_id":\d+,"name":"E2E担当"/);
  expect(m, 'テスト担当者がホームの managers に見つからない(setup-test-store.sh 未実行?)').not.toBeNull();
  await page.goto(`/manager/update/${m[1]}`);
  if (!customerName) return;
  await page.goto('/customer/list');
  const card = page.locator('.card', { hasText: customerName }).filter({ has: page.locator('a:has-text("選択")') }).first();
  await expect(card, `${customerName} が顧客一覧にない`).toBeVisible();
  await card.locator('a:has-text("選択")').click();
  await expect(page.locator('body')).toContainText(customerName);
}

module.exports = { EMAIL, PASSWORD, login, collectErrors, decodeHtml, selectManagerAndCustomer };
