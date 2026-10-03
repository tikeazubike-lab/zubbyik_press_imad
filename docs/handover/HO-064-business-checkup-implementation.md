---
type: HANDOVER
project: IMAD Consulting — Lead Capture & Content Automation
title: HO-064 — Business Checkup MVP implementation (HO-063 §29 report)
date: 2026-10-01
from: Hermes (implementation agent)
to: ChatGPT (review) / Malachy (approval of the OPEN items)
status: IMPLEMENTED — tests green, nothing committed, nothing pushed, nothing deployed
priority: HIGH
---

Answer to HO-063 §29. Test-first: RED was captured before implementation, GREEN afterwards.
Nothing was committed, pushed or deployed. Every claim below is backed by raw output in the
sections that follow.

---

## A. Implementation summary

A standalone `/business-checkup/` page, built as a classic WordPress page template with its
own plain-JS module and one bounded stylesheet. Eight questions (six scored), deterministic
client-side scoring, result shown before any contact details are requested, and a handoff into
**the existing contact flow** with campaign parameters carried through.

- **No new lead endpoint.** The result's primary CTA is
  `/?service=<slug>&utm_source=…&utm_campaign=…#contact` — the same mechanism
  `template-parts/section-offers.php:154` already uses.
- **No database change, no migration, no new table.** Nothing is stored server-side.
- **No new notification path.** Notification remains whatever the existing contact form does
  (FastAPI + WP `wp_mail` + Telegram + dead letters).
