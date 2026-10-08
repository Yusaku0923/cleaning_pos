// @ts-check
// UX 刷新(ログイン/メニュー)の確認。
const { test, expect } = require('@playwright/test');
const { login, collectErrors, selectManagerAndCustomer } = require('./helpers');

test('ログイン画面は日本語で、入力欄が大きく表示される', async ({ page }) => {
  await page.goto('/login');
  await expect(page.locator('h1')).toHaveText('ログイン');
  await expect(page.locator('label[for="email"]')).toHaveText('メールアドレス');
  await expect(page.locator('label[for="password"]')).toHaveText('パスワード');
  await expect(page.locator('body')).not.toContainText('Email Address');
  const h = await page.locator('#email').evaluate((el) => el.getBoundingClientRect().height);
  expect(h).toBeGreaterThanOrEqual(44); // タッチ操作の最小サイズ
});

test('ログイン失敗時は日本語のエラーが出る', async ({ page }) => {
  await page.goto('/login');
  await page.fill('#email', 'nobody@example.com');
  await page.fill('#password', 'wrong-password');
  await page.click('button[type="submit"]');
  await expect(page.locator('.invalid-feedback')).toContainText('正しくありません');
});

test.describe('メニュー', () => {
  test('タイルは全てリンク切れ無しで開き、未実装は「準備中」と明示される', async ({ page }) => {
    const errors = collectErrors(page);
    await login(page);
    await selectManagerAndCustomer(page);
    await page.goto('/menu');
    await expect(page).toHaveTitle(/メニュー \| /);

    // href の無い a タグ(押しても何も起きないタイル)が無い
    expect(await page.locator('.menu-tile:is(a):not([href])').count()).toBe(0);
    await expect(page.locator('.menu-tile-disabled')).toContainText('準備中');

    const hrefs = await page.locator('a.menu-tile').evaluateAll((a) => a.map((x) => x.getAttribute('href')));
    expect(hrefs.length).toBeGreaterThanOrEqual(5);
    for (const href of hrefs) {
      const res = await page.request.get(href);
      expect(res.status(), `${href}`).toBeLessThan(400);
    }
    expect(errors).toEqual([]);
  });

  test('本番設定では開発用のキャッシュ/ビルド操作が出ない', async ({ page }) => {
    await login(page);
    await page.goto('/menu');
    await expect(page.locator('#build-vue-btn')).toHaveCount(0);
    await expect(page.locator('#clear-and-build-btn')).toHaveCount(0);
    await expect(page.locator('#browser-cache-clear-btn')).toBeVisible();
  });

  test('スマホ幅でも横スクロールせずタイルが1列に収まる', async ({ browser }) => {
    const ctx = await browser.newContext({ viewport: { width: 390, height: 800 } });
    const page = await ctx.newPage();
    await login(page);
    await selectManagerAndCustomer(page);
    await page.goto('/menu');
    const overflow = await page.evaluate(() => document.documentElement.scrollWidth - document.documentElement.clientWidth);
    expect(overflow).toBeLessThanOrEqual(1);
    await ctx.close();
  });
});
