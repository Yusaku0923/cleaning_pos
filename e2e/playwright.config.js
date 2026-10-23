// @ts-check
const { defineConfig, devices } = require('@playwright/test');

// 書き込みを伴うテストがあるため、本番URLへの実行は拒否する
if (/cleaningpossystem\.shop/.test(process.env.E2E_BASE_URL || '')) {
  throw new Error('E2E は本番(cleaningpossystem.shop)に対して実行できません');
}

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
    baseURL: process.env.E2E_BASE_URL || 'http://localhost:8000',
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
    },
    {
      name: 'iPhone',
      use: { ...devices['iPhone 14'], defaultBrowserType: 'chromium' }, // WebKit不要。Chromiumでモバイルエミュレート
      testMatch: /delivery\.spec\.js/,
    },
  ],
  reporter: [['list'], ['html', { outputFolder: 'playwright-report', open: 'never' }]],
});
