import { test, expect } from '@playwright/test';
import type { Page } from '@playwright/test';
import { attachDiagnosticsListeners, collectPageDiagnostics } from './helpers/diagnostics';
import { captureFullPage, captureViewport } from './helpers/screenshots';

/**
 * Business Checkup — behaviour and visual capture spec.
 *
 * Spec: HO-063 §21 (RED -> GREEN), §22 (required automated tests), §23 (visual regression),
 *       §24 (security), §25 (performance).
 *
 * Requires /business-checkup/ to exist at VISUAL_BASE_URL (default: the local/staging
 * instance, not production, until the page is deployed).
 */

const ROUTE = '/business-checkup/';
const SLUG = 'business_checkup';

/**
 * Optional origin rewrite for running this spec against a local WordPress clone whose
 * canonical URL is pinned to another host (see HO-064 §F). Opt-in via env so the default
 * behaviour against production/staging is completely untouched:
 *
 *   VISUAL_BASE_URL=http://172.18.0.11 \
 *   CHECKUP_REWRITE_FROM=https://imadconsult.zubbystudio.site \
 *   CHECKUP_REWRITE_TO=http://172.18.0.11 \
 *   npx playwright test business-checkup.spec.ts --project=desktop
 */
const REWRITE_FROM = process.env.CHECKUP_REWRITE_FROM;
const REWRITE_TO = process.env.CHECKUP_REWRITE_TO;

/**
 * The existing-funnel regression checks assert behaviour that predates the checkup, so they
 * are aimed at the live site rather than the checkup's own instance. `crypto.randomUUID()`
 * inside contact.js requires a secure context, which rules out the plain-HTTP local clone.
 *
 *   CHECKUP_REGRESSION_BASE=https://imadconsulting.co.uk (default: the run's baseURL)
 */
const REGRESSION_BASE = process.env.CHECKUP_REGRESSION_BASE || '';

function regressionUrl(path: string) {
  return REGRESSION_BASE ? REGRESSION_BASE + path : path;
}

test.beforeEach(async ({ page }) => {
  if (!REWRITE_FROM || !REWRITE_TO) return;
  await page.route('**/*', (route) => {
    const url = route.request().url();
    if (url.startsWith(REWRITE_FROM)) {
      return route.continue({ url: REWRITE_TO + url.slice(REWRITE_FROM.length) });
    }
    return route.continue();
  });
});

// The fully predetermined fixture — identical to FIXTURE_MIXED in tests/unit/business-checkup.test.js.
const FIXTURE_MIXED: Record<string, string> = {
  stage: 'small',
  conversations: 'scattered', // 1 / 3
  presence: 'unsure', // 0 / 3
  email_identity: 'mixed', // 1 / 3
  wa_profile: 'not_set_up', // 0 / 2
  wa_number: 'no_clear', // 0 / 2
  wa_response: 'no_process', // 0 / 2
};

const FIXTURE_CEILING: Record<string, string> = {
  stage: 'growing',
  conversations: 'always',
  presence: 'current',
  email_identity: 'own_domain',
  wa_profile: 'business',
  wa_number: 'consistent',
  wa_response: 'clear_process',
};

const REQUIRED_ORDER = [
  'stage',
  'conversations',
  'presence',
  'email_identity',
  'wa_profile',
  'wa_number',
  'wa_response',
];

async function startCheckup(page: Page) {
  await page.goto(ROUTE, { waitUntil: 'domcontentloaded' });
  await page.locator('#checkup-start').click();
  await expect(page.locator('#checkup-question')).toBeVisible();
}

async function answerQuestion(page: Page, questionId: string, value: string) {
  await page.locator(`[data-checkup-answer="${questionId}"][value="${value}"]`).check();
}

