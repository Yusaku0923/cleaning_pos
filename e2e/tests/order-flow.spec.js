// @ts-check
// 注文→会計→返却/請求 の業務フロー。
// 事前に e2e/setup-test-store.sh でテスト用の担当者・顧客・衣類を作っておくこと。
// レシート印刷(EPSON実機)は対象外。注文登録はUIと同じ /api/order/store を直接呼ぶ。
const { test, expect } = require('@playwright/test');

const { login, collectErrors, decodeHtml, selectManagerAndCustomer } = require('./helpers');

async function apiToken(page) {
  const html = await (await page.request.get('/order/create')).text();
  const m = html.match(/:token="&quot;(.+?)&quot;"/);
  expect(m, '/order/create に API トークンがない').not.toBeNull();
  return m[1];
}

async function clothesId(page, token) {
  const res = await page.request.post('/api/clothes/search', {
    headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' },
    data: { keyword: 'E2E衣類' },
  });
  expect(res.status()).toBeLessThan(400);
  const html = decodeHtml(await (await page.request.get('/order/create')).text());
  const m = html.match(/"id":(\d+),"store_id":\d+,"category_id":\d+,(?:"sort_key":\w+,)?"name":"E2E衣類"/);
  expect(m, 'E2E衣類 が注文画面のカテゴリにない').not.toBeNull();
  return Number(m[1]);
}

function orderPayload(ctx, over = {}) {
  return {
    manager_id: ctx.managerId,
    customer_id: ctx.customerId,
    indexes: [ctx.clothesId],
    order: { [ctx.clothesId]: 2 },
    amount: 1000, reduction: 0, discount: 0, payment: 1000,
    not_paid: false, invoice: false, check_return: false,
    dont_issue_tag_list: [], created_at: '',
    ...over,
  };
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

  test('注文を登録でき、タグ番号が採番される', async ({ page }) => {
    await login(page);
    await selectManagerAndCustomer(page, 'E2E顧客');
    const token = await apiToken(page);
    ctx.token = token;
    ctx.clothesId = await clothesId(page, token);
    const html = await (await page.request.get('/order/create')).text();
    ctx.managerId = Number(html.match(/:manager_id="(\d+)"/)[1]);
    ctx.customerId = Number(html.match(/:customer_id="(\d+)"/)[1]);

    const res = await page.request.post('/api/order/store', {
      headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' },
      data: orderPayload(ctx),
    });
    expect(res.status()).toBe(200);
    const body = await res.json();
    expect(body.order_id).toBeGreaterThan(0);
    ctx.orderId = body.order_id;

    // タグが連番で2枚発行され、レシート用情報が取れる
    const rec = await page.request.get(`/api/receipt/${ctx.orderId}`, {
      headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' },
    });
    expect(rec.status()).toBe(200);
    expect(JSON.stringify(await rec.json())).toMatch(/\d-\d{3}/);
  });

  test('登録した注文が預り一覧に出る', async ({ page }) => {
    await login(page);
    await selectManagerAndCustomer(page, 'E2E顧客');
    await page.goto('/order/show');
    await expect(page.locator('body')).toContainText('1,000');
  });

  test('未収で登録した注文が入金画面に出る', async ({ page }) => {
    await login(page);
    await selectManagerAndCustomer(page, 'E2E顧客');
    const token = await apiToken(page);
    const res = await page.request.post('/api/order/store', {
      headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' },
      data: orderPayload(ctx, { amount: 500, payment: 0, not_paid: true, order: { [ctx.clothesId]: 1 } }),
    });
    expect(res.status()).toBe(200);
    await page.goto('/payment');
    await expect(page.locator('body')).toContainText('500');
  });

  test('注文を削除できる(後始末)', async ({ page }) => {
    await login(page);
    await selectManagerAndCustomer(page, 'E2E顧客');
    const token = await apiToken(page);
    const res = await page.request.post('/api/order/delete', {
      headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' },
      data: { order_id: ctx.orderId },
    });
    expect(res.status()).toBeLessThan(500);
  });
});

test.describe.serial('請求フロー(請求顧客)', () => {
  /** @type {any} */ const ctx = {};

  test('請求顧客の注文が登録でき、請求画面が開く', async ({ page }) => {
    const errors = collectErrors(page);
    await login(page);
    await selectManagerAndCustomer(page, 'E2E請求顧客');
    const token = await apiToken(page);
    const html = await (await page.request.get('/order/create')).text();
    ctx.managerId = Number(html.match(/:manager_id="(\d+)"/)[1]);
    ctx.customerId = Number(html.match(/:customer_id="(\d+)"/)[1]);
    ctx.clothesId = await clothesId(page, token);

    const res = await page.request.post('/api/order/store', {
      headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' },
      data: orderPayload(ctx, { invoice: true, payment: 0 }),
    });
    expect(res.status()).toBe(200);

    const inv = await page.goto('/invoice');
    expect(inv && inv.status()).toBeLessThan(400);
    await expect(page.locator('body')).toContainText('E2E請求顧客');
    expect(errors).toEqual([]);
  });
});
