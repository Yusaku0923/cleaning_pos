// @ts-check
// 請求書発行 (一覧・検索・選択・印刷 = PDF・繰越)、入金確認、日報 (画面・日付検索・PDF)。
// 「E2E請求顧客」(締め日 99・入金確認なし) の請求注文を 1 件作って使い、最後に消す (請求は globalSetup のリセットで消える)。
// 印刷: legacy は GET /invoice/generate?ids= (GET で issued_at を入れる)、v2 は POST /invoice/issue のあと GET /invoice/pdf?ids=。
const { test, expect } = require('@playwright/test');

const { TARGET, login, collectErrors, selectManagerAndCustomer } = require('./helpers');
const { apiHeaders, clothesId, storeOrder, deleteOrder, readIds } = require('./orderApi');

const V2 = TARGET === 'v2';

/**
 * 画面を開く。v2 は担当者・顧客を URL (?m=&c=) で持ち、画面の部品 (印刷・日付検索・戻る) は location.search から引き継ぐので、
 * 画面のリンクと同じく m / c 付きの URL で開く (helpers の carryPosContext は通信だけ書き換え、location は変えない)。
 */
async function open(page, path) {
  const search = V2 ? new URL(page.url()).search : '';
  return page.goto(path + search);
}
const PDF_URL = V2 ? /\/invoice\/pdf\?/ : /\/invoice\/generate\?/;

/** クリックで新しいタブに開く PDF の応答 (headless はダウンロード扱いにすることがあるので、タブではなく応答を見る) */
async function clickAndWaitPdf(page, locator, urlPattern) {
  const response = page.context().waitForEvent('response', { predicate: (r) => urlPattern.test(r.url()), timeout: 30000 });
  await locator.click();
  const res = await response;
  expect(res.status(), `${res.url()} が開けない`).toBe(200);
  expect(res.headers()['content-type']).toContain('application/pdf');
  const body = await res.body().catch(() => null); // ダウンロード扱いのときは本文を読めないことがある
  if (body) expect(body.subarray(0, 4).toString()).toBe('%PDF');
  return res;
}

