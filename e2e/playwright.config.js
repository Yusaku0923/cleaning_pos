// @ts-check
const { defineConfig, devices } = require('@playwright/test');

// 書き込みを伴うテストがあるため、本番URLへの実行は拒否する
if (/cleaningpossystem\.shop/.test(process.env.E2E_BASE_URL || '')) {
  throw new Error('E2E は本番(cleaningpossystem.shop)に対して実行できません');
}

module.exports = defineConfig({
  testDir: './tests',
  timeout: 30000,
  use: {
    baseURL: process.env.E2E_BASE_URL || 'http://localhost:8000',
    headless: !!process.env.CI || process.env.HEADED !== '1',  // HEADED=1 で表示
    slowMo: Number(process.env.SLOWMO || 0),
    screenshot: 'only-on-failure',
    video: 'retain-on-failure',
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
