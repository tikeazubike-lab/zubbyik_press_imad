const EXCLUDED_PATHS = new Set([
  '/wp-login.php',
  '/wp-admin',
  '/wp-content/uploads',
  '/feed',
  '/comments/feed',
]);

function isInternalUrl(urlString, baseOrigin) {
  try {
    const url = new URL(urlString, baseOrigin);
    return url.origin === baseOrigin;
  } catch {
    return false;
  }
}

function normalizeUrl(urlString, baseOrigin) {
  try {
    const url = new URL(urlString, baseOrigin);
    return `${url.pathname}`;
  } catch {
    return urlString;
  }
}

function shouldInclude(path) {
  if (EXCLUDED_PATHS.has(path)) return false;
  for (const excluded of EXCLUDED_PATHS) {
    if (path.startsWith(excluded)) return false;
  }
  return true;
}

/**
 * Discover internal routes from a page by scanning anchor links.
 */
async function discoverRoutesFromPage(page, baseUrl) {
  const baseOrigin = new URL(baseUrl).origin;
  const links = await page.$$eval('a[href]', (anchors) =>
    anchors.map((a) => a.href)
  );

  const routes = [];
  const seen = new Set();

  for (const href of links) {
    if (!isInternalUrl(href, baseOrigin)) continue;
    const path = normalizeUrl(href, baseOrigin);
    if (!shouldInclude(path)) continue;
    if (seen.has(path)) continue;
    seen.add(path);
    routes.push({ url: `${baseOrigin}${path}`, source: href });
  }

  return routes;
}

/**
 * Known static routes for the portfolio site.
 */
function getStaticRoutes(baseUrl) {
  const baseOrigin = new URL(baseUrl).origin;
  return [
    { url: `${baseOrigin}/`, source: 'static-root' },
    { url: `${baseOrigin}/blog`, source: 'static-nav' },
    { url: `${baseOrigin}/thank-you`, source: 'static-template' },
  ];
}

module.exports = {
  discoverRoutesFromPage,
  getStaticRoutes,
  isInternalUrl,
  normalizeUrl,
  shouldInclude,
};