test.describe.serial('請求書発行・入金確認・日報', () => {
  /** @type {any} */ const ctx = {};

  test('請求顧客の請求注文を作る', async ({ page }) => {
    await login(page);
    await selectManagerAndCustomer(page, 'E2E請求顧客');
    const headers = await apiHeaders(page);
    await readIds(page, ctx);
    ctx.clothesId = await clothesId(page, headers);
    ctx.orderId = await storeOrder(page, headers, ctx, { qty: 1, invoice: true, payment: 0 });
  });

  test('請求書発行: 今月の請求が並び、選ぶと 選択中 に入り、印刷で発行済みになって PDF が開く', async ({ page }) => {
    const errors = collectErrors(page);
    await login(page);
    await selectManagerAndCustomer(page, 'E2E請求顧客');
    await open(page, '/invoice');
    await expect(page.locator('.iv-title')).toHaveText('請求書発行');
    const card = page.locator('.iv-left-result-field-list-card', { hasText: 'E2E請求顧客' }).first();
    await expect(card).toContainText('500円（0円）');
    await expect(card).not.toHaveClass(/border-primary/);

    await card.click();
    await expect(card).toHaveClass(/iv-selected/);
    await expect(page.locator('.iv-right-select-field-card', { hasText: 'E2E請求顧客' })).toHaveCount(1);

    const requests = [];
    page.context().on('request', (r) => { if (/\/invoice\/(issue|pdf|generate)/.test(r.url())) requests.push(`${r.method()} ${new URL(r.url()).pathname}`); });
    await clickAndWaitPdf(page, page.locator('.iv-pdf'), PDF_URL);
    // v2: 発行済みの記録 (POST) が PDF を開く前に終わっている
    expect(requests).toEqual(V2 ? ['POST /invoice/issue', 'GET /invoice/pdf'] : ['GET /invoice/generate']);

    // 選び直すと 選択中 から外れる
    await card.click();
    await expect(page.locator('.iv-right-select-field-card')).toHaveCount(0);

    // 開き直すと発行済み (枠が青)
    await open(page, '/invoice');
    await expect(page.locator('.iv-left-result-field-list-card', { hasText: 'E2E請求顧客' }).first()).toHaveClass(/border-primary/);
    expect(errors).toEqual([]);
  });

  test('請求書発行: 検索で名前を絞ると表示項目に出て、該当の請求だけになる', async ({ page }) => {
    const errors = collectErrors(page);
    await login(page);
    await selectManagerAndCustomer(page, 'E2E請求顧客');
    await open(page, '/invoice');
    await page.locator('.iv-left-search-field-btn button').click();
    const modal = page.locator('.modal-cs');
    await expect(modal).toBeVisible();
    await modal.locator('#customer_name').fill('E2E請求');
    const searched = page.waitForResponse((r) => /\/api\/invoice\/search$/.test(r.url()));
    await modal.locator('.modal-cs-btn-send').click();
    expect((await searched).status()).toBe(200);
    await expect(modal).toHaveCount(0);
    await expect(page.locator('.iv-left-search-field')).toContainText('名前：E2E請求');
    const cards = page.locator('.iv-left-result-field-list-card');
    await expect(cards.first()).toContainText('E2E請求顧客');
    for (const text of await cards.allInnerTexts()) expect(text).toContain('E2E請求顧客');

    // 該当なし
    await page.locator('.iv-left-search-field-btn button').click();
    await modal.locator('#customer_name').fill('該当しない名前E2E');
    await modal.locator('.modal-cs-btn-send').click();
    await expect(cards).toHaveCount(0);
    expect(errors).toEqual([]);
  });

  test('請求書発行: 1 件選んで 操作 → 繰り越し すると、その請求は（繰越）表示になる', async ({ page }) => {
    const errors = collectErrors(page);
    await login(page);
    await selectManagerAndCustomer(page, 'E2E請求顧客');
    await open(page, '/invoice');
    const card = page.locator('.iv-left-result-field-list-card', { hasText: 'E2E請求顧客' }).first();
    await card.click();
    await page.locator('.iv-operate').click();
    const button = page.locator('.modal-iv-list-btn', { hasText: '選択中の請求書を繰り越し' });
    await expect(button).toBeVisible();
    // 締日は 99 (月末) で合っているので「締日に揃える」は出ない
    await expect(page.locator('.modal-iv-list-btn', { hasText: '締日に揃える' })).toHaveCount(0);
    const carried = page.waitForResponse((r) => /\/api\/invoice\/carry_over$/.test(r.url()));
    await button.click();
    expect((await carried).status()).toBe(200);
    await page.waitForLoadState('load');
    await expect(page.locator('.iv-left-result-field-list-card', { hasText: 'E2E請求顧客' }).first()).toContainText('500円（繰越）');
    expect(errors).toEqual([]);
  });

  test('入金確認: 入金未確認の請求が並び、選ぶと伝票が出る', async ({ page }) => {
    const errors = collectErrors(page);
    page.on('dialog', (d) => d.dismiss());
    await login(page);
    await selectManagerAndCustomer(page, 'E2E請求顧客');
    await open(page, '/invoice/payment_confimation');
    await expect(page.locator('body')).toContainText('入金未確認一覧');
    // 今月の請求 (繰越元) と、繰越で作られた来月の請求 (伝票 0 枚)
    const rows = page.locator('.paymentconfirm-orders-order');
    await expect(rows).toHaveCount(2);
    await expect(rows.nth(0)).toContainText('1枚');
    await expect(rows.nth(0)).toContainText('500円');
    await expect(rows.nth(1)).toContainText('0枚');
    await rows.nth(0).click();
    await expect(rows.nth(0)).toHaveClass(/paymentconfirm-selected/);
    await expect(page.locator('.paymentconfirm-detail-clothes')).toHaveCount(1);
    await expect(page.locator('.paymentconfirm-detail-clothes').first()).toContainText(String(ctx.orderId));
    // 選び直しても前の請求の伝票は残らない (旧は足されていく。v2 で直した)
    if (V2) {
      await rows.nth(1).click();
      await expect(page.locator('.paymentconfirm-detail-clothes')).toHaveCount(0);
      await rows.nth(0).click();
      await expect(page.locator('.paymentconfirm-detail-clothes')).toHaveCount(1);
    }
    expect(errors).toEqual([]);
  });

  test('入金確認: 入金確認 → お会計 すると請求が確認済みになり一覧から消える', async ({ page }) => {
    // 旧は請求書 ID を注文 ID として /api/payment に送る不具合があり、旧の基準環境の別の注文を書き換えるので流さない
    test.skip(!V2, '旧の不具合 (別の注文を入金済みにする) のため legacy では実行しない');
    const errors = collectErrors(page);
    await login(page);
    await selectManagerAndCustomer(page, 'E2E請求顧客');
    await open(page, '/invoice/payment_confimation');
    const rows = page.locator('.paymentconfirm-orders-order');
    await rows.nth(0).click();
    await page.locator('.paymentconfirm-operation-btn', { hasText: '入金確認' }).click();
    await page.locator('.modal-ac-keypad-upper-key', { hasText: /^5$/ }).click();
    await page.locator('.modal-ac-keypad-lower-key', { hasText: /^00$/ }).click();
    const confirmed = page.waitForResponse((r) => /\/api\/invoice\/payment_confirmation$/.test(r.url()));
    await page.locator('.modal-ac-send').click();
    expect((await confirmed).status()).toBe(200);
    await page.waitForLoadState('load');
    await expect(rows).toHaveCount(1);
    await expect(rows.first()).toContainText('0枚');
    expect(errors).toEqual([]);
  });

  test('日報: 本日の受付が並び、開くと品物が出て、日付で検索でき、印刷で PDF が開く', async ({ page }) => {
    const errors = collectErrors(page);
    await login(page);
    await selectManagerAndCustomer(page);
    await open(page, '/daily_report');
    await expect(page.locator('.dr-date')).toContainText('本日の日報');
    const card = page.locator('.dr-body-cards', { hasText: 'E2E請求顧客' }).filter({ hasText: String(ctx.orderId) }).first();
    await expect(card).toContainText('500円');
    await card.click();
    const detail = page.locator(`#block-${ctx.orderId} + label + .dr-body-ac`);
    await expect(detail).toContainText('E2E衣類');

    await clickAndWaitPdf(page, page.locator('.dr-pdf'), V2 ? /\/daily-report\/\d{4}-\d{2}-\d{2}\/pdf/ : /\/daily_report\/generate\/\d{4}-\d{2}-\d{2}/);

    // 日付で検索 (過去の日)
    await page.locator('.dr-search-form').fill('2023-01-05');
    await page.locator('.dr-search-btn').click();
    await page.waitForURL(/date=2023-01-05/);
    await expect(page.locator('.dr-date')).toContainText('2023年01月05日の日報');
    // 担当者は選んだまま (v2 は ?m= を引き継ぐ)
    await expect(page.locator('body')).toContainText('E2E担当');
    expect(errors).toEqual([]);
  });

  test('作った請求注文を削除する (後始末)', async ({ page }) => {
    await login(page);
    await selectManagerAndCustomer(page, 'E2E請求顧客');
    const headers = await apiHeaders(page);
    expect(ctx.orderId, '削除対象の注文IDがない(前のテストが失敗)').toBeGreaterThan(0);
    await deleteOrder(page, headers, ctx, ctx.orderId, { invoiceOrder: true });
  });
});