/** Answer every required question, then optionally Q8. */
async function completeCheckup(
  page: Page,
  fixture: Record<string, string>,
  priority?: string
) {
  await startCheckup(page);
  for (const questionId of REQUIRED_ORDER) {
    await answerQuestion(page, questionId, fixture[questionId]);
    await page.locator('#checkup-next').click();
  }
  await expect(page.locator('#checkup-question')).toHaveAttribute(
    'data-checkup-question-id',
    'priority'
  );
  if (priority) {
    await answerQuestion(page, 'priority', priority);
  }
  await page.locator(priority ? '#checkup-next' : '#checkup-skip').click();
  await expect(page.locator('#checkup-result')).toBeVisible();
}

test('landing: intro renders and captions the commitment', async ({ page }, testInfo) => {
  const listeners = attachDiagnosticsListeners(page);
  await page.goto(ROUTE, { waitUntil: 'domcontentloaded' });

  await expect(page.locator('#checkup-intro-title')).toBeVisible();
  await expect(page.locator('#checkup-intro-meta')).toContainText('8');
  await expect(page.locator('#checkup-start')).toBeVisible();
  await expect(page.locator('#checkup-result')).toHaveCount(0);

  await captureFullPage(page, SLUG, testInfo.project.name, 'landing');

  const diagnostics = await collectPageDiagnostics(page, listeners);
  expect(diagnostics.consoleErrors, JSON.stringify(diagnostics.consoleErrors)).toHaveLength(0);
});

test('start: the first question replaces the intro and shows progress', async ({ page }, testInfo) => {
  await startCheckup(page);

  await expect(page.locator('#checkup-question')).toHaveAttribute(
    'data-checkup-question-id',
    'stage'
  );
  await expect(page.locator('#checkup-intro-title')).toHaveCount(0);
  await expect(page.locator('#checkup-progress-label')).toContainText('1');
  await expect(page.locator('#checkup-progress-label')).toContainText('8');

  await captureFullPage(page, SLUG, testInfo.project.name, 'question');
});

test('navigation: forward through every question and back again, answers surviving', async ({
  page,
}) => {
  await startCheckup(page);

  await answerQuestion(page, 'stage', 'small');
  await page.locator('#checkup-next').click();
  await expect(page.locator('#checkup-question')).toHaveAttribute(
    'data-checkup-question-id',
    'conversations'
  );

  await answerQuestion(page, 'conversations', 'mostly');
  await page.locator('#checkup-back').click();

  // Back returns to the previous question with the earlier choice still selected.
  await expect(page.locator('#checkup-question')).toHaveAttribute(
    'data-checkup-question-id',
    'stage'
  );
  await expect(page.locator('[data-checkup-answer="stage"][value="small"]')).toBeChecked();

  await page.locator('#checkup-next').click();
  await expect(page.locator('#checkup-question')).toHaveAttribute(
    'data-checkup-question-id',
    'conversations'
  );
  await expect(
    page.locator('[data-checkup-answer="conversations"][value="mostly"]')
  ).toBeChecked();
});

test('validation: a required question blocks progression with an inline message', async ({
  page,
}) => {
  await startCheckup(page);
  await page.locator('#checkup-next').click();

  await expect(page.locator('#checkup-question')).toHaveAttribute(
    'data-checkup-question-id',
    'stage'
  );
  await expect(page.locator('#checkup-error')).toBeVisible();
  await expect(page.locator('#checkup-error')).not.toBeEmpty();
  await expect(page.locator('#checkup-error')).toHaveAttribute('role', 'alert');
});

