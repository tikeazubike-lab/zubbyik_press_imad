import { test } from '@playwright/test';
import fs from 'fs';
import path from 'path';
import { waitForVisualStability } from './helpers/animations';
import { captureViewport } from './helpers/screenshots';

const BASE_URL = process.env.VISUAL_BASE_URL || 'https://imadconsulting.co.uk';
const BASE_DIR = path.resolve(__dirname, 'visual-baseline');

test('navigation scrolled state', async ({ page }, testInfo) => {
  const viewport = testInfo.project.name;
  await page.goto(BASE_URL, { waitUntil: 'domcontentloaded' });
  await waitForVisualStability(page);
  await page.evaluate(() => window.scrollBy({ top: 300, behavior: 'instant' as any }));
  await page.waitForTimeout(400);
  const slug = 'home-nav-scrolled';
  const screenshotPath = await captureViewport(page, slug, viewport, 'interaction');
  saveInteractionMeta(slug, viewport, screenshotPath, 'Navigation after scroll');
});

test('mobile menu open', async ({ page }, testInfo) => {
  const viewport = testInfo.project.name;
  if (viewport !== 'mobile') test.skip();
  await page.goto(BASE_URL, { waitUntil: 'domcontentloaded' });
  await waitForVisualStability(page);

  const menuButton = page.locator('[aria-label*="menu" i], button[class*="menu" i], .menu-toggle, [class*="hamburger" i]').first();
  if (await menuButton.isVisible().catch(() => false)) {
    await menuButton.click();
    await page.waitForTimeout(500);
    const slug = 'home-mobile-menu';
    const screenshotPath = await captureViewport(page, slug, viewport, 'interaction');
    saveInteractionMeta(slug, viewport, screenshotPath, 'Mobile menu open');
  }
});

test('dark mode', async ({ page }, testInfo) => {
  const viewport = testInfo.project.name;
  await page.goto(BASE_URL, { waitUntil: 'domcontentloaded' });
  await waitForVisualStability(page);

  const themeButton = page.locator('button[aria-label*="theme" i], button[class*="theme" i], [title*="theme" i]').first();
  if (await themeButton.isVisible().catch(() => false)) {
    await themeButton.click();
    await page.waitForTimeout(500);
    const slug = 'home-dark-mode';
    const screenshotPath = await captureViewport(page, slug, viewport, 'interaction');
    saveInteractionMeta(slug, viewport, screenshotPath, 'Dark mode toggle');
  }
});

function saveInteractionMeta(slug: string, viewport: string, screenshotPath: string, description: string) {
  const metaDir = path.join(BASE_DIR, 'metadata', 'interactions');
  fs.mkdirSync(metaDir, { recursive: true });
  fs.writeFileSync(
    path.join(metaDir, `${slug}__${viewport}.json`),
    JSON.stringify({ slug, viewport, screenshot: screenshotPath, description }, null, 2)
  );
}
