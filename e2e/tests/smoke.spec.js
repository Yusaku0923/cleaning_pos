// @ts-check
// 移行・UX刷新の合格ラインとなるスモークテスト。
// 全画面が 5xx/JSエラー無しで開き、Vue がマウントされることを確認する。
const { test, expect } = require('@playwright/test');

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

function collectErrors(page) {
  const errors = [];
  page.on('pageerror', (e) => errors.push(`pageerror: ${e.message}`));
  page.on('console', (m) => {
    if (m.type() === 'error' && !IGNORED_ERRORS.some((r) => r.test(m.text()))) errors.push(`console: ${m.text()}`);
  });
  return errors;
}

test.describe('認証', () => {
  test('未ログインは /login にリダイレクトされる', async ({ page }) => {
    await page.goto('/menu');
    await expect(page).toHaveURL(/\/login/);
  });

  test('誤ったパスワードではログインできない', async ({ page }) => {
    await page.goto('/login');
    await page.fill('input[name="email"]', EMAIL);
    await page.fill('input[name="password"]', 'wrong-password');
    await page.click('button[type="submit"]');
    await expect(page).toHaveURL(/\/login/);
  });

  test('正しい認証情報でログインできる', async ({ page }) => {
    await login(page);
    await expect(page).not.toHaveURL(/\/login/);
  });

  test('/login/auto は誤った資格情報だと /login に戻る', async ({ page }) => {
    await page.goto('/login/auto?id=nobody@example.com&pass=wrong');
    await expect(page).toHaveURL(/\/login/);
  });
});

const PAGES = [
  ['/', 'ホーム'],
  ['/menu', 'メニュー'],
  ['/order/create', '注文入力'],
  ['/order/all_show', '注文一覧'],
  ['/customer/list', '顧客一覧'],
  ['/customer/create', '顧客登録'],
  ['/return', '返却'],
  ['/payment', '会計'],
  ['/invoice', '請求'],
  ['/daily_report', '日報'],
  ['/receipt/edit', 'レシート設定'],
  ['/clothes/create', '衣類登録'],
  ['/delivery', '納品書'],
  ['/delivery/email-logs', '納品メールログ'],
  ['/delivery/sp/choice', 'SP選択'],
];

test.describe('主要画面が開く', () => {
  for (const [path, name] of PAGES) {
    test(`${name} (${path})`, async ({ page }) => {
      const errors = collectErrors(page);
      await login(page);
      const res = await page.goto(path);
      expect(res && res.status(), `${path} の HTTP ステータス`).toBeLessThan(400);
      await page.waitForLoadState('networkidle').catch(() => {});
      // レイアウトに本文が描画されていること(白画面の検知)
      const bodyText = (await page.locator('body').innerText()).trim();
      expect(bodyText.length, `${path} が白画面`).toBeGreaterThan(0);
      expect(errors, `${path} でJSエラー`).toEqual([]);
    });
  }
});

test('顧客表示画面は未ログインでも開ける', async ({ page }) => {
  const res = await page.goto('/customer_display');
  expect(res && res.status()).toBeLessThan(400);
});

test('クライアントエラー収集APIが受け付ける(未認証)', async ({ request }) => {
  const res = await request.post('/api/client-error', {
    data: { errors: [{ message: 'e2e smoke', type: 'test' }] },
    headers: { Accept: 'application/json' },
  });
  expect(res.status(), 'client-error API').toBeLessThan(500);
});

test('ログアウトできる', async ({ page }) => {
  await login(page);
  await page.goto('/menu');
  const csrf = await page.locator('meta[name="csrf-token"]').first().getAttribute('content');
  const res = await page.request.post('/logout', { headers: { 'X-CSRF-TOKEN': csrf || '' } });
  expect(res.status()).toBeLessThan(500);
  await page.goto('/menu');
  await expect(page).toHaveURL(/\/login/);
});
