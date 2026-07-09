const { chromium } = require('playwright');
const { writeFileSync } = require('fs');

const URL = process.argv[2];
const OUTDIR = process.argv[3];

const sections = [
  { id: 'home',      name: '01-hero' },
  { id: 'about',     name: '02-about' },
  { id: 'skills',    name: '03-skills' },
  { id: 'projects',  name: '04-projects' },
  { id: 'experience',name: '05-experience' },
  { id: 'blog',      name: '06-blog' },
  { id: 'contact',   name: '07-contact' },
];

async function run() {
  const browser = await chromium.launch({ headless: true });
  const context = await browser.newContext({
    viewport: { width: 1440, height: 900 },
    deviceScaleFactor: 2,
  });
  const page = await context.newPage();

  console.log(`Navigating to ${URL}...`);
  await page.goto(URL, { waitUntil: 'networkidle', timeout: 30000 });
  await page.waitForTimeout(2000);

  console.log('  Full page...');
  await page.screenshot({ path: `${OUTDIR}/00-fullpage.png`, fullPage: true });

  for (const section of sections) {
    console.log(`  Section: ${section.name}...`);
    try {
      const el = page.locator(`#${section.id}`).first();
      if (await el.isVisible()) {
        await el.scrollIntoViewIfNeeded();
        await page.waitForTimeout(1000);
        await page.evaluate(() => window.scrollBy(0, -80));
        await page.waitForTimeout(500);
        await el.screenshot({ path: `${OUTDIR}/${section.name}.png` });
      }
    } catch (e) {
      console.log(`    Error: ${e.message}`);
    }
  }

  // Project cards
  console.log('  Project cards...');
  for (let i = 0; i < 3; i++) {
    try {
      const card = page.locator('.proj-card').nth(i);
      if (await card.isVisible()) {
        await card.scrollIntoViewIfNeeded();
        await page.waitForTimeout(1500);
        await card.screenshot({ path: `${OUTDIR}/04-project-card-${i + 1}.png` });
      }
    } catch (e) {
      console.log(`    Card ${i + 1} error: ${e.message}`);
    }
  }

  // Dark mode
  console.log('  Dark mode...');
  await page.evaluate(() => document.documentElement.classList.add('dark'));
  await page.waitForTimeout(500);
  await page.screenshot({ path: `${OUTDIR}/08-darkmode.png`, fullPage: false });

  // Mobile
  console.log('  Mobile viewport...');
  await page.setViewportSize({ width: 375, height: 812 });
  await page.evaluate(() => window.scrollTo(0, 0));
  await page.waitForTimeout(500);
  await page.screenshot({ path: `${OUTDIR}/09-mobile.png`, fullPage: true });

  console.log('');
  console.log('Screenshots saved to:', OUTDIR);
  await browser.close();
}

run().catch(e => { console.error(e); process.exit(1); });
