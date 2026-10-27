// @ts-check
// 注文の登録・削除・ID の読み取り (UI と同じ API を直接呼ぶ)。order-flow / invoice の spec で使う。
// legacy は /api/order/store (Bearer トークン)、v2 は POST /api/orders (Sanctum のセッション Cookie + X-XSRF-TOKEN)。
const { test, expect } = require('@playwright/test');

const { TARGET, decodeHtml } = require('./helpers');

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
async function storeOrder(page, headers, ctx, { qty = 2, payment = 1000, notPaid = false, invoice = false, checkReturn = false } = {}) {
  const res = V2
    ? await page.request.post('/api/orders', {
      headers,
      data: {
        manager_id: ctx.managerId, customer_id: ctx.customerId,
        items: [{ clothes_id: ctx.clothesId, qty }],
        discount_ratio: null, discount_amount: 0, deposit: payment,
        is_invoice: invoice, not_paid: notPaid || invoice, check_return: checkReturn, created_at: null,
      },
    })
    : await page.request.post('/api/order/store', {
      headers,
      data: {
        manager_id: ctx.managerId, customer_id: ctx.customerId,
        indexes: [ctx.clothesId], order: { [ctx.clothesId]: qty },
        amount: 500 * qty, reduction: 0, discount: 0, payment,
        not_paid: notPaid, invoice, check_return: checkReturn, dont_issue_tag_list: [], created_at: '',
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

module.exports = { orderCreateUrl, apiHeaders, clothesId, storeOrder, deleteOrder, readIds };
