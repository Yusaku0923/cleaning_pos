// @ts-check
// 注文→会計→返却/請求 の業務フロー。
// 事前に e2e/setup-test-store.sh (legacy) / php artisan e2e:seed (v2) でテスト用の担当者・顧客・衣類を作っておくこと
// (globalSetup が実行する)。
// レシート印刷(EPSON実機)は対象外 (プリンタへの接続失敗は helpers の IGNORED_ERRORS で無視)。
// 注文登録は UI と同じ API を直接呼ぶ: legacy は /api/order/store (Bearer トークン)、
// v2 は POST /api/orders (Sanctum のセッション Cookie + X-XSRF-TOKEN、合計はサーバーで計算)。
const { test, expect } = require('@playwright/test');

const { TARGET, login, collectErrors, decodeHtml, selectManagerAndCustomer } = require('./helpers');

const V2 = TARGET === 'v2';

/** v2 は担当者・顧客を URL (?m=&c=) で持つ。page.request は page.route を通らないので、今の m/c を付ける */
function orderCreateUrl(page) {
  return V2 ? `/order/create${new URL(page.url()).search}` : '/order/create';
}

/** API 呼び出し用のヘッダー。legacy: 画面に埋め込まれたトークン、v2: XSRF-TOKEN Cookie */
async function apiHeaders(page) {
  if (V2) {
    await page.request.get('/login'); // XSRF-TOKEN Cookie を確実に持つ
    const cookies = await page.context().cookies();
    const xsrf = cookies.find((c) => c.name === 'XSRF-TOKEN');
    expect(xsrf, 'XSRF-TOKEN Cookie がない').toBeTruthy();
    const origin = new URL(page.url() === 'about:blank' ? test.info().project.use.baseURL : page.url()).origin;
    return { 'X-XSRF-TOKEN': decodeURIComponent(xsrf.value), Accept: 'application/json', Referer: origin + '/', Origin: origin };
  }
  const html = await (await page.request.get(orderCreateUrl(page))).text();
  const m = html.match(/:token="&quot;(.+?)&quot;"/);
  expect(m, '/order/create に API トークンがない').not.toBeNull();
  return { Authorization: `Bearer ${m[1]}`, Accept: 'application/json' };
}

async function clothesId(page, headers) {
  const res = await page.request.post('/api/clothes/search', { headers, data: { keyword: 'E2E衣類', name: 'E2E衣類' } });
  expect(res.status()).toBeLessThan(400);
  const html = decodeHtml(await (await page.request.get(orderCreateUrl(page))).text());
  const m = html.match(/"id":(\d+),"store_id":\d+,"category_id":\d+,(?:"sort_key":\w+,)?"name":"E2E衣類"/);
  expect(m, 'E2E衣類 が注文画面のカテゴリにない').not.toBeNull();
  return Number(m[1]);
}

/** 注文を登録して order_id を返す。qty 個の E2E衣類 (500 円) */
async function storeOrder(page, headers, ctx, { qty = 2, payment = 1000, notPaid = false, invoice = false } = {}) {
  const res = V2
    ? await page.request.post('/api/orders', {
      headers,
      data: {
        manager_id: ctx.managerId, customer_id: ctx.customerId,
        items: [{ clothes_id: ctx.clothesId, qty }],
        discount_ratio: null, discount_amount: 0, deposit: payment,
        is_invoice: invoice, not_paid: notPaid || invoice, check_return: false, created_at: null,
      },
    })
    : await page.request.post('/api/order/store', {
      headers,
      data: {
        manager_id: ctx.managerId, customer_id: ctx.customerId,
        indexes: [ctx.clothesId], order: { [ctx.clothesId]: qty },
        amount: 500 * qty, reduction: 0, discount: 0, payment,
        not_paid: notPaid, invoice, check_return: false, dont_issue_tag_list: [], created_at: '',
      },
    });
  expect(res.status(), await res.text()).toBe(V2 ? 201 : 200);
  const body = await res.json();
  expect(body.order_id).toBeGreaterThan(0);
  if (V2) {
    expect(body.total).toBe(500 * qty);
    expect(body.tags.length).toBe(qty);
  }
  return body.order_id;
}

/** 作った注文を削除し、成功(200 かつ ok)を確認する。E2E データを毎回同じ状態に戻すため。 */
async function deleteOrder(page, headers, ctx, orderId, { invoiceOrder = false } = {}) {
  const res = await page.request.post('/api/order/delete', {
    headers,
    data: { order_id: orderId, customer_id: ctx.customerId },
  });
  // 旧のバグ: 請求付き注文の削除は、注文を消したあと Invoice の削除フック(未定義の orders())で 500 になる。
  // 注文は消えているので legacy ではこの 500 を既知として許容する(残る請求は globalSetup のリセットで消す)。
  if (invoiceOrder && !V2) {
    expect([200, 500], `注文 ${orderId} の削除`).toContain(res.status());
    return;
  }
  expect(res.status(), `注文 ${orderId} の削除`).toBe(200);
  expect((await res.json()).ok).toBe(true);
}