test('result: the predetermined fixture produces the exact expected result', async ({
  page,
}, testInfo) => {
  await completeCheckup(page, FIXTURE_MIXED, 'emails_spam');

  await expect(page.locator('#checkup-result-title')).toHaveText('Needs attention now');
  await expect(page.locator('[data-checkup-score="overall"]')).toContainText('2');
  await expect(page.locator('[data-checkup-score="overall"]')).toContainText('15');
  await expect(page.locator('[data-checkup-score="business_health"]')).toContainText('2');
  await expect(page.locator('[data-checkup-score="business_health"]')).toContainText('9');
  await expect(page.locator('[data-checkup-score="whatsapp"]')).toContainText('0');
  await expect(page.locator('[data-checkup-score="whatsapp"]')).toContainText('6');

  // Band wording for both dimensions.
  await expect(page.locator('[data-checkup-band="business_health"]')).toHaveText('At risk');
  await expect(page.locator('[data-checkup-band="whatsapp"]')).toHaveText('At risk');

  // Computed priority (machine-readable for the test) and stated priority are both shown.
  await expect(page.locator('#checkup-priority')).toHaveAttribute(
    'data-checkup-priority',
    'presence'
  );
  await expect(page.locator('#checkup-stated-priority')).toContainText(
    'Emails from my business are not reaching customers'
  );

  // Findings: between three and five.
  const findings = page.locator('#checkup-findings li');
  const count = await findings.count();
  expect(count).toBeGreaterThanOrEqual(3);
  expect(count).toBeLessThanOrEqual(5);

  await captureFullPage(page, SLUG, testInfo.project.name, 'result');
});

test('result: skipping Q8 falls back to the computed priority service', async ({ page }) => {
  await completeCheckup(page, FIXTURE_MIXED);

  await expect(page.locator('#checkup-result')).toBeVisible();
  await expect(page.locator('#checkup-stated-priority')).toHaveCount(0);
  await expect(page.locator('#checkup-primary-cta')).toHaveAttribute(
    'href',
    /\?service=wp-rebuild#contact$/
  );
});

test('result: identifies itself as a self-assessment, never a completed audit', async ({
  page,
}) => {
  await completeCheckup(page, FIXTURE_MIXED, 'emails_spam');

  const disclaimer = page.locator('#checkup-disclaimer');
  await expect(disclaimer).toBeVisible();
  await expect(disclaimer).toContainText('self-assessment');
  await expect(disclaimer).toContainText('not a technical audit');
});

test('result: appears before any contact details are requested', async ({ page }) => {
  await completeCheckup(page, FIXTURE_MIXED, 'emails_spam');

  // The checkup collects no personal data of its own.
  await expect(page.locator('input[type="email"]')).toHaveCount(0);
  await expect(page.locator('input[name="email"]')).toHaveCount(0);
  await expect(page.locator('textarea')).toHaveCount(0);
  await expect(page.locator('input[name="name"]')).toHaveCount(0);
});

test('attribution: UTM parameters survive from the checkup URL to the contact CTA', async ({
  page,
}) => {
  await page.goto(`${ROUTE}?utm_source=status&utm_campaign=ep001`, {
    waitUntil: 'domcontentloaded',
  });
  await page.locator('#checkup-start').click();
  for (const questionId of REQUIRED_ORDER) {
    await answerQuestion(page, questionId, FIXTURE_MIXED[questionId]);
    await page.locator('#checkup-next').click();
  }
  await answerQuestion(page, 'priority', 'emails_spam');
  await page.locator('#checkup-next').click();

  await expect(page.locator('#checkup-primary-cta')).toHaveAttribute(
    'href',
    /\?service=fix-spam&utm_source=status&utm_campaign=ep001#contact$/
  );
});

