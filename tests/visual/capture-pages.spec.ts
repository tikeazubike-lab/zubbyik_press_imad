import { test, expect } from '@playwright/test';
import fs from 'fs';
import path from 'path';
import { scrollToPercent, waitForVisualStability } from './helpers/animations';
import { attachDiagnosticsListeners, collectPageDiagnostics } from './helpers/diagnostics';
import { captureFullPage, captureViewport, slugifyRoute } from './helpers/screenshots';

const BASE_DIR = path.resolve(__dirname, 'visual-baseline');
const ROUTES_FILE = path.join(BASE_DIR, 'routes.json');

interface RouteInventory {
  baseUrl: string;
  routes: Array<{ url: string; source: string }>;
}

function loadRoutes(): RouteInventory {
  if (!fs.existsSync(ROUTES_FILE)) {
    throw new Error(`Routes file not found: ${ROUTES_FILE}. Run "npm run visual:routes" first.`);
  }
  return JSON.parse(fs.readFileSync(ROUTES_FILE, 'utf-8'));
}

const inventory = loadRoutes();

for (const route of inventory.routes) {
  const routeSlug = slugifyRoute(new URL(route.url).pathname);

  test(`capture ${routeSlug}`, async ({ page }, testInfo) => {
    const viewport = testInfo.project.name;
    const listeners = attachDiagnosticsListeners(page);
    const metadata: any = {
      url: route.url,
      viewport,
      routeSlug,
      screenshots: [],
      scrollStates: [],
    };

    await page.goto(route.url, { waitUntil: 'domcontentloaded' });
    await waitForVisualStability(page);

    const initialScreenshot = await captureFullPage(page, routeSlug, viewport, 'initial-full');
    metadata.screenshots.push({ type: 'initial-full', path: initialScreenshot });

    for (const pct of [0, 25, 50, 75, 100]) {
      await scrollToPercent(page, pct);
      const scrollScreenshot = await captureViewport(page, routeSlug, viewport, `scroll-${pct.toString().padStart(2, '0')}`);
      metadata.scrollStates.push({ percent: pct, path: scrollScreenshot });
    }

    const diagnostics = await collectPageDiagnostics(page, listeners);
    metadata.diagnostics = diagnostics;

    const metaDir = path.join(BASE_DIR, 'metadata', 'pages');
    fs.mkdirSync(metaDir, { recursive: true });
    fs.writeFileSync(
      path.join(metaDir, `${routeSlug}__${viewport}.json`),
      JSON.stringify(metadata, null, 2)
    );

    expect(diagnostics.consoleErrors.length).toBeLessThan(10);
  });
}
