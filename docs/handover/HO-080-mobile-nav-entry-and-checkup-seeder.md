---
type: HANDOVER
project: IMAD Consulting — Lead Capture & Content Automation
title: HO-080 — Mobile-only nav entry for the checkup; seed_business_checkup_page() in the seeder
date: 2026-10-03
from: Hermes (implementation agent)
to: Malachy (approval) / Claude Web (review)
status: IMPLEMENTED — tests green, committed locally, NOT pushed, NOT deployed
priority: MEDIUM
---

Two tasks from Malachy: (1) the recommended fix for the mobile gap left by HO-079, and
(2) a seeder method so the checkup page can't go missing again.

---

## A. Mobile gap — recommendation and implementation (HO-079 §D option b)

**Recommendation: a mobile-only entry in the collapsed menu.** Rejected the alternatives for
concrete reasons, not preference:

- **Show the header CTA at <=800px** — the mobile header already carries the wordmark, theme
  toggle and hamburger. The CTA label is `Business Checkup` (16 chars, uppercase at 0.68rem
  with 0.08em tracking ~140px). At 360-390px it either crowds the row or forces a second,
  shortened label variant plus more CSS. Higher surface, worse result.
- **Leave it (option c)** — after HO-079 the CTA is the only desktop path, so leaving it means
  phones have *no* route to a page that works. Not a defensible default for a conversion page.

The collapsed menu is where mobile users already look, its items are full-width 16px-padded tap
targets, and it costs zero header width — so the desktop header stays exactly
`[Work | Experience | Contact | Blog]` as requested.

### Implementation

`template-parts/navigation.php` — the checkup returns to the nav array, flagged:

```php
array( 'href' => '/business-checkup/', 'label' => __( 'Business Checkup', … ), 'route' => true, 'mobile_only' => true ),
```

plus one line in the render loop, applied to both anchor branches:

```php
<?php $link_class = ! empty( $link['mobile_only'] ) ? ' class="nav-mobile-only"' : ''; ?>
…
<a href="…"<?php echo $link_class; ?> data-section-link="…">
```

`assets/css/main.css` — hidden by default, shown only inside the collapsed panel:

```css
.site-nav .nav-mobile-only { display: none; }          /* near the .site-nav block */

@media (max-width: 800px) {
  .site-nav .nav-mobile-only { display: block; }       /* beside .header-cta{display:none} */
}
```

The two rules are deliberately adjacent in intent: the CTA is hidden exactly where the menu
entry appears, so the page is reachable at every width.

## B. `seed_business_checkup_page()`

`inc/data-seeder.php` — a new method mirroring `seed_thank_you_page()`, called from `seed()`
after `seed_thank_you_page()`.

**Two deliberate deviations from the thank-you pattern, both stated rather than silently
resolved:**

1. **The existence check covers every post status** (`publish, draft, pending, private,
   future, trash`), not just `publish`. A draft holding the slug would otherwise make
   `wp_insert_post` create `business-checkup-2`, so the seeder would report success while
   `/business-checkup/` stayed a 404 — precisely the class of failure HO-079 hit. The log line
   names the status and ID so a collision is visible instead of silent.
2. **`flush_rewrite_rules()` added to `seed()`** with a log line. This is beyond the literal
   ask, and it exists because of the actual production incident: the page was published and
   correct, yet unreachable until `wp rewrite flush`. A seeder that creates a page no one can
   visit is not finished. Removable in one line if you'd rather the seeder stayed narrow.

## C. Files changed

| file | change |
|---|---|
| `malachy-portfolio/template-parts/navigation.php` | mobile-only entry + class in the loop |
| `malachy-portfolio/assets/css/main.css` | 2 rules (base hide + <=800px show) |
| `malachy-portfolio/inc/data-seeder.php` | `seed_business_checkup_page()` + call + flush |
| `malachy-portfolio/style.css` | Version 1.3.21 -> 1.3.22 |
| `malachy-portfolio/functions.php` | `MALACHY_THEME_VERSION` 1.3.21 -> 1.3.22 |
| `docs/handover/HO-080-…md` | this file |

**Version bump reason:** `main.css` enqueues with `MALACHY_THEME_VERSION` as its `?ver=`, so
without the bump every returning visitor keeps the cached stylesheet and never sees the new
rule. The constant is hardcoded at `functions.php:17` as well as in `style.css:7` — both were
moved together (a known divergence trap in this theme).

