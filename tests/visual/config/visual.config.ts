import { defineConfig, devices } from '@playwright/test';
import path from 'path';

/**
 * Playwright configuration for HO-016 visual regression baseline capture.
 *
 * Captures the production site at desktop, tablet, and mobile viewports
 * with deterministic settings for repeatable baselines.
 */
export default defineConfig({
  testDir: path.resolve(__dirname, '..'),
  outputDir: path.resolve(__dirname, '../visual-baseline/test-results'),
  fullyParallel: false,
  forbidOnly: !!process.env.CI,
  retries: 0,
  workers: 1,
  reporter: [['list']],
  use: {
    baseURL: process.env.VISUAL_BASE_URL || 'https://imadconsulting.co.uk',
    trace: 'off',
    screenshot: 'off',
    video: 'off',
    actionTimeout: 15000,
    navigationTimeout: 30000,
    deviceScaleFactor: 1,
  },
  projects: [
    {
      name: 'desktop',
      use: {
        viewport: { width: 1440, height: 900 },
        userAgent: 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36',
      },
    },
    {
      name: 'tablet',
      use: {
        viewport: { width: 1024, height: 768 },
        userAgent: 'Mozilla/5.0 (iPad; CPU OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1',
      },
    },
    {
      name: 'mobile',
      use: {
        viewport: { width: 390, height: 844 },
        userAgent: 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1',
      },
    },
  ],
});
