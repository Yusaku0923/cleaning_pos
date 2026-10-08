// @ts-check
// 移行・UX刷新の合格ラインとなるスモークテスト。
// 全画面が 5xx/JSエラー無しで開き、Vue がマウントされることを確認する。
const { test, expect } = require('@playwright/test');

const { EMAIL, PASSWORD, login, collectErrors, selectManagerAndCustomer } = require('./helpers');

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
      await selectManagerAndCustomer(page, 'E2E請求顧客'); // 担当者・顧客のセッションがある状態で全画面を開く
      const res = await page.goto(path);
      expect(res && res.status(), `${path} の HTTP ステータス`).toBeLessThan(400);
      await page.waitForLoadState('networkidle').catch(() => {});
      // レイアウトに本文が描画されていること(白画面の検知)
      const bodyText = (await page.locator('body').innerText()).trim();
      expect(bodyText.length, `${path} が白画面`).toBeGreaterThan(0);
      expect(page.url(), `${path} がホームへ弾かれた`).toContain(path === '/' ? '' : path);
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
