// @ts-check
// 移行・UX刷新の合格ラインとなるスモークテスト。
// 全画面が 5xx/JSエラー無しで開き、Vue がマウントされることを確認する。
const { test, expect } = require('@playwright/test');

const { TARGET, EMAIL, PASSWORD, login, collectErrors, selectManagerAndCustomer } = require('./helpers');
const V2 = TARGET === 'v2';

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

  // v2 は /login/auto (URL にパスワードを載せる) を作らず、端末トークンの /login/device/{token} に置き換えた
  // (allowed-diffs.md「端末の自動ログイン」)。v2 では同じ観点 (誤りは入れない・正しければ入れて Referer を
  // 送らせない・連続した誤りを 429) を /login/device で確かめる。
  test('/login/auto は誤った資格情報だと /login に戻る', async ({ page }) => {
    if (V2) {
      // 旧の URL ではログインできない (パスワードを URL で受け付けない)
      const res = await page.goto(`/login/auto?id=${encodeURIComponent(EMAIL)}&pass=${encodeURIComponent(PASSWORD)}`);
      expect(res && res.status()).toBe(404);
      await page.goto('/menu');
      await expect(page).toHaveURL(/\/login/);
      expect((await page.goto('/login/device/' + 'x'.repeat(48)))?.status()).toBe(404);
      return;
    }
    await page.goto('/login/auto?id=nobody@example.com&pass=wrong');
    await expect(page).toHaveURL(/\/login/);
  });

  test('/login/auto は正しい資格情報でログインでき、Referer を送らせない', async ({ request }) => {
    // v2: 端末トークンは php artisan device:issue でしか作れない。E2E_DEVICE_URL (発行した URL) があるときだけ確かめる
    if (V2) {
      const deviceUrl = process.env.E2E_DEVICE_URL;
      test.skip(!deviceUrl, 'v2: E2E_DEVICE_URL (device:issue の URL) がないため省略。PHPUnit AuthTest で確認済み');
      const res = await request.get(new URL(/** @type {string} */ (deviceUrl)).pathname, { maxRedirects: 0 });
      expect(res.status()).toBe(302);
      expect(res.headers()['referrer-policy']).toBe('no-referrer');
      expect(res.headers()['location']).not.toMatch(/\/login$/);
      return;
    }
    const url = `/login/auto?id=${encodeURIComponent(EMAIL)}&pass=${encodeURIComponent(PASSWORD)}`;
    let res = await request.get(url, { maxRedirects: 0 });
    if (res.status() === 429) { // 直前の実行の制限が残っている場合は解除を待つ
      test.setTimeout(90000);
      await new Promise((r) => setTimeout(r, (Number(res.headers()['retry-after']) || 60) * 1000 + 500));
      res = await request.get(url, { maxRedirects: 0 });
    }
    expect(res.status()).toBe(302);
    expect(res.headers()['referrer-policy']).toBe('no-referrer');
    expect(res.headers()['location']).not.toMatch(/\/login$/);
  });

  test('/login/auto は連続した誤り試行を 429 で制限する', async ({ request }) => {
    const codes = [];
    for (let i = 0; i < 14; i++) {
      const url = V2 ? '/login/device/' + 'x'.repeat(48) : '/login/auto?id=x@example.com&pass=bad';
      codes.push((await request.get(url, { maxRedirects: 0 })).status());
    }
    expect(codes).toContain(429);
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

// 旧は未ログインでも開けた (顧客名が公開チャンネルで流れていた)。v2 は表示端末のログイン
// (GET /display/login/{token}) が要り、未ログインはスタッフ向けの案内 (401)。POS の /login には飛ばさない
// (allowed-diffs.md「顧客表示」)。
test('顧客表示画面: 旧は未ログインでも開ける / v2 は表示端末のログインが要る', async ({ page }) => {
  const res = await page.goto('/customer_display');
  if ((process.env.E2E_TARGET || 'legacy') === 'v2') {
    expect(res && res.status()).toBe(401);
    expect(page.url()).toContain('/customer_display');
    await expect(page.getByText('顧客表示の再ログインが必要です（スタッフ向け）')).toBeVisible();
  } else {
    expect(res && res.status()).toBeLessThan(400);
  }
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

test('APIトークンはページ表示のたびに増えず、同一セッションで再利用される', async ({ page }) => {
  await login(page);
  await selectManagerAndCustomer(page, 'E2E顧客');
  if (V2) {
    // v2 は API トークンを発行しない (Sanctum の SPA セッション cookie)。画面にトークンが出ず、API がセッションで通ることを確かめる
    const html = await (await page.request.get('/order/create')).text();
    expect(html).not.toMatch(/:token=/);
    const xsrf = (await page.context().cookies()).find((c) => c.name === 'XSRF-TOKEN');
    const res = await page.request.post('/api/customer/search', {
      data: { manager_id: Number(new URL(page.url()).searchParams.get('m')), keyword: 'E2E' },
      headers: { Accept: 'application/json', 'X-XSRF-TOKEN': decodeURIComponent(xsrf ? xsrf.value : '') },
    });
    expect(res.status(), 'セッションだけで API が通る').toBeLessThan(400);
    return;
  }
  const tokenOf = async () => (await (await page.request.get('/order/create')).text()).match(/:token="&quot;(.+?)&quot;"/)[1];
  const a = await tokenOf();
  const b = await tokenOf();
  expect(b).toBe(a);
});
