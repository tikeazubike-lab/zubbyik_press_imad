const { chromium } = require('@playwright/test');
const fs = require('fs');
const path = require('path');
const { discoverRoutesFromPage, getStaticRoutes } = require('./helpers/routes');

const BASE_URL = process.env.VISUAL_BASE_URL || 'https://imadconsulting.co.uk';
const OUTPUT = path.resolve(__dirname, 'visual-baseline/routes.json');

async function main() {
  const browser = await chromium.launch();
  const context = await browser.newContext({ viewport: { width: 1440, height: 900 } });
  const page = await context.newPage();

  const staticRoutes = getStaticRoutes(BASE_URL);
  const allRoutes = new Map();

  for (const route of staticRoutes) {
    allRoutes.set(route.url, route);
  }

  const pagesToCrawl = [BASE_URL, `${BASE_URL}/blog`];

  for (const pageUrl of pagesToCrawl) {
    try {
      await page.goto(pageUrl, { waitUntil: 'networkidle', timeout: 30000 });
      const routes = await discoverRoutesFromPage(page, BASE_URL);
      for (const route of routes) {
        allRoutes.set(route.url, route);
      }
    } catch (err) {
      console.warn(`Failed to crawl ${pageUrl}: ${err.message}`);
    }
  }

  const routeList = Array.from(allRoutes.values()).sort((a, b) => a.url.localeCompare(b.url));

  fs.mkdirSync(path.dirname(OUTPUT), { recursive: true });
  fs.writeFileSync(OUTPUT, JSON.stringify({ baseUrl: BASE_URL, routes: routeList }, null, 2));

  console.log(`Discovered ${routeList.length} routes.`);
  console.log(`Saved to ${OUTPUT}`);

  await browser.close();
}

main().catch((err) => {
  console.error(err);
  process.exit(1);
});
