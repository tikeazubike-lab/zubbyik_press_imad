---
type: HANDOVER
project: IMAD Consulting — Lead Capture & Content Automation
title: HO-050 — Work section converted to swipe/autoplay carousel (v1.3.16); wp_mail inbox delivery PROVEN (closes item 2)
date: 2026-09-23
from: MiMo-V2.6-Pro (Architect) / MiMo-V2.6-Flash (Implementer)
to: Claude[Sonnet] Web (Reviewer) / ChatGPT (Co-reviewer)
status: COMPLETE on staging — NOT committed, NOT pushed, NOT deployed to production (per Malachy: everything stays local/staging until go-ahead)
priority: HIGH
---

## 0. Decisions locked (Malachy, 2026-09-23)

| Question | Answer |
|---|---|
| Autoplay | **Desktop only** (≥801px): 5s autoplay + draining countdown, pause on hover/focus/touch. **Mobile: never auto** |
| Card content | The **6 projects from `docs/handover/carousel_showcase.html`**, seeded into the `project` CPT (admin-editable, scales to 20+) |
| Images | **Full-card background**, text overlaid with gradient scrim |
| Mobile controls | **Swipe only + live counter** — arrows/dots/countdown hidden below 801px |

Repo policy this session: **no `git commit`, no `git push`, no GitHub.**
Everything lives in the working tree + staging (bind-mounted = live there).

## 1. Also in this unit: outstanding item 2 CLOSED (wp_mail inbox proof)

Malachy delivered the received-email screenshot:
**`assets/tests/gmail_DiscTestScreenshot.png`** (folder untouched, as instructed).

It shows `[Malachy Portfolio] Contact from DiscTest` in the inbox — the exact
test submission from HO-049 §4b (name DiscTest, message
`legacy-nonce-name`, received 19:16). This is the received-email proof that
was the last open requirement for context outstanding item 2 → **CLOSED**.

Flagged (not fixed, out of scope): the email's From display name is the
stale production `blogname` (`Malachy – QA Engineer, SysAdmin & IT Support
Specialist`) — `wp_mail` uses blogname as sender name.

## 2. Image pipeline

7 source PNGs in `assets/` (1.3–1.9 MB each; `assets/tests/` untouched).
They are marketing/infographic banners (email deliverability, website
migration, spam) — thematically **offer-ish, not project-ish**; used as
decorative backgrounds per decision. 6 of 7 mapped to cards, 1 spare:

| Card | Source PNG | Theme asset (WebP ×3) |
|---|---|---|
| EPM Test Taxonomy | `Gemini_..._vl5b7rvl5b7rvl5b.png` | `project-test-taxonomy{,-600w,-900w}.webp` |
| Self-Hosted Service Stack | `ChatGPT Image Jun 29...website_migation...png` | `project-service-stack{,...}.webp` |
| WordPress Chatbot-Readiness | `ChatGPT_img_jun28_email_deliverabiity_2.png` | `project-chatbot-readiness{,...}.webp` |
| EPM v2 | `Gemini_..._email_deliverabilty.png` | `project-epm-v2{,...}.webp` |
| Specforge | `ChatGPT_img_jun28_email_deliverabiity.png` | `project-specforge{,...}.webp` |
| IMAD Automation Platform | `Gemini_..._website_migration_zero_downtime.png` | `project-imad-automation{,...}.webp` |
| — spare | `Gemini_..._stopped_important_mails_landing_into_spam.png` | unused |

Converted with `cwebp -q 80` at 1000w/900w/600w → 17–90 KB each (18 files,
all non-zero, verified). Sources left untouched in `assets/`.

**Deviation**: image→card mapping was proposed from filenames and confirmed
against actual image content during implementation; all six are
text-heavy banners — under the full-card-background decision the text
overlay sits on a dark gradient scrim (`rgba(10,11,14,.2→.92)`), white
card text. Legibility verified in screenshots; if copy-vs-banner clashes
annoy, swap mappings or soften scrim — iterate per Malachy.

