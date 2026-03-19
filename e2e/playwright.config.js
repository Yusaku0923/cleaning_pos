// @ts-check
const { defineConfig, devices } = require('@playwright/test');

module.exports = defineConfig({
  testDir: './tests',
  timeout: 30000,
  use: {
    baseURL: 'http://localhost:8000',
    headless: false,       // ブラウザを表示してデバッグしやすく
    slowMo: 300,           // 操作を遅くして目で追えるように
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
      use: { ...devices['iPhone 14'] },
    },
  ],
  reporter: [['list'], ['html', { outputFolder: 'playwright-report', open: 'on-failure' }]],
});