async function readIds(page, ctx) {
  const html = await (await page.request.get(orderCreateUrl(page))).text();
  ctx.managerId = Number(html.match(/:manager_id="(\d+)"/)[1]);
  ctx.customerId = Number(html.match(/:customer_id="(\d+)"/)[1]);
}

test.describe.serial('注文フロー(通常顧客)', () => {
  /** @type {any} */ const ctx = {};

  test('担当者・顧客を選んで注文画面が開き、衣類を追加すると金額が出る', async ({ page }) => {
    const errors = collectErrors(page);
    await login(page);
    await selectManagerAndCustomer(page, 'E2E顧客');
    await page.goto('/order/create');
    await page.locator('text=E2Eカテゴリ').first().click();
    await expect(page.locator('text=E2E衣類').first()).toBeVisible();
    await page.locator('text=E2E衣類').first().click();
    await expect(page.locator('body')).toContainText('500');
    expect(errors).toEqual([]);
  });

  test('画面で 会計 → 預り金入力 → お会計 すると注文が登録され、レシート用データが読まれる', async ({ page }) => {
    const errors = collectErrors(page);
    page.on('dialog', (d) => d.dismiss()); // プリンタに接続できない alert
    await login(page);
    await selectManagerAndCustomer(page, 'E2E顧客');
    await page.goto('/order/create');
    await page.locator('text=E2Eカテゴリ').first().click();
    await page.locator('text=E2E衣類').first().click();
    await page.locator('text=E2E衣類').first().click();
    await expect(page.locator('.order-amount').first()).toContainText('1,000 円');
    await page.locator('.to-bill').click();
    await expect(page.locator('.bill-total')).toContainText('1,000 円');
    // 内消費税 10%: 旧は切り捨て trunc(1000 - 1000/1.1) = 90、v2 は請求書と同じ四捨五入 1000 - round(909.09) = 91 (allowed-diffs)
    await expect(page.locator('body')).toContainText(V2 ? '(91 円)' : '(90 円)');
    await page.locator('text=預り金入力').click();
    await expect(page.locator('.modal-ac-board')).toBeVisible();
    await page.locator('.modal-ac-keypad-lower-key', { hasText: /^1$/ }).click();
    await page.locator('.modal-ac-keypad-lower-key', { hasText: /^000$/ }).click();
    await page.locator('.modal-ac-keypad-lower-key', { hasText: /^0$/ }).click(); // 10,000
    const stored = page.waitForResponse((r) => /\/api\/(orders|order\/store)$/.test(r.url()) && r.request().method() === 'POST');
    const receipt = page.waitForResponse((r) => /\/api\/receipt\/\d+$/.test(r.url()));
    await page.locator('.modal-ac-send').click();
    expect((await stored).status()).toBeLessThan(300);
    const rec = await receipt;
    expect(rec.status()).toBe(200);
    const body = await rec.json();
    expect(body.amount).toBe(1000);
    expect(body.payment).toBe(10000);
    expect(body.order_list[0].count).toBe(2);
    await expect(page.locator('.modal-ch-change')).toContainText('9,000 円');
    ctx.uiOrderId = Number(rec.url().match(/(\d+)$/)[1]);
    // 「ホームに戻る」で担当者・顧客を引き継いだホームに戻る
    await page.locator('.modal-overlay').click({ position: { x: 5, y: 5 } });
    await page.locator('a', { hasText: 'ホームに戻る' }).click();
    await expect(page.locator('body')).toContainText('E2E顧客');
    expect(errors).toEqual([]);
  });

  test('注文を登録でき、タグ番号が採番される', async ({ page }) => {
    await login(page);
    await selectManagerAndCustomer(page, 'E2E顧客');
    const headers = await apiHeaders(page);
    ctx.headers = headers;
    ctx.clothesId = await clothesId(page, headers);
    await readIds(page, ctx);

    ctx.orderId = await storeOrder(page, headers, ctx);

    // タグが連番で2枚発行され、レシート用情報が取れる
    const rec = await page.request.get(`/api/receipt/${ctx.orderId}`, { headers });
    expect(rec.status()).toBe(200);
    expect(JSON.stringify(await rec.json())).toMatch(/\d-\d{3}/);
  });

  test('登録した注文が預り一覧に出る', async ({ page }) => {
    test.fixme(V2, 'Task 8: /order/show は未移植 (501)');
    await login(page);
    await selectManagerAndCustomer(page, 'E2E顧客');
    await page.goto('/order/show');
    await expect(page.locator('body')).toContainText('1,000');
  });

  test('未収で登録した注文が入金画面に出る', async ({ page }) => {
    await login(page);
    await selectManagerAndCustomer(page, 'E2E顧客');
    const headers = await apiHeaders(page);
    ctx.clothesId = ctx.clothesId || await clothesId(page, headers);
    await readIds(page, ctx);
    ctx.unpaidOrderId = await storeOrder(page, headers, ctx, { qty: 1, payment: 0, notPaid: true });
    test.fixme(V2, 'Task 8: /payment は未移植 (501)');
    await page.goto('/payment');
    await expect(page.locator('body')).toContainText('500');
  });

  test('作った注文をすべて削除できる(後始末)', async ({ page }) => {
    test.fixme(V2, 'Task 8: 注文の削除は未移植 (globalSetup の e2e:seed が毎回消す)');
    await login(page);
    await selectManagerAndCustomer(page, 'E2E顧客');
    const headers = await apiHeaders(page);
    for (const id of [ctx.orderId, ctx.unpaidOrderId, ctx.uiOrderId]) {
      expect(id, '削除対象の注文IDがない(前のテストが失敗)').toBeGreaterThan(0);
      await deleteOrder(page, headers, ctx, id);
    }
    // 預り一覧・入金画面に残っていない
    await page.goto('/payment');
    await expect(page.locator('body')).not.toContainText('500 円');
  });
});

