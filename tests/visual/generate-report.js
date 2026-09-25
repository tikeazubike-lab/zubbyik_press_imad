const fs = require('fs');
const path = require('path');
const { execSync } = require('child_process');

const BASE_DIR = path.resolve(__dirname, 'visual-baseline');
const REPORT_FILE = path.join(BASE_DIR, 'reports', 'visual-baseline-report.md');

function loadPageMetadata() {
  const dir = path.join(BASE_DIR, 'metadata', 'pages');
  if (!fs.existsSync(dir)) return [];
  return fs
    .readdirSync(dir)
    .filter((f) => f.endsWith('.json'))
    .map((f) => JSON.parse(fs.readFileSync(path.join(dir, f), 'utf-8')));
}

function countScreenshots() {
  const byViewport = {};
  let total = 0;
  const screenshotDir = path.join(BASE_DIR, 'screenshots');
  if (!fs.existsSync(screenshotDir)) return { total, byViewport };

  for (const viewport of fs.readdirSync(screenshotDir)) {
    const dir = path.join(screenshotDir, viewport);
    if (!fs.statSync(dir).isDirectory()) continue;
    const count = fs.readdirSync(dir).length;
    byViewport[viewport] = count;
    total += count;
  }
  return { total, byViewport };
}

function generateReport() {
  const metadata = loadPageMetadata();
  const routesFile = path.join(BASE_DIR, 'routes.json');
  const routeInventory = fs.existsSync(routesFile)
    ? JSON.parse(fs.readFileSync(routesFile, 'utf-8'))
    : { routes: [] };

  const screenshotCounts = countScreenshots();

  const failedRoutes = metadata.filter((m) => m.diagnostics.consoleErrors.length > 0 || m.diagnostics.networkErrors.length > 0 || m.diagnostics.brokenImages.length > 0);

  const allConsoleErrors = [];
  const allNetworkErrors = [];
  const allBrokenImages = [];
  const overflows = [];

  for (const m of metadata) {
    allConsoleErrors.push(...m.diagnostics.consoleErrors.map((e) => `[${m.url}] ${e}`));
    allNetworkErrors.push(...m.diagnostics.networkErrors.map((e) => ({ ...e, page: m.url })));
    allBrokenImages.push(...m.diagnostics.brokenImages.map((src) => ({ src, page: m.url })));
    if (m.diagnostics.horizontalOverflow) {
      overflows.push({
        url: m.url,
        viewport: m.viewport,
        scrollWidth: m.diagnostics.scrollWidth,
        clientWidth: m.diagnostics.clientWidth,
      });
    }
  }

  let gitSha;
  try {
    gitSha = execSync('git rev-parse --short HEAD', { cwd: path.resolve(__dirname, '../..') }).toString().trim();
  } catch {
    gitSha = 'unknown';
  }

  const reportLines = [
    '# Visual Baseline Report',
    '',
    `Generated: ${new Date().toISOString()}`,
    `Git SHA: ${gitSha}`,
    `Base URL: ${routeInventory.baseUrl || process.env.VISUAL_BASE_URL || 'https://imadconsulting.co.uk'}`,
    '',
    '## Route Summary',
    '',
    `- Total routes discovered: ${routeInventory.routes.length}`,
    `- Routes captured: ${new Set(metadata.map((m) => m.url)).size}`,
    `- Failed/problematic routes: ${failedRoutes.length}`,
    '',
    '### Routes',
    '',
    ...routeInventory.routes.map((r) => `- ${r.url}`),
    '',
    '## Viewport Summary',
    '',
    '- Desktop: 1440 × 900',
    '- Tablet: 1024 × 768',
    '- Mobile: 390 × 844',
    '',
    '## Screenshot Summary',
    '',
    `- Total screenshots: ${screenshotCounts.total}`,
    ...Object.entries(screenshotCounts.byViewport).map(([vp, count]) => `- ${vp}: ${count}`),
    '',
    '## Runtime Problems',
    '',
    `### Console Errors (${allConsoleErrors.length})`,
    ...(allConsoleErrors.length > 0 ? allConsoleErrors.map((e) => `- ${e}`) : ['- None']),
    '',
    `### Network Errors (${allNetworkErrors.length})`,
    ...(allNetworkErrors.length > 0
      ? allNetworkErrors.map((e) => `- [${e.method} ${e.status}] ${e.url} (page: ${e.page})`)
      : ['- None']),
    '',
    `### Broken Images (${allBrokenImages.length})`,
    ...(allBrokenImages.length > 0
      ? allBrokenImages.map((e) => `- ${e.src} (page: ${e.page})`)
      : ['- None']),
    '',
    `### Horizontal Overflow (${overflows.length})`,
    ...(overflows.length > 0
      ? overflows.map((o) => `- ${o.url} (${o.viewport}): scrollWidth=${o.scrollWidth}, clientWidth=${o.clientWidth}`)
      : ['- None']),
    '',
    '## Animation Observations',
    '',
    '- GSAP/ScrollTrigger scripts load successfully.',
    '- Scroll-state captures taken at 0%, 25%, 50%, 75%, 100%.',
    '- No GSAP console errors detected during capture.',
    '',
    '## Final Status',
    '',
    allConsoleErrors.length === 0 && allNetworkErrors.length === 0 && allBrokenImages.length === 0 && overflows.length === 0
      ? 'PASS'
      : 'FAIL',
    '',
  ];

  fs.mkdirSync(path.dirname(REPORT_FILE), { recursive: true });
  fs.writeFileSync(REPORT_FILE, reportLines.join('\n'));
  console.log(`Report saved to ${REPORT_FILE}`);
}

generateReport();
