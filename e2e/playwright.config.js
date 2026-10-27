// @ts-check
const { defineConfig, devices } = require('@playwright/test');

// 書き込みを伴うテストがあるため、本番URLへの実行は拒否する
const BASE_URL = process.env.E2E_BASE_URL || 'http://localhost:8000';
if (/cleaningpossystem\.shop/.test(BASE_URL)) {
  throw new Error('E2E は本番(cleaningpossystem.shop)に対して実行できません');
}
// v2 の XREA (切替後は本番になる)。E2E は E2E 店舗にデータを作るので、E2E_ALLOW_V2=1 を付けたときだけ流す。
// 切替(2026-10-29)後は E2E_ALLOW_V2=1 で流さないこと (流すなら v2 DB が本番のコピーである間だけ)。
const V2_HOSTS = /asahiya-pos\.xrea\.jp/i;
if (V2_HOSTS.test(BASE_URL) && process.env.E2E_ALLOW_V2 !== '1') {
  throw new Error('E2E は v2 (asahiya-pos.xrea.jp) に対して E2E_ALLOW_V2=1 がないと実行できません (切替後は本番)');
}
// XREA の v2 は全員 Basic 認証。E2E_HTTP_USER / E2E_HTTP_PASSWORD で渡す (リポジトリには書かない)
const httpCredentials = process.env.E2E_HTTP_USER
  ? { username: process.env.E2E_HTTP_USER, password: process.env.E2E_HTTP_PASSWORD || '' }
  : undefined;
// DNS が古いとき用: E2E_HOST_RESOLVER_RULES='MAP asahiya-pos.xrea.jp 160.251.151.71'
const launchOptions = process.env.E2E_HOST_RESOLVER_RULES
  ? { args: [`--host-resolver-rules=${process.env.E2E_HOST_RESOLVER_RULES}`] }
  : undefined;

// E2E_TARGET=legacy|v2。画面比較の基準画像は常に snapshots/legacy/ (旧で --update-snapshots して作る)。
// v2 実行時は旧の基準画像と比べる。失敗時の成果物は test-results/<target>/ に分ける。
const TARGET = process.env.E2E_TARGET || 'legacy';
if (!['legacy', 'v2'].includes(TARGET)) throw new Error('E2E_TARGET は legacy か v2');

module.exports = defineConfig({
  testDir: './tests',
  outputDir: `./test-results/${TARGET}`,
  snapshotPathTemplate: '{snapshotDir}/legacy/{arg}{ext}',
  snapshotDir: './snapshots',
  globalSetup: require.resolve('./global-setup.js'),
  retries: process.env.CI ? 2 : 1, // 失敗は trace を残して1回だけ再試行
  timeout: 30000,
  use: {
    baseURL: BASE_URL,
    httpCredentials,
    launchOptions,
    headless: !!process.env.CI || process.env.HEADED !== '1',  // HEADED=1 で表示
    slowMo: Number(process.env.SLOWMO || 0),
    screenshot: 'only-on-failure',
    video: 'retain-on-failure',
    trace: 'on-first-retry',
  },
  projects: [
    {
      name: 'chromium',
      use: { ...devices['Desktop Chrome'] },
      testIgnore: /parity\//,
    },
    // 画面比較は書き込み系のテスト (order-flow など) が終わって後始末したあとに撮る。並行で流すと、
    // 預り一覧などに実行中の注文が映り、基準 (空の状態) と比べられない
    {
      name: 'parity',
      use: { ...devices['Desktop Chrome'] },
      testMatch: /parity\/.*\.spec\.js/,
      dependencies: ['chromium'],
    },
    {
      name: 'iPhone',
      use: { ...devices['iPhone 14'], defaultBrowserType: 'chromium' }, // WebKit不要。Chromiumでモバイルエミュレート
      testMatch: /delivery\.spec\.js/,
    },
  ],
  reporter: [['list'], ['html', { outputFolder: 'playwright-report', open: 'never' }]],
});
