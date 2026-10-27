// @ts-check
// E2E 共通ヘルパー。
const { expect } = require('@playwright/test');

const EMAIL = process.env.E2E_EMAIL || 'test@gmail.com';
const PASSWORD = process.env.E2E_PASSWORD || 'Test123456';
// legacy: 担当者・顧客を /manager/update/{id} と顧客一覧の「選択」リンクで選ぶ。
// v2: 担当者・顧客が URL 方式になる。画面操作(クリックする要素)は同じなので、
//     URL 方式への差し替えは下の selectManagerAndCustomer の V2 分岐だけに置く。
const TARGET = process.env.E2E_TARGET || 'legacy';

// 外部(EPSONプリンタ/Pusher)への接続失敗は環境依存なので無視する
// 「プリンター」は印刷処理のログ (接続できない・リトライ上限) の文言
const IGNORED_ERRORS = [/epson/i, /プリンター/, /pusher/i, /WebSocket/i, /ERR_CONNECTION/i, /ERR_NAME_NOT_RESOLVED/i, /Failed to load resource/i];

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
  expect(m, 'テスト担当者がホームの managers に見つからない(setup-test-store.sh / e2e:seed 未実行?)').not.toBeNull();
  await page.goto(`/manager/update/${m[1]}`);
  if (customerName) {
    // v2: /manager/update は ?m= 付きのホームへリダイレクトする。顧客一覧へはその m を引き継ぐ
    // (画面の「顧客一覧」ボタンと同じ URL)。
    await page.goto(TARGET === 'v2' ? `/customer/list${new URL(page.url()).search}` : '/customer/list');
    const card = page.locator('.card', { hasText: customerName }).filter({ has: page.locator('a:has-text("選択")') }).first();
    await expect(card, `${customerName} が顧客一覧にない`).toBeVisible();
    await card.locator('a:has-text("選択")').click();
    await expect(page.locator('body')).toContainText(customerName);
  }
  if (TARGET === 'v2') await carryPosContext(page);
}

/**
 * v2: 選択中の担当者・顧客は URL (?m=&c=) で持つ。テストが page.goto('/order/create') のように
 * クエリ無しで開いても legacy (セッション) と同じ画面になるよう、以後の同一オリジンの画面遷移に
 * 今の m / c を付ける (画面内のリンクは元から m / c 付き)。
 * 「今の」m / c は画面遷移のたびに URL から読み直す (入力クリア → /?m= のあとに古い c を付けない)。
 */
async function carryPosContext(page) {
  const current = new URL(page.url());
  const read = (u) => ['m', 'c'].filter((k) => u.searchParams.has(k)).map((k) => [k, u.searchParams.get(k)]);
  const state = { ctx: read(current) };
  expect(state.ctx.length, '担当者を選んだあとの URL に m が無い').toBeGreaterThan(0);
  if (page.__posContextListener) page.off('framenavigated', page.__posContextListener);
  page.__posContextListener = (frame) => {
    if (frame !== page.mainFrame()) return;
    const u = new URL(frame.url());
    if (u.origin === current.origin && u.searchParams.has('m')) state.ctx = read(u);
  };
  page.on('framenavigated', page.__posContextListener);
  await page.unroute('**/*').catch(() => {});
  await page.route('**/*', (route) => {
    const req = route.request();
    const url = new URL(req.url());
    if (!req.isNavigationRequest() || url.origin !== current.origin || url.searchParams.has('m')) return route.continue();
    for (const [k, v] of state.ctx) url.searchParams.set(k, v);
    return route.continue({ url: url.toString() });
  });
}

module.exports = { TARGET, EMAIL, PASSWORD, login, collectErrors, decodeHtml, selectManagerAndCustomer };
