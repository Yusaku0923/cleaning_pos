// @ts-check
// 新旧の画面比較。基準画像は常に旧で作る(snapshots/legacy/)。
//   基準の作成: E2E_TARGET=legacy E2E_BASE_URL=http://localhost:8081 npx playwright test tests/parity --project=parity --no-deps --update-snapshots
//   v2 の確認 : E2E_TARGET=v2     E2E_BASE_URL=<v2 の URL>          npx playwright test tests/parity --project=parity --no-deps
//   (project「parity」は全体実行では chromium のテストのあとに流れる。単独なら --project=parity --no-deps)
// 個人情報の混入を避けるため、担当者「E2E担当」・顧客「E2E顧客」「E2E請求顧客」(setup-test-store.sh)の画面だけを撮る。
// 日時・タグ番号・注文件数など実行ごとに変わる値は mask で隠す。
const { test, expect } = require('@playwright/test');
const { login, collectErrors, selectManagerAndCustomer } = require('../helpers');

test.use({ viewport: { width: 1280, height: 800 } });

const SHOT = { maxDiffPixelRatio: 0.02, animations: 'disabled', caret: 'hide', fullPage: true };

/** 実行ごとに変わりうる表示(日付・時刻・タグ番号)を隠す対象 */
function dynamicMasks(page) {
  return [
    page.locator('input[type="date"], input[type="datetime-local"], input[type="month"]'),
    page.locator('.tag-number, .order-id, .created_at, time'),
    page.locator('#RealtimeClockArea'), // 現在時刻
    page.locator('span.fs-22.fw-bold'), // 注文作成の発行予定タグ番号(採番カウンタは実行ごとに進む)
    page.locator('.category-bar'), // カテゴリは本番マスタ(取引先名を含む)なので隠す
  ];
}

async function settle(page) {
  await page.waitForLoadState('networkidle').catch(() => {});
  await page.evaluate(() => document.fonts && document.fonts.ready);
  await page.waitForTimeout(500);
}

/** 他のテストの実行回数で増える値(来店日・回数・金額の累計)。履歴系の画面でだけ隠す */
function accumulatedMasks(page) {
  return [
    page.getByText(/\d{4}\/\d{2}\/\d{2}/),
    page.getByText(/\d{4}-\d{2}/), // 請求の対象月(実行月)
    page.getByText(/\d{2}\/\d{2}\s*[〜～]\s*\d{2}\/\d{2}/), // 請求対象期間
    page.getByText(/^\s*\d+回\s*$/),
    page.getByText(/[\d,]+円/),
    page.getByText(/^\s*\d{4,6}\s*$/), // 伝票No.(全体で連番)
  ];
}

async function shot(page, name, { accumulated = false, extraMasks = [] } = {}) {
  await settle(page);
  const mask = [...dynamicMasks(page), ...(accumulated ? accumulatedMasks(page) : []), ...extraMasks];
  await expect(page).toHaveScreenshot(`${name}.png`, { ...SHOT, mask });
}

test.describe('画面比較(旧=基準)', () => {
  test('01 ログイン', async ({ page }) => {
    await page.goto('/login');
    await shot(page, 'login');
  });

  test('02 ホーム', async ({ page }) => {
    await login(page);
    await page.goto('/');
    await shot(page, 'home');
  });

  test('03 メニュー', async ({ page }) => {
    await login(page);
    await page.goto('/menu');
    await shot(page, 'menu');
  });

  test('04 注文作成', async ({ page }) => {
    const errors = collectErrors(page);
    await login(page);
    await selectManagerAndCustomer(page, 'E2E顧客');
    await page.goto('/order/create');
    await page.locator('text=E2Eカテゴリ').first().click();
    await page.locator('text=E2E衣類').first().click();
    await shot(page, 'order-create');
    expect(errors).toEqual([]);
  });

  test('05 会計モーダル', async ({ page }) => {
    await login(page);
    await selectManagerAndCustomer(page, 'E2E顧客');
    await page.goto('/order/create');
    await page.locator('text=E2Eカテゴリ').first().click();
    await page.locator('text=E2E衣類').first().click();
    await page.locator('.to-bill').click();
    await page.locator('text=預り金入力').click();
    await expect(page.locator('.modal-ac-board')).toBeVisible();
    await shot(page, 'accounting-modal');
  });

  test('06 預り一覧', async ({ page }) => {
    await login(page);
    await selectManagerAndCustomer(page, 'E2E顧客');
    await page.goto('/order/show');
    await shot(page, 'order-show', { accumulated: true });
  });

  test('07 お渡し', async ({ page }) => {
    await login(page);
    await selectManagerAndCustomer(page, 'E2E顧客');
    await page.goto('/return');
    await shot(page, 'return', { accumulated: true });
  });

  test('08 入金', async ({ page }) => {
    await login(page);
    await selectManagerAndCustomer(page, 'E2E顧客');
    await page.goto('/payment');
    await shot(page, 'payment', { accumulated: true });
  });

  test('09 請求', async ({ page }) => {
    await login(page);
    await selectManagerAndCustomer(page, 'E2E請求顧客');
    await page.goto('/invoice');
    await shot(page, 'invoice', { accumulated: true });
  });

  test('10 入金確認', async ({ page }) => {
    await login(page);
    await selectManagerAndCustomer(page, 'E2E請求顧客');
    await page.goto('/invoice/payment_confimation');
    await shot(page, 'invoice-payment-confirmation', { accumulated: true });
  });

  test('11 顧客検索', async ({ page }) => {
    await login(page);
    await selectManagerAndCustomer(page);
    await page.goto('/customer/search');
    await shot(page, 'customer-search', { accumulated: true });
  });

  test('12 納品書', async ({ page }) => {
    await login(page);
    await page.goto('/delivery');
    // 取引先の数はデータ次第 (旧は全店舗の取引先を出し、v2 は自店舗だけ: allowed-diffs.md「納品書の取引先」)。
    // 1 件目のカードだけを残して配置を比べる
    await page.locator('#app .card').first().waitFor();
    await page.evaluate(() => document.querySelectorAll('#app .card').forEach((c, i) => { if (i > 0) c.remove(); }));
    // 納品書の取引先名は本番データなので隠し、ボタン類の配置だけを比べる
    await shot(page, 'delivery', { extraMasks: [page.locator('.card-title')] });
  });
});
