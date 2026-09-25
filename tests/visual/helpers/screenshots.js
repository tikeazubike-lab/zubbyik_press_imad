const fs = require('fs');
const path = require('path');

const BASE_DIR = path.resolve(__dirname, '../visual-baseline');

function slugifyRoute(urlPath) {
  return (
    urlPath
      .replace(/^\//, '')
      .replace(/\/$/, '')
      .replace(/[^a-z0-9]+/gi, '_')
      .toLowerCase() || 'home'
  );
}

function getScreenshotDir(viewport) {
  return path.join(BASE_DIR, 'screenshots', viewport);
}

async function captureFullPage(page, routeSlug, viewport, suffix) {
  const dir = getScreenshotDir(viewport);
  fs.mkdirSync(dir, { recursive: true });
  const filename = `${routeSlug}__${viewport}__${suffix}.png`;
  const filepath = path.join(dir, filename);
  await page.screenshot({ path: filepath, fullPage: true });
  return path.relative(BASE_DIR, filepath);
}

async function captureViewport(page, routeSlug, viewport, suffix) {
  const dir = getScreenshotDir(viewport);
  fs.mkdirSync(dir, { recursive: true });
  const filename = `${routeSlug}__${viewport}__${suffix}.png`;
  const filepath = path.join(dir, filename);
  await page.screenshot({ path: filepath, fullPage: false });
  return path.relative(BASE_DIR, filepath);
}

module.exports = { slugifyRoute, getScreenshotDir, captureFullPage, captureViewport };