- **No AI, no analytics, no website/email checker.**
- **No new dependency, no build step, no framework.**
- Scoring lives in one pure function (`scoreCheckup`) exported for unit tests; the file avoids
  `crypto`, `fetch` and anything else requiring a secure context, so it works on plain HTTP
  too (the theme's `contact.js` does not — see §G observations).

Deliberate interpretations, stated rather than silently resolved:

1. **Priority = largest shortfall, not lowest raw points.** The scored maxima differ (3 vs 2), so
   "lowest points" would flag a *fully-maxed* WhatsApp question as the priority whenever a
   business question also scored low. Largest shortfall keeps the specified tie-break order
   (`conversations → presence → email_identity → wa_profile → wa_number → wa_response`) and
   guarantees a perfect answer can never be reported as a problem.
2. **Q8 drives the CTA slug; the computed priority is still shown.** `recommendedSlug` =
   stated priority if given, else the computed priority's service. Both are displayed, neither
   replaces the other (HO-063 §7).
3. **No sticky action bar on phones** (HO-062 suggested one). The site's chat launcher is
   `position:fixed; bottom:24px; right:24px; z-index:9999` and physically intercepted clicks on
   the checkup's actions during the test run; a sticky bar would sit under another component's
   hit area. Actions stay in normal flow with mobile bottom padding instead. Evidence in §C.
4. **Two questions map to no service slug.** `conversations`, `wa_profile`, `wa_number`,
   `wa_response` have no matching offer in the existing nine, and HO-063 §11 forbids inventing
   a WhatsApp slug — those hand off with **no** `service=` parameter. `presence → wp-rebuild`,
   `email_identity → m365-setup`, and all four Q8 options map to real existing slugs.

---

## B. Exact files changed

### CREATE

| Path | Purpose | Notes |
|---|---|---|
| `malachy-portfolio/template-business-checkup.php` | `Template Name: Business Checkup` page template | Server-inert: no form post, no DB write. Reuses `get_option('malachy_whatsapp')` (existing centralised setting) and `home_url('/')`, both escaped and passed to JS as data attributes. |
| `malachy-portfolio/assets/js/business-checkup.js` | Question data, pure scoring, validation, state, rendering, CTA construction | Plain ES5-style IIFE, no dependencies. `module.exports` guard makes it testable in Node; `typeof document === 'undefined'` guard keeps it importable without a DOM. All DOM built with `createElement`/`textContent` — no `innerHTML` (HO-063 §24). |
| `malachy-portfolio/assets/css/business-checkup.css` | Bounded `.checkup-*` styles | Every selector namespaced; values all from existing tokens (`--card`, `--border`, `--radius`, `--primary`, `--font-display`…). No animation, no global selectors, no breakpoints beyond the existing 767px. |
| `tests/unit/business-checkup.test.js` | 34 unit tests for the scoring contract | `node:test` + `node:assert` — built into Node, so **no new test framework and no dependency** (HO-063 §26). |
| `tests/visual/business-checkup.spec.ts` | 21 browser tests + visual captures | Follows the existing harness conventions (`helpers/diagnostics`, `helpers/screenshots`, `captureFullPage`/`captureViewport`, project viewports). |

### MODIFY (both minimum, both reviewed)

| Path | Change | Risk |
|---|---|---|
| `malachy-portfolio/functions.php` | +20 lines: one guarded enqueue block for the checkup CSS/JS, gated on `is_page_template('template-business-checkup.php') \|\| is_page('business-checkup')` | Low. Purely additive inside `malachy_enqueue_assets()`; no other page's behaviour changes. |
| `malachy-portfolio/template-parts/navigation.php` | +1 array entry (`Business Checkup` → `/business-checkup/`, `'route' => true`) | Low in code. **Not zero in tests**: it is a site-wide markup change, so the shared visual baselines are now stale — see §F. |

### NOT TOUCHED (verified)

`contact.js`, `inc/contact-handler.php`, `section-hero.php`, `section-contact.php`,
`assets/js/animations/*` (incl. `AnimationManager.js`), `imad-automation/**`, migrations,
`docker-compose.yml`. `.htaccess` shows as modified in `git status` but that predates this task
(HO-058) and was not touched here.

---

## C. Test-first evidence

### RED — unit (module did not exist)

```
$ node --test tests/unit/
Error: Cannot find module '/home/zubbyik/wordpress_project/malachy-portfolio/assets/js/business-checkup.js'
    code: 'MODULE_NOT_FOUND',
ℹ tests 1
ℹ pass 0
ℹ fail 1
✖ tests/unit (53.688365ms)
  'test failed'
```

### RED — browser (page did not exist)

```
$ VISUAL_BASE_URL=http://172.18.0.11 CHECKUP_REWRITE_FROM=http://imadconsult.zubbystudio.site \
  CHECKUP_REWRITE_TO=http://172.18.0.11 \
  npx playwright test business-checkup.spec.ts --config=tests/visual/config/visual.config.ts \
  --project=desktop --reporter=line
  18 failed
     [desktop] › landing: intro renders and captions the commitment
     [desktop] › start: the first question replaces the intro and shows progress
     … (all 18 feature tests)
  1 skipped
  1 passed (4.3m)
```

The one that passed was `regression: checkup assets are not loaded on unrelated pages` — i.e.
the harness itself was proven working before implementation, so the 18 failures are missing
functionality rather than broken infrastructure (HO-063 §21).

Honest note on the RED sequence: the **first** RED attempt (19 failed) was contaminated by my
own harness bug — `route.continue` rejects a protocol change, and I had configured an
`https → http` rewrite (`Error: route.continue: New URL must have same protocol as overridden
URL`). That made *every* test fail, including the regressions, which would have been
misleading evidence. I diagnosed it, switched the rewrite to same-protocol
(`http://… → http://…`), and re-ran to get the clean RED above. The already-verified local
container emits asset URLs over `http://`, which is why the same-protocol form works.

### GREEN — unit

```
$ node --test tests/unit/*.test.js
ℹ tests 34
ℹ suites 0
ℹ pass 34
ℹ fail 0
ℹ cancelled 0
ℹ skipped 0
ℹ todo 0
ℹ duration_ms 125.806302
```

(Note: this Node build rejects a bare directory argument — `node --test tests/unit/` fails with
`Cannot find module '…/tests/unit'` — so the file glob is the working command.)

### GREEN — browser, all three viewports

```
$ npx playwright test business-checkup.spec.ts --config=tests/visual/config/visual.config.ts --reporter=line
  2 skipped
  61 passed (1.2m)
```

10 screenshots produced, covering the states HO-063 §23 requires:

```
tests/visual/visual-baseline/screenshots/desktop/business_checkup__desktop__landing.png
tests/visual/visual-baseline/screenshots/desktop/business_checkup__desktop__question.png
tests/visual/visual-baseline/screenshots/desktop/business_checkup__desktop__result.png
tests/visual/visual-baseline/screenshots/tablet/business_checkup__tablet__landing.png
tests/visual/visual-baseline/screenshots/tablet/business_checkup__tablet__question.png
tests/visual/visual-baseline/screenshots/tablet/business_checkup__tablet__result.png
tests/visual/visual-baseline/screenshots/mobile/business_checkup__mobile__landing.png
tests/visual/visual-baseline/screenshots/mobile/business_checkup__mobile__question.png
tests/visual/visual-baseline/screenshots/mobile/business_checkup__mobile__result.png
tests/visual/visual-baseline/screenshots/mobile/business_checkup__mobile__result-mobile.png
```

### Defects found and fixed during the GREEN phase (all real, all caught by the tests)

| # | Symptom | Cause | Fix |
|---|---|---|---|
| 1 | Nothing rendered; `#checkup-start` never appeared | I called a `clear(state)` helper that was never defined → `ReferenceError` at first render | Added `clearStage()` and replaced the three calls |
| 2 | `findings: gaps are ordered by size of shortfall` failed | Findings were emitted in question order, not shortfall order | Sort gaps by `pointsLost` desc with question order as tie-break |
| 3 | `question set: … maxPoints` failed | Question data had no declared maximum | Declared `maxPoints` on all eight questions + a test asserting it matches the highest option |
| 4 | Predetermined-result test failed | My test asserted a label string that did not match the option text | Corrected the fixture expectation (implementation was right) |
| 5 | Mobile restart test: click intercepted | The site's chat launcher (`#ai-chatbot-root`, `z-index:9999`, fixed bottom-right) covered the button; a retry then hit the open chat panel's input | Removed the mobile sticky bar and added mobile bottom padding so the checkup's actions clear the launcher |

---

## D. Regression evidence

Existing funnel, unchanged — asserted against the **live** site (`CHECKUP_REGRESSION_BASE`),
because `contact.js` calls `crypto.randomUUID()` and therefore needs a secure context:

```
[61/63] [mobile] › regression: the homepage hero campaign CTA still swaps on UTM arrival        ✓
[62/63] [mobile] › regression: the campaign redirect still resolves to the homepage with UTM parameters ✓
[60/63] [mobile] › regression: checkup assets are not loaded on unrelated pages                ✓
```

- Hero: `/?utm_source=status&utm_campaign=ep001` still swaps the button to "Tell me your
  problem"; plain `/` still shows "View My Projects".
- Campaign: `https://api.imadconsulting.co.uk/r/status?campaign=ep001` still returns **307**
  with `utm_source=status` and `utm_campaign=ep001` in the `Location`.
- Scope: the homepage loads **no** `business-checkup` asset — confirmed in the browser *and* by
  grepping live HTML:

```
$ for u in https://imadconsulting.co.uk/ https://imadconsulting.co.uk/blog; do curl -s "$u" | grep -c 'business-checkup'; done
  https://imadconsulting.co.uk/              business-checkup references: 0
  https://imadconsulting.co.uk/blog          business-checkup references: 0
```

- No GSAP dependency: with the whole animation pipeline blocked at the network layer
  (`gsap|ScrollTrigger|TextPlugin|/animations/`), the checkup still completes and renders its
  result. (`window.gsap` is `undefined` in that run.)
- PHP clean: the checkup page emits **no** `Warning:`/`Notice:`/`Deprecated:`/`Fatal error` with
  `WP_DEBUG` on locally.
- PHP 7.0 floor: `php -l` clean on all three PHP files. The new code uses no 7.1+ syntax; the
  only `??` occurrences are pre-existing lines (null coalescing is PHP 7.0).
- Styles confirmed applied from real tokens (computed styles, desktop): body
  `oklch(0.12 0.012 70)`, option card `oklch(0.15 0.012 70)` + `--border` + `10px` radius +
  `44px` min-height, legend `"Instrument Serif", Georgia, serif` 28px, next button
  `oklch(0.62 0.2 25)` on `oklch(0.98 0.01 70)` at 44px, `business-checkup.css` in
  `document.styleSheets`. No element whose text colour equals its own background.
- Visual regression: the new spec produced its own captures; **no unrelated baseline was
  written**. The shared navigation baselines are stale by design (one new menu item) and are
  deliberately *not* regenerated here — see §F.

---

## E. Final git evidence

```
$ git status --short
 M .htaccess                                        ← pre-existing (HO-058), not touched here
 M malachy-portfolio/functions.php
 M malachy-portfolio/template-parts/navigation.php
?? malachy-portfolio/assets/css/business-checkup.css
?? malachy-portfolio/assets/js/business-checkup.js
?? malachy-portfolio/template-business-checkup.php
?? tests/unit/
?? tests/visual/business-checkup.spec.ts
?? docs/handover/HO-057…, HO-058…, HO-059.md, HO-060…, HO-061.md, HO-062…, HO-063…, docs/ops/

$ git diff --stat
 .htaccess                                       | 22 ++++++++++++++++++++++
 malachy-portfolio/functions.php                 | 20 ++++++++++++++++++++
 malachy-portfolio/template-parts/navigation.php |  3 ++-
 3 files changed, 44 insertions(+), 1 deletion(-)

$ git diff --check
  (no output — clean)
```

Focused diff, navigation (one line, matching the theme's existing conventions):

```diff
@@ -12,7 +12,8 @@ $nav_links = array(
 	array( 'href' => '/#contact', 'label' => __( 'Contact', 'malachy-portfolio' ), 'section' => 'contact' ),
 	array( 'href' => '/blog', 'label' => __( 'Blog', 'malachy-portfolio' ), 'route' => true ),
+	array( 'href' => '/business-checkup/', 'label' => __( 'Business Checkup', 'malachy-portfolio' ), 'route' => true ),
 );
```

Focused diff, enqueue (additive, guarded):

```diff
@@ -203,6 +203,26 @@ function malachy_enqueue_assets() {
 		true
 	);
+
+	// Business Checkup — page-scoped only (HO-063 §16). No GSAP, never site-wide.
+	// is_page_template() covers the assigned template; is_page() covers a page slug that
+	// exists without the template being assigned yet.
+	if ( is_page_template( 'template-business-checkup.php' ) || is_page( 'business-checkup' ) ) {
+		wp_enqueue_style( 'malachy-business-checkup', MALACHY_THEME_URI . '/assets/css/business-checkup.css', array( 'malachy-main' ), MALACHY_THEME_VERSION );
+		wp_enqueue_script( 'malachy-business-checkup', MALACHY_THEME_URI . '/assets/js/business-checkup.js', array(), MALACHY_THEME_VERSION, true );
+	}
 }
```

**No commit, no push, no deployment was performed.** Per the project's rule, committing is
gated on your go-ahead.

---

## F. Manual steps

Nothing here was done for production; each is a human action.

1. **Create the production page** (WP admin → Pages → Add New, or WP-CLI):
   - Title `Business Checkup`, slug `business-checkup`, Template `Business Checkup`, published.
   - WP-CLI equivalent:
     `wp post create --post_type=page --post_title='Business Checkup' --post_name='business-checkup' --post_status=publish --page_template='template-business-checkup.php'`
   - Already done on the **local clone only** (page ID 237) so the browser tests could run. The
     local clone's `wp-cli.phar` was copied to `/tmp` inside `malachy-wp` to do it.
2. **Deploy**: push to GitHub, then your manual `git pull` on the InMotion host (the standing
   process). The page must exist before the nav link is useful.
3. **Purge Cloudflare** for the HTML after the pull.
4. **Regenerate the shared visual baselines** — the nav item changes every page's header. This
   is the intentional baseline change HO-063 §23 asks to document. Suggested: `npm run
   visual:capture` against production once deployed, and review the diff.
5. **Do not run the full capture suite with the checkup page missing** — `business-checkup.spec.ts`
   asserts the page exists. Once the page is live it passes; before then, either run it against
   a local instance as in §C or exclude it with `--grep-invert "business checkup"`.

Test-environment notes (no repo or compose change was made):
- The local clone pins its URL via `WORDPRESS_CONFIG_EXTRA` (WP_HOME/WP_SITEURL constants), so
  `wp option update home` has no effect. Rather than touch compose, the spec takes an opt-in
  origin rewrite (`CHECKUP_REWRITE_FROM` / `CHECKUP_REWRITE_TO`), which is inert unless set.
- The local clone's DB values for `home`/`siteurl` are unchanged (verified after the run).
- `tests/visual/config/visual.config.ts` points at **production** by default; the checkup runs
  used `VISUAL_BASE_URL=http://172.18.0.11` (the local container).
- Local scratch scripts used for diagnosis live outside the repo in
  `~/.hermes/cache/scratch/` (`smoke-checkup.js`, `qa-styles.js`, `diag-hero.js`) and are not
  part of the deliverable.

---

## G. Known limitations

- **No website technical checker** (no server-side URL fetching; no SSRF surface added).
- **No email/DNS checker** (no SPF/DKIM/DMARC/MX lookups).
- **No AI report** — no AI call anywhere, and no second provider introduced.
- **No analytics** — no tracking endpoint, no third-party tag. `checkup_*` events remain
  unwired (see HO-062 §16 for the event model and the cheapest future mechanisms).
- **No server-side diagnostic storage** — answers are never sent anywhere; the handoff is the
  existing contact form, so the lead carries the service slug and whatever the visitor writes,
  not the structured answers.
- **`npm test` is still the pre-existing failing stub** (`echo "Error: no test specified" &&
  exit 1`). The unit suite runs via `node --test tests/unit/*.test.js`. Wiring `npm test` to it
  is a one-line `package.json` change, deliberately **not** made here because `package.json` is
  outside HO-063 §26's declared surface — an OPEN item.
- **Eight questions, six scored** — two are deliberately unscored (segmentation + routing).
- Observations recorded, **not** acted on (all outside HO-063 §2 scope):
  1. `contact.js` calls `crypto.randomUUID()` unguarded, which throws on any non-secure origin
     (`pageerror: crypto.randomUUID is not a function` over plain HTTP), killing the hero CTA
     swap and the form's idempotency key generation. Production is HTTPS so this is latent, not
     live — but it means the contact funnel silently degrades on any HTTP origin.
  2. The chat launcher overlaps page content at the bottom-right on mobile for *any* page with
     controls there — the checkup now pads around it, but the underlying widget has no such
     allowance.
  3. Per HO-060, `leads.status` is never written, so the follow-up reminder can never fire.

---

## H. Acceptance checklist (HO-063 §28)

| # | Item | Status |
|---|---|---|
| 1 | Dedicated WordPress template exists | **PASS** — `template-business-checkup.php` |
| 2 | Eight questions implemented | **PASS** |
| 3 | Q1/Q8 unscored | **PASS** (unit: `stage`/`priority` `scored:false`) |
| 4 | Q2–Q7 scoring correct | **PASS** (per-answer unit test) |
| 5 | Business Health scoring correct | **PASS** |
| 6 | WhatsApp scoring correct | **PASS** |
| 7 | Overall scoring correct | **PASS** |
| 8 | Band boundaries tested | **PASS** — every boundary incl. 3/4, 6/7, 5/6, 10/11, 2/3, 4/5 |
| 9 | Priority tie-breaking deterministic | **PASS** — largest shortfall, then fixed question order |
| 10 | Result appears before contact details | **PASS** — asserted: no email/name/textarea on the page |
| 11 | Result identifies itself as a self-assessment | **PASS** — `#checkup-disclaimer` asserted |
| 12 | All required result elements present | **PASS** — title, overall, both dimension scores, bands, explanation, 3–5 findings, priority, next step, offer, both CTAs, restart |
| 13 | Existing contact flow reused | **PASS** — `?service=<slug>…#contact` |
| 14 | No new lead endpoint | **PASS** |
| 15 | UTM source/campaign survive to the contact CTA | **PASS** — asserted with and without UTM |
| 16 | Existing WhatsApp destination reused | **PASS** — `get_option('malachy_whatsapp')`, `wa.me`, digits-only |
| 17 | One navigation entry added | **PASS** |
| 18 | Hero behaviour unchanged | **PASS** — asserted against the live site |
| 19 | JS/CSS page-scoped | **PASS** — asserted no checkup asset on the homepage |
| 20 | No GSAP dependency added | **PASS** — completes with the animation pipeline blocked |
| 21 | Mobile layout works | **PASS** — 44px targets, no horizontal overflow |
| 22 | Keyboard navigation works | **PASS** — full run by keyboard only |
| 23 | Accessibility requirements met | **PASS** — fieldset/legend, labels, focus on step change, `aria-live`, `role="progressbar"`, inline `role="alert"` error, reduced-motion via the existing global rule, 44px targets |
| 24 | Required validation works | **PASS** — blocks with inline message; Q8 skippable |
| 25 | No server/database changes | **PASS** |
| 26 | No third-party dependencies | **PASS** — `node:test` is built in; no `package.json` change |
| 27 | Unit tests pass | **PASS** — 34/34 (via `node --test`; `npm test` not wired — see §G) |
| 28 | Playwright tests pass | **PASS** — 61 passed, 2 skipped (mobile-only captures), 0 failed |
| 29 | Visual regression passes | **PASS with follow-up** — new captures produced and asserted; the 15 shared baselines are stale because of the nav item and need a deliberate regeneration (§F.4) |
| 30 | Relevant regression tests pass | **PASS** — hero swap, campaign 307 redirect, asset scoping |
| 31 | No console errors | **PASS** — asserted 0 on the checkup page |
| 32 | Raw test output included | **PASS** — §C, §D, §E |
| 33 | `git diff --check` passes | **PASS** |
| 34 | Final diff reviewed for unintended changes | **PASS** — exactly 1 nav line + 20 additive enqueue lines; `.htaccess` is pre-existing |
| 35 | No deployment claimed unless performed | **PASS** — none performed, none claimed |

### Blocked / open

- **BLOCKED (expected): production page creation, deployment, Cloudflare purge** — all require
  your action; the page cannot exist in production until you create it (§F.1–3).
- **OPEN: regenerate the shared visual baselines** after the nav change (§F.4).
- **OPEN: wire `npm test`** to the unit suite (`package.json` is outside the declared surface).
- **OPEN: nav placement** — appended after `Blog` to keep the existing order intact; move it if
  you want it grouped with `Contact`.
- **OPEN (carried from HO-062 §20):** handoff upgrade A′ vs B, `diagnostic_data` JSONB, analytics
  mechanism, AI provider, result-copy sign-off, WhatsApp service slug.
- **Not in scope, still outstanding:** the HO-058 repo-exposure fix (`.htaccess` + HO-058)
  remains uncommitted and unshipped — production is still serving `docker-compose.yml`.
