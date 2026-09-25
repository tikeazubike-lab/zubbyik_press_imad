---
type: HANDOVER
project: IMAD Consulting — Lead Capture & Content Automation
title: HO-052 — Experience duplicates removed (+ helper title-match bug), section rhythm/spacing fixes, jobs-showcase overhaul (§ fold-in)
date: 2026-09-24
from: MiMo-V2.6-Pro (Architect) / MiMo-V2.6-Flash (Implementer)
to: Claude[Sonnet] Web (Reviewer) / ChatGPT (Co-reviewer)
status: COMPLETE on staging (v1.3.20) — NOT committed, NOT pushed, NOT deployed to production
priority: HIGH
---

## 0. Repo policy this session (unchanged)

No `git commit`, no `git push`, no GitHub. Everything lives in the working
tree + staging (bind-mounted theme = live there).

## 1. Reported issues (Malachy, 2026-09-24)

1. Duplicate work-experience entries in the Experience section.
2. The testimonial section "jammed into" the experience section — some
   experience content appearing inside the testimonial section.

## 2. Root cause — experience duplicates (raw evidence)

DB state before fix:

```
=== experience count=15 ===
47  | order=1 | 2026-09-17 12:33:32 | name=founder-systems-qa-consultant   | RAW=[Founder &amp; Systems/QA Consultant]
75  | order=1 | 2026-09-23 21:01:36 | name=founder-systems-qa-consultant-2 | RAW=[Founder &amp; Systems/QA Consultant]
88  | order=1 | 2026-09-23 21:01:39 | name=founder-systems-qa-consultant-3 | RAW=[Founder &amp; Systems/QA Consultant]
... (101, 114, 127, 141, 153, 166 = 9 copies total)
48..53 | orders 2..7 | unique roles
```

Stored title is **entity-encoded** (`&amp;`). Probe through WP bootstrap:

```
$ wp_insert_post(['post_title' => 'Probe & Test'])  →  raw=[Probe &amp; Test]
title_save_pre: HAS FILTERS
   10 trim
   10 wp_filter_kses      <-- encodes & -> &amp;
```

`create_posts()` matched existing posts with `get_posts(['title' => $item['title']])`
— an exact match against the raw DB title. For the one seeded title containing
`&`, `'Founder & Systems/QA Consultant'` never equals the stored
`'Founder &amp; Systems/QA Consultant'`, so every seed/reconcile run appended
another copy. The other 6 titles have no `&` and matched/skipped correctly,
which is why only the first entry duplicated. 8 copies were created in an
11-second window (2026-09-23 21:01:36–47) — repeated seed runs in the HO-050
session.

Checked the same latent bug elsewhere (raw query):

```
=== page duplicates on title === (none)
=== project duplicates on title === (none)
=== testimonial duplicates on title === (none)
```

Offer pages / thank-you page already matched by slug (`name`), not title —
hence unaffected.

## 3. Fix — code

**`inc/data-seeder.php`**

- `create_posts()` now matches existing posts by **slug**
  (`'name' => sanitize_title($item['title'])`) instead of raw title, with
  `post_status => 'any'`. Entity encoding can no longer break idempotency for
  any title, any CPT.
- New `dedupe_cpt( $post_type )` — deletes posts sharing a sanitized title,
  keeping the lowest ID (the original).
- `seed_experience()` calls `dedupe_cpt('experience')` before the idempotent
  upsert (self-healing on every run).
- New public `migrate_experience()` (CLI: `wp malachy migrate_experience`) —
  dedupe + re-seed.

## 4. Fix — verification (raw output)

Ran `migrate_experience()` once, then `migrate_experience()` + full `seed()`
twice more to prove the old "append on every run" behaviour is gone:

```
AFTER 2 MORE RUNS count=7

47 | order=1 | Founder &amp; Systems/QA Consultant | IMaD Consulting
48 | order=2 | Test Analyst / UAT | Imad Consulting (clients: zubbystudio, Okra Technology)
49 | order=3 | Test Analyst / Front-End Development | Imad Consulting (client: Fitzdanuk.org)
50 | order=4 | Test Analyst (Planixs) | Planixs (clients: Barclays, RBS, Vodafone, Zenith Bank)
51 | order=5 | Test Analyst (Parcel Force / NatWest / Quick Light) | ...
52 | order=6 | Desktop Support Engineer | British Telecoms
53 | order=7 | Network Administrator | Admiral Insurance
```

Live staging HTML:

```
curl -s .../?v=dedupe | rg -c 'class="experience-item'   -> 7
curl -s .../?v=dedupe | rg -c 'Founder &amp; Systems/QA Consultant' -> 1
```

## 5. Issue 2 — testimonial/experience seam