test('attribution: no UTM present means no UTM invented', async ({ page }) => {
  await completeCheckup(page, FIXTURE_MIXED, 'emails_spam');

  const href = await page.locator('#checkup-primary-cta').getAttribute('href');
  expect(href).toMatch(/\?service=fix-spam#contact$/);
  expect(href).not.toContain('utm_');
});

test('CTAs: the WhatsApp secondary action reuses the existing destination', async ({ page }) => {
  await completeCheckup(page, FIXTURE_MIXED, 'emails_spam');

  const whatsapp = page.locator('#checkup-whatsapp-cta');
  await expect(whatsapp).toBeVisible();
  await expect(whatsapp).toHaveAttribute('href', /^https:\/\/wa\.me\/\d+$/);
  await expect(whatsapp).toHaveAttribute('target', '_blank');
  await expect(whatsapp).toHaveAttribute('rel', /noopener/);
});

test('restart: Start Again clears the run and returns to the intro', async ({ page }) => {
  await completeCheckup(page, FIXTURE_MIXED, 'emails_spam');
  await page.locator('#checkup-restart').click();

  await expect(page.locator('#checkup-result')).toHaveCount(0);
  await expect(page.locator('#checkup-start')).toBeVisible();

  await startCheckup(page);
  await expect(page.locator('#checkup-question')).toHaveAttribute(
    'data-checkup-question-id',
    'stage'
  );
  await expect(page.locator('[data-checkup-answer="stage"]:checked')).toHaveCount(0);
});

test('accessibility: fieldset/legend, labels, live region and progress semantics', async ({
  page,
}) => {
  await startCheckup(page);

  await expect(page.locator('#checkup-question')).toHaveJSProperty('tagName', 'FIELDSET');
  await expect(page.locator('#checkup-question legend')).toHaveCount(1);

  // Every answer control is a real labelled input inside its label.
  const inputs = page.locator('.checkup-option input');
  const inputCount = await inputs.count();
  expect(inputCount).toBeGreaterThan(1);
  for (let i = 0; i < inputCount; i += 1) {
    await expect(inputs.nth(i)).toHaveAttribute('type', 'radio');
    await expect(inputs.nth(i)).toHaveAttribute('name', /.+/);
  }

  const progress = page.locator('#checkup-progress');
  await expect(progress).toHaveAttribute('role', 'progressbar');
  await expect(progress).toHaveAttribute('aria-valuenow', '1');
  await expect(progress).toHaveAttribute('aria-valuemax', '8');

  await expect(page.locator('#checkup-live')).toHaveAttribute('aria-live', 'polite');
});

test('accessibility: focus moves to the new question after advancing', async ({ page }) => {
  await startCheckup(page);
  await answerQuestion(page, 'stage', 'small');
  await page.locator('#checkup-next').click();

  await expect(page.locator('#checkup-question')).toHaveAttribute(
    'data-checkup-question-id',
    'conversations'
  );
  await expect(page.locator('#checkup-question')).toBeFocused();
});

test('accessibility: the whole checkup is completable by keyboard alone', async ({ page }) => {
  await page.goto(ROUTE, { waitUntil: 'domcontentloaded' });

  await page.locator('#checkup-start').focus();
  await page.keyboard.press('Enter');
  await expect(page.locator('#checkup-question')).toBeVisible();

  for (const questionId of REQUIRED_ORDER) {
    const firstOption = page.locator(`[data-checkup-answer="${questionId}"]`).first();
    await firstOption.focus();
    await page.keyboard.press('Space');
    await page.locator('#checkup-next').focus();
    await page.keyboard.press('Enter');
  }

  await page.locator('#checkup-skip').focus();
  await page.keyboard.press('Enter');
  await expect(page.locator('#checkup-result')).toBeVisible();
  await expect(page.locator('#checkup-primary-cta')).toBeVisible();
});

test('mobile: no horizontal overflow and adequately sized controls', async ({ page }) => {
  await startCheckup(page);

  const option = page.locator('.checkup-option').first();
  const box = await option.boundingBox();
  expect(box).not.toBeNull();
  expect(box!.height).toBeGreaterThanOrEqual(44);

  const next = await page.locator('#checkup-next').boundingBox();
  expect(next).not.toBeNull();
  expect(next!.height).toBeGreaterThanOrEqual(44);
  expect(next!.width).toBeGreaterThanOrEqual(44);

  // Finish the run in place. (Re-navigating here would correctly resume the stored run, which
  // is what the restart test already covers.)
  await answerQuestion(page, 'stage', FIXTURE_MIXED.stage);
  await page.locator('#checkup-next').click();
  for (const questionId of REQUIRED_ORDER.slice(1)) {
    await answerQuestion(page, questionId, FIXTURE_MIXED[questionId]);
    await page.locator('#checkup-next').click();
  }
  await page.locator('#checkup-skip').click();
  await expect(page.locator('#checkup-result')).toBeVisible();

  const diagnostics = await collectPageDiagnostics(page, attachDiagnosticsListeners(page));
  expect(diagnostics.horizontalOverflow, `scrollWidth ${diagnostics.scrollWidth} > clientWidth ${diagnostics.clientWidth}`).toBe(false);
});

test('performance: the checkup does not depend on GSAP or the animation pipeline', async ({
  page,
}) => {
  // The theme enqueues its GSAP bundle for every non-mobile page; the checkup adds nothing to
  // that and must not need it. Block the animation pipeline entirely and complete a run.
  await page.route(/(gsap|ScrollTrigger|TextPlugin|\/animations\/)/i, (route) => route.abort());

  await page.goto(ROUTE, { waitUntil: 'domcontentloaded' });
  const gsapPresent = await page.evaluate(() => typeof (window as any).gsap !== 'undefined');
  expect(gsapPresent).toBe(false);

  await page.locator('#checkup-start').click();
  for (const questionId of REQUIRED_ORDER) {
    await answerQuestion(page, questionId, FIXTURE_MIXED[questionId]);
    await page.locator('#checkup-next').click();
  }
  await page.locator('#checkup-skip').click();

  await expect(page.locator('#checkup-result')).toBeVisible();
  await expect(page.locator('#checkup-result-title')).toHaveText('Needs attention now');
  await expect(page.locator('#checkup-primary-cta')).toBeVisible();
});

test('regression: checkup assets are not loaded on unrelated pages', async ({ page }) => {
  await page.goto('/', { waitUntil: 'domcontentloaded' });

  const assets = await page.locator('script[src], link[rel="stylesheet"]').evaluateAll((nodes) =>
    nodes.map((node) => node.getAttribute('src') || node.getAttribute('href') || '')
  );
  const offenders = assets.filter((href) => /business-checkup/i.test(href));
  expect(offenders, offenders.join('\n')).toHaveLength(0);
});

test('regression: the homepage hero campaign CTA still swaps on UTM arrival', async ({
  page,
}) => {
  await page.goto(regressionUrl('/?utm_source=status&utm_campaign=ep001'), {
    waitUntil: 'domcontentloaded',
  });

  const heroCta = page.locator('.hero-actions .btn-primary');
  await expect(heroCta).toBeVisible();
  await expect(heroCta).toHaveText(/Tell me your problem/i);

  await page.goto(regressionUrl('/'), { waitUntil: 'domcontentloaded' });
  await expect(page.locator('.hero-actions .btn-primary')).toHaveText(/View My Projects/i);
});

test('regression: the campaign redirect still resolves to the homepage with UTM parameters', async ({
  request,
}) => {
  const response = await request.get('https://api.imadconsulting.co.uk/r/status?campaign=ep001', {
    maxRedirects: 0,
  });

  expect(response.status()).toBe(307);
  const location = response.headers()['location'] || '';
  expect(location).toContain('imadconsulting.co.uk');
  expect(location).toContain('utm_source=status');
  expect(location).toContain('utm_campaign=ep001');
});

test('mobile result: captured for the baseline set', async ({ page }, testInfo) => {
  test.skip(testInfo.project.name !== 'mobile', 'mobile-only capture');
  await completeCheckup(page, FIXTURE_MIXED, 'emails_spam');
  await captureViewport(page, SLUG, testInfo.project.name, 'result-mobile');
  await expect(page.locator('#checkup-result')).toBeVisible();
});
