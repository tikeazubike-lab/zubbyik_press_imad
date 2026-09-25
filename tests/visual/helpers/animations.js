/**
 * Wait for the page to be visually stable before capturing screenshots.
 */
async function waitForVisualStability(page) {
  await page.waitForLoadState('domcontentloaded');
  await page.waitForLoadState('networkidle');

  await page.evaluate(() => document.fonts.ready);

  await page.evaluate(() =>
    Promise.all(
      Array.from(document.images)
        .filter((img) => !img.complete)
        .map(
          (img) =>
            new Promise((resolve) => {
              img.addEventListener('load', () => resolve(), { once: true });
              img.addEventListener('error', () => resolve(), { once: true });
              setTimeout(() => resolve(), 3000);
            })
        )
    )
  );

  await page.waitForTimeout(800);

  await page.evaluate(() => {
    if (typeof window !== 'undefined' && window.ScrollTrigger) {
      window.ScrollTrigger.refresh();
    }
  });

  await page.waitForTimeout(300);
}

/**
 * Scroll to a specific percentage of the document height.
 */
async function scrollToPercent(page, percent) {
  await page.evaluate((pct) => {
    const height = document.documentElement.scrollHeight - window.innerHeight;
    window.scrollTo({ top: height * (pct / 100), behavior: 'instant' });
  }, percent);

  await page.waitForTimeout(600);

  await page.evaluate(() => {
    if (typeof window !== 'undefined' && window.ScrollTrigger) {
      window.ScrollTrigger.refresh();
    }
  });

  await page.waitForTimeout(300);
}

module.exports = { waitForVisualStability, scrollToPercent };