test.describe.serial('請求フロー(請求顧客)', () => {
  /** @type {any} */ const ctx = {};

  test('画面で 伝票発行 すると請求書払いで登録され、レシート用データが読まれる', async ({ page }) => {
    const errors = collectErrors(page);
    page.on('dialog', (d) => d.dismiss());
    await login(page);
    await selectManagerAndCustomer(page, 'E2E請求顧客');
    await page.goto('/order/create');
    await page.locator('text=E2Eカテゴリ').first().click();
    await page.locator('text=E2E衣類').first().click();
    await page.locator('.to-bill').click();
    await expect(page.locator('#is-invoice')).toBeChecked();
    const receipt = page.waitForResponse((r) => /\/api\/receipt\/\d+$/.test(r.url()));
    await page.locator('text=伝票発行').click();
    const body = await (await receipt).json();
    expect(body.is_invoice).toBeTruthy();
    expect(body.amount).toBe(500);
    await expect(page.locator('body')).toContainText('請求書払い');
    if (V2) {
      // 「レシート発行」ボタンも同じ注文を読む (旧は注文 ID を持っておらず /api/receipt/null を読んで失敗していた)
      const again = page.waitForResponse((r) => /\/api\/receipt\/\d+$/.test(r.url()));
      await page.locator('text=レシート発行').click();
      expect((await again).status()).toBe(200);
    }
    expect(errors).toEqual([]);
  });

  test('請求顧客の注文が登録でき、請求画面が開く', async ({ page }) => {
    const errors = collectErrors(page);
    await login(page);
    await selectManagerAndCustomer(page, 'E2E請求顧客');
    const headers = await apiHeaders(page);
    await readIds(page, ctx);
    ctx.clothesId = await clothesId(page, headers);

    ctx.orderId = await storeOrder(page, headers, ctx, { invoice: true, payment: 0 });
    ctx.headers = headers;

    test.fixme(V2, 'Task 9: /invoice は未移植 (501)');
    const inv = await page.goto('/invoice');
    expect(inv && inv.status()).toBeLessThan(400);
    await expect(page.locator('body')).toContainText('E2E請求顧客');
    expect(errors).toEqual([]);
  });

  test('作った請求注文を削除できる(後始末。請求も消える)', async ({ page }) => {
    test.fixme(V2, 'Task 8: 注文の削除は未移植 (globalSetup の e2e:seed が毎回消す)');
    await login(page);
    await selectManagerAndCustomer(page, 'E2E請求顧客');
    const headers = await apiHeaders(page);
    expect(ctx.orderId, '削除対象の注文IDがない(前のテストが失敗)').toBeGreaterThan(0);
    await deleteOrder(page, headers, ctx, ctx.orderId, { invoiceOrder: true });
    // 注文が消えている(請求の削除は v2 では 200 で消える。legacy は旧バグで請求が残るためリセットに任せる)
    const gone = await page.request.get(`/api/receipt/${ctx.orderId}`, { headers });
    expect(gone.status(), '削除した注文が残っている').toBe(404);
  });
});