## 3. Code changes (all `php -l` / `node --check` clean)

- **`inc/data-seeder.php`** — `seed_projects()`: 3 entries → the 6 prototype
  projects (tag/tech/url/excerpt verbatim from the prototype's JS array;
  `url='#'` → template falls back to `/#contact`; `github` kept). Log line
  updated. Idempotent by title (existing `create_posts` skip).
- **`template-parts/section-projects.php`** — rewritten as carousel:
  keeps `#work`, `.section-wrap`, `.projects-top`, `.projects-heading`
  (nav/hero anchors depend on them); `.project-list` vertical stack →
  `.countdown-track` + `.carousel[data-carousel] > .track[data-track]` with
  server-rendered `.card`s (featured image as absolute `cover` `.card-bg`
  + `.card-scrim` + `.card-content`: tag/title/desc/stack/View project)
  + `.controls` (6 dots + prev/next). Live counter `01 / 06` in the
  existing `.project-count` slot. ARIA: `role=region`,
  `aria-roledescription=carousel`, per-slide `aria-label="N of 6: Title"`,
  dots `role=tab`. Fallback array (empty CPT) retained.
- **`assets/css/main.css`** — `.project-list`/`.project*` block (desktop
  + mobile) replaced with carousel styles on theme tokens (`--card`,
  `--line`, `--primary`, `--radius`, `--font-serif`); card text fixed
  white-on-scrim (works in both light/dark themes). Mobile
  (`max-width:800px`): `.controls` + `.countdown-track` hidden, counter
  stays. `prefers-reduced-motion`: instant scroll, no countdown drain.
- **`assets/js/animations/projects.js`** — rewritten vanilla (no GSAP):
  dots/arrows/keyboard ←/→ → `scrollTo`; scroll listener (120ms debounce)
  syncs current+counter+dots after manual swipe; autoplay gated behind
  `matchMedia('(min-width:801px)') && !reducedMotion`, pauses on
  hover/focus/touchstart, stops at last slide; `matchMedia` change listener
  stops/starts it across the breakpoint. Heading `.reveal` fade kept
  (gated off under reduced motion). Enqueue untouched.
- **Version → 1.3.16** (`MALACHY_THEME_VERSION` + `style.css`).

Shared-code check (standing rule): single-caller all the way (`front-page`
→ `#work` only); consumers of `#work` = nav link, hero role links, CTA,
`navigation.js` section list — all preserved. Forms/chatbot/contact.js
untouched. HO-012 timeline rule N/A (no pinned GSAP here).

## 4. Staging reseed + verification (raw output)

Old 3 posts deleted, seeder run, final state:

```
count=6
1 | EPM Test Taxonomy — Automated Test Framework | thumb=yes ... tag=Testing tech=pytest,...
2 | Self-Hosted Service Stack | thumb=yes ... tag=DevOps
3 | WordPress Chatbot-Readiness Assessment | thumb=yes ... tag=WordPress
4 | EPM v2 — Estate Portfolio Manager | thumb=yes ... tag=Full-stack
5 | Specforge | thumb=yes ... tag=Tooling
6 | IMAD Consulting Automation Platform | thumb=yes ... tag=Automation
```

Reseed churn left 45 orphaned attachments → cleaned (kept: 6). A cleanup
bug briefly deleted the physical files shared with live attachments
(all reseeds overwrite the same upload path) → thumbnails rebuilt from
theme assets; final check:

```
project attachments: 6
all: file-OK ...url=.../uploads/2026/09/project-*.webp
HTTP: 200 ×6
```

Markup (staging curl): 6 × `class="card"`, `ver=1.3.16` (contact.js +
main.css), `data-carousel/data-track/controls/dots/data-prev/data-next`,
counter `01 / 06`, 6 dots, countdown present, old classes absent (0),
`<section id="work">` intact.

### Browser verification (Playwright, staging)