Measured before fix: `#experience` height **4384px** (15 items),
`#experience` bottom == `#testimonials` top (no gap), and `#experience` had
**`padding-bottom: 0`** — the timeline line (`.experience-line`, `bottom:0`)
and the last item ran flush into the pinned testimonials section, which opens
as a dark void until its background fades in. Screenshot:
`ho052-seam-after-dedupe.png` (experience tail + orange line directly above the
centred "07 WHAT CLIENTS SAY" label in blackness).

Fix (`assets/css/main.css`):

```css
#experience.experience {
  padding-top: clamp(100px, 5vw, 190px);
  padding-bottom: clamp(120px, 12vw, 220px);  /* separates timeline from pinned testimonials */
}
```

After: `#experience` height **2305px** (7 items); `#testimonials` top moved
6447 → 6619 (desktop). Desktop + mobile seam screenshots:
`ho052-seam-desktop-after.png`, `ho052-seam-mobile-after.png` — timeline ends,
clear gap, then the testimonials label.

Pinned testimonials still verified working after the change:
`active: true`, `isPinned: true`, `staticHidden: none`, `expItems: 7`,
0 console errors; background + typewriter render (`ho052-pin-final.png`).

**Note (design, not a bug):** the testimonials section is a `pin: true`
ScrollTrigger panel; the first ~5% of the pin is intentionally a background
fade-in, so there is still a short dark lead-in after the seam. If Malachy
wants the photo visible immediately, that is a HO-019 timeline change
(fade-in → start visible) — not done here.

## 6. Also in this session — section rhythm (Malachy requests, folded in)

- `#work.projects` given independent `padding-top`/`padding-bottom:
  clamp(100px, 5vw, 190px)`; removed from the shared `.intro/.services/.stack/
  …` group (which stays `clamp(100px, 13vw, 190px)`).
- `#experience.experience` given independent `padding-top` (same clamp).
- Removed now-dead `.projects { padding-bottom: 150px }` and the `max-width:800px`
  `.projects { padding-bottom: 95px }` override.
- Verified computed values at 1440 / 1024 / 375 (all `#work` + `#experience`
  = 100px; siblings unchanged at 187.2 / 133.1 / 100px).

## 7. Also in this session — jobs showcase overhaul (v1.3.16 → v1.3.17, still iterating)

Full replacement of the HO-050 flat text-card carousel with the 3D looping
showcase per `docs/handover/3D-carousel-for-jobs-section.txt` +
`Webflow-3D-Looping-Card-Animation.png`:

- `template-parts/section-projects.php` rewritten — borderless two-half
  showcase; mobile full-viewport stacked slides; dots timeline (desktop).
- `assets/js/animations/projects.js` rewritten vanilla (no GSAP) — three-tier
  `matchMedia` (mobile ≤767 / tablet 768–1199 / desktop ≥1200), WAAPI deck
  shuffle (front → 2nd → 3rd → dark, new front slips in), char-split spotlight
  title, layered parallax description, tablet swipe, desktop 5s autoplay, dots
  click → jump + 3s-idle resume with no skipped sequence.
- `assets/css/main.css` — old `.carousel/.track/.card` block replaced with
  `jc-*` styles; `#work` background made continuous (transparent).
- `inc/data-seeder.php` — 6 projects retitled + regrouped
  (Test Engineering / Web Platforms / Automation & Infrastructure) + `_project_tldr`
  + `_project_image`; `migrate_projects()` upsert ran on staging (6 posts, no dups).
- 6 source images in `assets/carousels/` optimised with `cwebp -q 80` to
  `assets/images/work-*-{800,1400}.webp` (21–79 KB each vs 0.7–2 MB sources).
- `functions.php` — `projects.js` enqueued on the front page for **all**
  devices (was trapped inside the `! wp_is_mobile()` GSAP block, which would
  have starved tablets); dropped from the GSAP animation-module list.
- Bug found + fixed during verification: `overflow-x: clip` on `.projects`
  (which is itself `.section-wrap`) was cropping the full-bleed mobile track
  by 24px each side (image + left of every text line). Removed; the global
  `.portfolio-shell { overflow: hidden }` already prevents page scrollbars.
- **Not yet finished:** tablet + desktop browser verification and Malachy's
  copy/heading decisions on staging. No HO-051 written yet — this is still an
  open unit; will be handed over once Malachy signs off.

## 8. Version

`MALACHY_THEME_VERSION` + `style.css` → **1.3.20** (was 1.3.16 at session
start; bumps: 1.3.17 showcase, 1.3.18/1.3.19 padding, 1.3.20 dedupe+seam).

## 9. Remaining / next

1. Malachy reviews staging; carousel copy + "Recent projects." heading decision.
2. Finish tablet + desktop carousel verification; write HO-051.
3. Production deploy of the accumulated work (still gated on Malachy) — own
   tracked step, incl. production project reseed + experience dedupe.
4. Confirm whether to change the HO-019 testimonials entry fade (dark lead-in).
