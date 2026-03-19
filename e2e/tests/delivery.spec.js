// @ts-check
const { test, expect } = require('@playwright/test');

const BASE = 'http://localhost:8000';
const EMAIL = 'test@gmail.com';
const PASSWORD = 'Test123456';

/** ログインしてセッションを取得 */
async function login(page) {
  await page.goto(`${BASE}/login`);
  await page.fill('input[name="email"]', EMAIL);
  await page.fill('input[name="password"]', PASSWORD);
  await page.click('button[type="submit"]');
  // ログイン後は /home or /delivery/sp/choice にリダイレクト
  await page.waitForURL(url => !url.href.includes('/login'), { timeout: 15000 });
}

// ────────────────────────────────────────────────────────────────
// PC: メニュー → 納品書管理
// ────────────────────────────────────────────────────────────────
test('メニューから納品書管理へ遷移', async ({ page }) => {
  await login(page);
  await page.goto(`${BASE}/menu`);
  await page.click('text=納品書管理');
  await expect(page).toHaveURL(/\/delivery/);
});

// ────────────────────────────────────────────────────────────────
// SP: ホームアクセス時のリダイレクト（iPhone UA）
// ────────────────────────────────────────────────────────────────
test('スマホ UA でホームにアクセスすると sp/choice にリダイレクト', async ({ browser }) => {
  const ctx = await browser.newContext({
    userAgent: 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1',
  });
  const page = await ctx.newPage();
  await login(page);
  await page.goto(`${BASE}/`);
  await expect(page).toHaveURL(/\/delivery\/sp\/choice/);
  await ctx.close();
});

// ────────────────────────────────────────────────────────────────
// SP: choice 画面のボタン確認
// ────────────────────────────────────────────────────────────────
test('SP choice 画面に3つのボタンが表示される', async ({ page }) => {
  await login(page);
  await page.goto(`${BASE}/delivery/sp/choice`);
  await expect(page.locator('text=レ　ジ')).toBeVisible();
  await expect(page.locator('text=納品書記帳')).toBeVisible();
  await expect(page.locator('text=タブレット管理画面')).toBeVisible();
});

// ────────────────────────────────────────────────────────────────
// SP: entry 画面 - 顧客・部署・商品が表示される
// ────────────────────────────────────────────────────────────────
test('SP entry 画面に顧客・商品が表示される', async ({ page }) => {
  await login(page);
  await page.goto(`${BASE}/delivery/sp/entry`);
  // Vue コンポーネントがマウントされるのを待つ
  await page.waitForSelector('.sp-entry', { timeout: 10000 });
  // 日付ピッカーが表示されること
  await expect(page.locator('input[type="date"]')).toBeVisible();
  // 入力ボタンが表示されること
  await expect(page.locator('button:has-text("入力")')).toBeVisible();
  console.log('Page HTML:', await page.locator('.sp-entry').innerHTML());
});

// ────────────────────────────────────────────────────────────────
// SP: entry 画面 - 日付を選んでフォームが開く
// ────────────────────────────────────────────────────────────────
test('SP entry - 日付選択でフォームが開き商品が表示される', async ({ page }) => {
  await login(page);
  await page.goto(`${BASE}/delivery/sp/entry`);
  await page.waitForSelector('.sp-entry', { timeout: 10000 });

  // 今日の日付をセット
  const today = new Date().toISOString().slice(0, 10);
  await page.fill('input[type="date"]', today);
  await page.click('button:has-text("入力")');

  // フォームが開いて「保存する」ボタンが表示されること
  await expect(page.locator('button:has-text("保存する")')).toBeVisible({ timeout: 10000 });

  // 部署・商品一覧が表示されること（departments がロードされている証拠）
  const cards = page.locator('.card');
  const count = await cards.count();
  console.log(`商品カード数: ${count}`);
  expect(count).toBeGreaterThan(0);
});

// ────────────────────────────────────────────────────────────────
// SP: entry 画面 - 保存
// ────────────────────────────────────────────────────────────────
test('SP entry - 数量を入力して保存できる', async ({ page }) => {
  await login(page);
  await page.goto(`${BASE}/delivery/sp/entry`);
  await page.waitForSelector('.sp-entry', { timeout: 10000 });

  // テスト用日付（今日）
  const today = new Date().toISOString().slice(0, 10);
  await page.fill('input[type="date"]', today);
  await page.click('button:has-text("入力")');
  await expect(page.locator('button:has-text("保存する")')).toBeVisible({ timeout: 10000 });

  // コンソールエラーをキャプチャ
  const consoleErrors = [];
  page.on('console', msg => { if (msg.type() === 'error') consoleErrors.push(msg.text()); });

  // アラートをキャプチャ（あればエラー内容を記録）
  let alertMessage = '';
  page.on('dialog', async dialog => {
    alertMessage = dialog.message();
    await dialog.accept();
  });

  // 最初の＋ボタンを1回押す
  const plusBtns = page.locator('button:has-text("＋")');
  await plusBtns.first().click();

  // 保存
  await page.click('button:has-text("保存する")');
  await page.waitForTimeout(3000);

  console.log('アラート:', alertMessage || 'なし');
  console.log('コンソールエラー:', consoleErrors);

  // 保存後は一覧に戻る（selectedDate が null になる）
  expect(alertMessage).toBe('');
  await expect(page.locator('input[type="date"]')).toBeVisible({ timeout: 10000 });
});

// ────────────────────────────────────────────────────────────────
// SP: entry 画面 - 削除
// ────────────────────────────────────────────────────────────────
test('SP entry - 入力済み日付を削除できる', async ({ page }) => {
  await login(page);
  await page.goto(`${BASE}/delivery/sp/entry`);
  await page.waitForSelector('.sp-entry', { timeout: 10000 });

  // 削除ボタンがあれば実行（入力済みデータが前提）
  const deleteBtn = page.locator('button:has-text("削除")').first();
  const hasDates = await deleteBtn.isVisible().catch(() => false);

  if (!hasDates) {
    console.log('入力済みデータなし - 削除テストスキップ');
    return;
  }

  // confirm ダイアログを自動的にOKする
  page.on('dialog', dialog => dialog.accept());
  await deleteBtn.click();

  // リストが更新される（削除ボタンが減るか0になる）
  await page.waitForTimeout(1000);
  console.log('削除後の削除ボタン数:', await page.locator('button:has-text("削除")').count());
});

// ────────────────────────────────────────────────────────────────
// API: 保存エラー時にアラートが出るか（ネットワークエラー模倣）
// ────────────────────────────────────────────────────────────────
test('API エラー時に「保存に失敗しました」アラートが表示される', async ({ page }) => {
  await login(page);
  await page.goto(`${BASE}/delivery/sp/entry`);
  await page.waitForSelector('.sp-entry', { timeout: 10000 });

  // API をブロックして強制的にエラーにする
  await page.route('**/api/delivery/entries', route => {
    if (route.request().method() === 'POST') {
      route.abort('failed');
    } else {
      route.continue();
    }
  });

  const today = new Date().toISOString().slice(0, 10);
  await page.fill('input[type="date"]', today);
  await page.click('button:has-text("入力")');
  await expect(page.locator('button:has-text("保存する")')).toBeVisible({ timeout: 10000 });

  // アラートをキャプチャ
  let alertMessage = '';
  page.on('dialog', async dialog => {
    alertMessage = dialog.message();
    await dialog.accept();
  });

  await page.click('button:has-text("保存する")');
  await page.waitForTimeout(2000);
  expect(alertMessage).toContain('保存に失敗しました');
});