**Desktop 1280×800** — zero console errors. Screenshot:
`desktop-carousel-element.png` (full-bleed image + scrim + white overlay
text, tag pills, serif titles, stack chips, View project links).
- Autoplay ran live: counter observed advancing across `01→…→06/06`
  while page sat; next disabled at 06, prev at 01 (end-stops work).
- Prev/next clicks navigate; dot jump → `01/06`, scroll 0; keyboard
  ArrowLeft/Right changes slide; counter↔dot↔scroll stay in sync.
- Note: at 1280px the track's max scroll is exactly 946px
  (6×340 + 5×18 − container); cards 4–6 targets clamp there — same math
  as the prototype, not a defect.

**Mobile 375×812** — `controls: none`, `countdown: none`,
counter visible `04/06`, card width 293px = `min(78vw,360)`.
**No-autoplay: counter unchanged across 8.2s wait** (`noAutoplay: true`).
Swipe-sync: programmatic track scroll to card 4 → counter `04/06`,
dot 3, scroll == target (932). Screenshot: `mobile-carousel.png`.

**Reduced motion (emulated)** — `reduced: true`, controls still visible on
desktop (`flex`), counter unchanged across 6.2s → no autoplay.

## 5. Deviations from plan/statement

1. Autoplay breakpoint implemented at **801px** (CSS mobile block is
   `max-width:800px`); my earlier message said 768px — using 801 keeps
   the JS gate and the CSS hide-rule on the exact same edge.
2. Starter images are banner art with baked-in text (offer-themed), not
   project screenshots — flagged in §2; iterate if unsatisfied.
3. Production deploy of 1.3.15+1.3.16 remains a **separate tracked step**
   (production still serves v1.3.14) — blocked on Malachy's go-ahead.

## 6. Findings flagged, NOT fixed

- Email From name = stale production blogname (§1).
- `assets/` spare image unused; swap freely.
- Settings fields `malachy_phone`/`malachy_whatsapp`/`malachy_twitter`
  still missing `register_setting` (HO-049 finding, unchanged).
- Chatbot persona strings still say "SysAdmin" (HO-049 finding, unchanged).

## 7. Next steps (awaiting Malachy)

1. Review staging (`imadconsult.zubbystudio.site`) — iterate on
   visuals/mappings/copy as needed.
2. On go-ahead: `git commit` (theme v1.3.16 + HO-048/049/050 + record
   corrections) — nothing pushed before that.
3. Production deploy (1.3.15 title/meta/GSC + 1.3.16 carousel together) —
   own tracked step with own verification; production reseed of the 6
   projects included in that step.

## 8. Iteration 1 (2026-09-23, Malachy): background images removed

Cards are now **text-only**, matching `carousel_showcase.html` exactly —
Malachy reviewed the image-backed version on staging and asked for the
images out. Changed:

- `section-projects.php`: dropped the `card-bg`/`card-scrim` markup and
  the `image`/`image_id` data collection (featured-image fallback code
  removed with it).
- `main.css`: removed `.card-bg`/`.card-scrim` rules and the fixed-white
  overlay text colors; cards now use theme tokens — surface
  `--secondary`, text `--foreground`/`--ink-soft`, tag pill
  `--primary` on `--background`, stack chips `--line` border; `min-height`
  dropped (content-sized, equal-height via flex row stretch).
- JS/controls/autoplay/mobile-swipe behavior: **untouched** (all §4
  verification still applies).

Kept for easy restore (per "for now"): the 18 WebP files in
`malachy-portfolio/assets/images/`, the seeder's featured-image
assignments, and the source PNGs in `assets/`.

Re-verified on staging: `php -l` clean; live DOM shows 0 images / 0
scrims in cards, computed colors = theme tokens, 6 cards, counter
`01 / 06`; screenshot `text-cards-v2.png`. Note: `main.css?ver=` stayed
1.3.16 within the same unreleased unit, so browsers with a cached copy of
the earlier 1.3.16 CSS must hard-refresh (no user impact post-deploy).
