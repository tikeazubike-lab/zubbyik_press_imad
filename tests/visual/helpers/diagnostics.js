/**
 * Attach diagnostic listeners to a Playwright page.
 */
function attachDiagnosticsListeners(page) {
  const consoleErrors = [];
  const consoleWarnings = [];
  const networkErrors = [];

  page.on('console', (msg) => {
    const text = msg.text();
    if (msg.type() === 'error') {
      consoleErrors.push(text);
    } else if (msg.type() === 'warning') {
      consoleWarnings.push(text);
    }
  });

  page.on('pageerror', (err) => {
    consoleErrors.push(err.message);
  });

  page.on('response', (response) => {
    const status = response.status();
    if (status >= 400) {
      networkErrors.push({
        url: response.url(),
        status,
        method: response.request().method(),
      });
    }
  });

  page.on('requestfailed', (request) => {
    networkErrors.push({
      url: request.url(),
      status: null,
      method: request.method(),
    });
  });

  return { consoleErrors, consoleWarnings, networkErrors };
}

/**
 * Collect page diagnostics after navigation/scrolling.
 */
async function collectPageDiagnostics(page, listeners) {
  const dims = await page.evaluate(() => ({
    scrollWidth: document.documentElement.scrollWidth,
    clientWidth: document.documentElement.clientWidth,
    documentHeight: document.documentElement.scrollHeight,
  }));

  const brokenImages = await page.$$eval('img', (images) =>
    images
      .filter((img) => !img.complete || img.naturalWidth === 0)
      .map((img) => img.src)
  );

  return {
    consoleErrors: listeners.consoleErrors.slice(),
    consoleWarnings: listeners.consoleWarnings.slice(),
    networkErrors: listeners.networkErrors.slice(),
    brokenImages,
    horizontalOverflow: dims.scrollWidth > dims.clientWidth,
    scrollWidth: dims.scrollWidth,
    clientWidth: dims.clientWidth,
    documentHeight: dims.documentHeight,
  };
}

module.exports = { attachDiagnosticsListeners, collectPageDiagnostics };