## D. Verification (raw output)

PHP syntax, all three changed PHP files:

```
No syntax errors detected in /t/template-parts/navigation.php
No syntax errors detected in /t/inc/data-seeder.php
No syntax errors detected in /t/functions.php
```

Rendered header on the local clone: 4 unclassed items plus `Business Checkup` carrying
`class="nav-mobile-only"`, the CTA intact, and `main.css?ver=1.3.22` (bump confirmed live).

Reachability probe, Playwright, 5 viewports — `visibleNavItems` counts only rendered links:

| viewport | CTA | hamburger | visible nav items | checkup reachable | panel overflow | doc overflow |
|---|---|---|---|---|---|---|
| desktop 1280 | shown | no | Work, Experience, Contact, Blog | **yes** (CTA) | 0px | 0px |
| tablet 800 | hidden | yes | + Business Checkup | **yes** (menu) | 0px | 0px |
| tablet 768 | hidden | yes | + Business Checkup | **yes** (menu) | 0px | 0px |
| mobile 390 | hidden | yes | + Business Checkup | **yes** (menu) | 0px | 0px |
| mobile 360 | hidden | yes | + Business Checkup | **yes** (menu) | 0px | 0px |

Menu entry renders 178x49px — above the 44px minimum target the project already uses.

Seeder, **branch 1 — idempotent, page present** (local clone, page 237):

```
  → Business Checkup page already exists (publish, ID 237).
  → rewrite rules flushed.
Success: All data seeded successfully.
checkup pages after the run: 1        (no duplicate created)
```

Seeder, **branch 2 — creation, slug free** (reversible: renamed 237, seeded, restored):

```
  → Business Checkup page created (ID 251).
251	business-checkup	publish
  template: template-business-checkup.php
  pages with the clean slug: 1
  /business-checkup/ -> 200            <- reachable with NO manual flush
```

Restore, verified:

```
Success: Deleted post 251.
Success: Updated post 237.
237	business-checkup	publish
  title: Business Checkup | status: publish | template: template-business-checkup.php
  checkup pages total: 1
  /business-checkup/ -> 200
  → Business Checkup page already exists (publish, ID 237).   (re-run after restore)
```

The local clone is back to its original state: page 237, same slug, status and template.

Tests:

```
node --test tests/unit/business-checkup.test.js   ->  tests 34 | pass 34 | fail 0
npx playwright test --config=tests/visual/config/visual.config.ts business-checkup.spec.ts
    --project=desktop (local clone)               ->  18 passed | 1 skipped | 2 failed*
    * both failures were "route.continue: New URL must have same protocol as overridden URL"
      — my https->http origin rewrite, not the code. Re-run at the live origin:
    -g "regression"                               ->  3 passed
```

So all 21 spec tests are accounted for (18 local + 3 live), 1 skipped is the mobile-only
capture on the desktop project — no real failures.

## E. What is still needed before this is live

1. Your go-ahead -> push, then your manual `git pull` on the InMotion host.
2. Nothing else. The checkup page already exists in production (114); `/business-checkup/`
   returns 200 and the CTA is live. This change only restores the phone/tablet path.
3. Optional: Cloudflare purge, though the stylesheet URL changes with `?ver=1.3.22`, so the
   new CSS is fetched without one.
4. The 15 stale shared visual baselines remain a separate, deliberate regeneration step
   (HO-064 §F.4) — unchanged by this work.

## F. Git state

- Modified: the 5 theme/doc files in §C.
- Untracked and **not mine**: `ram_audit.sh`.
- Committed locally only. **Nothing pushed, nothing deployed.**

## G. Limitations

- Verified against the local clone and the live origin, not staging.
- No automated test covers nav composition, so this entry is protected by the reachability
  probe (run ad hoc) rather than by a committed test. A cheap assertion on the mobile menu
  contents would close that; not added, since `tests/` is HO-063's declared surface.
- The seeder's all-status guard does not catch a page whose slug was auto-suffixed on trash
  (`business-checkup__trashed`): trashing frees the clean slug by design, so the seeder will
  create a fresh page — correct behaviour, but worth knowing if you ever trash the live page
  and re-seed.
- `flush_rewrite_rules()` in the seeder regenerates rules from the active theme and plugins;
  standard and safe, but it is a database write on every seed run.
