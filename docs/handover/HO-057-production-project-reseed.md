---
type: HANDOVER
project: IMAD Consulting — Lead Capture & Content Automation
title: HO-057 — Production project-section divergence: root cause, and a read-only preflight/verify/render tool for the reseed
date: 2026-09-26
from: Hermes (orchestrator)
to: Claude[Sonnet] Web (Reviewer) / Malachy (the pull and the run are yours)
status: DRAFT — tooling written and verified locally against a production-equivalent CPT state; NOT pushed, NOT run on production
priority: HIGH
---

## 0. Scope of this document

Malachy compared production to staging and found them "totally different", most
visibly the project section, with the new card images missing on production. His
working hypothesis was that `.gitignore` had excluded the images from the repo.

That hypothesis is wrong, and I can disprove it with numbers (§1). The real cause
is database content, not files. This document records the root cause, records the
seeder that already exists for exactly this (§2), introduces one new read-only tool
that the seeder was missing (§3), and gives the production runbook (§4).

Everything here is written to be pasted into Claude as-is.

---

## 1. Why production and staging differ

### 1.1 The `.gitignore` hypothesis is disproved

The ignore rules are root-anchored on purpose — `/*.png`, `/*.jpg`, `/*.webp`,
etc. — and the file itself says so:

```
# Root-anchored (leading /) on purpose: theme images under
# malachy-portfolio/assets/images/ and the already-tracked
# docs/handover/*.png are NOT affected by these rules.
```

Evidence they aren't ignored:

- `git ls-files malachy-portfolio/assets/images/` → **57 tracked files**.
- `git status --porcelain --ignored=matching malachy-portfolio/` → **empty** (no
  untracked or ignored images inside the theme).

And the live proof, probed over HTTP against production:

| file | production | staging | git blob size |
|---|---|---|---|
| `work-specforge-tooling-800.webp` | 200 / 31756b | 200 / 31756b | 31756 |
| `project-specforge-900w.webp` | 200 / 36252b | 200 / 36252b | 36252 |
| `project-epm-v2-900w.webp` | 200 / 43778b | 200 / 43778b | 43778 |
| `project-test-taxonomy-900w.webp` | 200 / 23424b | 200 / 23424b | 23424 |
| `project-chatbot-readiness-900w.webp` | 200 / 43252b | 200 / 43252b | 43252 |

Byte-identical to the committed blobs. **Nothing is missing, nothing is 404ing.**

> Correction to my own first pass: I initially probed
> `/wp-content/themes/malachy-portfolio/...` and got 404s on production. That was
> my error. Production's real theme path is
> `/wp-content/themes/zubbyik_press_imad/malachy-portfolio/...` — the repo is
> checked out as the themes directory with the theme in the `malachy-portfolio/`
> subdirectory. Re-probed on the correct path, everything returns 200.

### 1.2 Production's code is already in sync with git

sha256 of live files on both hosts versus `HEAD`:

```
FILE                               PROD         STAGING      GIT HEAD
assets/css/main.css                86e5213e707d 86e5213e707d 86e5213e707d
assets/js/animations/projects.js   86bff278d560 86bff278d560 86bff278d560
assets/js/theme-toggle.js          72b97a554729 72b97a554729 72b97a554729
assets/js/contact.js                f297c3db8705 f297c3db8705 f297c3db8705
```

Identical everywhere. The theme code deploy is done.

### 1.3 The real cause: the section is database-driven

`template-parts/section-projects.php` does not hold a static card list. It runs a
`WP_Query` on the `project` CPT (`menu_order`, ASC) and picks each card image in
this priority order (lines 139–162):

1. post meta `_project_image` → renders `assets/images/<value>-800.webp` / `-1400.webp`
2. else the featured image → renders whatever lives in `wp-content/uploads/`
3. else no image

If the CPT were empty it would fall back to a hardcoded six-item list (lines 44–96).

The two environments hold **completely disjoint** post sets:

```
PRODUCTION — 3 posts
  id=34  order=1  featured_media=42  test-automation-framework
  id=36  order=2  featured_media=43  infrastructure-as-code
  id=38  order=3  featured_media=41  custom-wordpress-platform

STAGING — 6 posts
  id=154 order=1  featured_media=222  epm-test-taxonomy-automated-test-framework
  id=156 order=5  featured_media=226  self-hosted-service-stack
  id=158 order=3  featured_media=224  wordpress-chatbot-readiness-assessment
  id=160 order=4  featured_media=225  epm-v2-estate-portfolio-manager
  id=162 order=2  featured_media=223  specforge
  id=164 order=6  featured_media=227  imad-consulting-automation-platform

slug overlap between the two sets: NONE
```

- Production's 3 legacy posts have **no `_project_image` meta**, so they fall to
  branch 2 and their cards load the old uploads JPEGs —
  `uploads/2026/07/project-{qa,sysadmin,wordpress}.jpg` (featured media 42/43/41,
  verified via `/wp-json/wp/v2/media/<id>`).
- Staging's 6 posts **do** have `_project_image`, so branch 1 wins and the cards
  use the `work-*.webp` theme assets.

So production is not missing images. Production is rendering three *older, different
posts* that were never given the new image meta, and the three newest projects do not
exist there at all.

**That is why a `git pull` and a Cloudflare purge could never converge them: the
difference is rows in the WordPress database.** The files on both hosts are already
identical, so the purge just re-serves the same DB-driven markup.

This is the step HO-002-era context already flagged and nobody ran.
`docs/handover/Imad-project-context.md` §7: *"v1.3.15 → v1.3.21 production deploy —
everything from HO-049 through HO-053 is staged and verified on staging only …
must also reseed the 6 project posts + experience de-dupe on production."*

---

## 2. The seeder already exists — I did not write a second one

`malachy-portfolio/inc/data-seeder.php` (tracked, required by `functions.php:357`)
already registers the WP-CLI command namespace:

```
wp malachy migrate_projects     # HO-051 — upsert the 6 showcase projects
wp malachy migrate_experience   # HO-052 — de-dupe then re-seed the canonical 7
```

`seed_projects()` matches an existing post by **canonical title OR `legacy_title`**
and updates it in place (lines 327–372), so it is idempotent and does not duplicate.
It then writes `_project_tag`, `_project_tldr`, `_project_tech`, `_project_url`,
`_project_github`, `_project_image`, and sideloads the featured image.

What that means for production specifically:

- Post **34 "Test Automation Framework"**, **36 "Infrastructure as Code"**,
  **38 "Custom WordPress Platform"** each match a canonical title exactly → they are
  **updated in place**, keep their IDs, and gain `_project_image`.
- **Specforge Tooling**, **Estate Portfolio Manager**, **IMAD Consulting Automation
  Platform** do not exist on production → they are **created**.
- Result: 6 posts, every card on a theme asset. Same end state as staging, modulo
  post IDs and slugs (neither affects rendering).

One behavioural detail worth recording: the legacy posts on production have
*different slugs* than staging's (`custom-wordpress-platform` vs
`wordpress-chatbot-readiness-assessment`, etc.) because staging's posts came from an
older seed whose titles were then renamed in place by this same migration. Slugs are
not used for matching, so this does not change the outcome — but it does mean
production's resulting slugs will differ from staging's after the run. Rendering is
unaffected; any external link to `/projects/<slug>/` would be, so see §6.

### 2.1 The second half — `migrate_experience`

§7 requires it, and it is worth knowing that `dedupe_cpt()` calls
`wp_delete_post( $pid, true )` — **force delete, no trash**. I checked production
before recommending it:

```
PRODUCTION experience CPT: 7 posts, all titles distinct
  Network Administrator / Desktop Support Engineer / Test Analyst (Parcel Force /
  NatWest / Quick Light) / Test Analyst (Planixs) / Test Analyst / Front-End
  Development / Test Analyst / UAT / Founder & Systems/QA Consultant
```

No duplicates, so the delete branch does nothing on production. It will only refresh
the canonical seven. Staging likewise holds 7.

---

## 3. What I added, and why

The seeder mutates a live site with **no dry run and no acceptance check**. On a
production database whose current state nobody had written down, "run it and see" is
not an acceptable first step. So I added one read-only tool:

### `docs/ops/project-reseed-preflight.php`

Three modes, **none of which write anything**:

| mode | what it does |
|---|---|
| `preflight` | reports, per canonical item, whether the seeder will UPDATE an existing post (and which ID, and that post's current `_project_image`/thumbnail) or CREATE a new one; flags unmatched posts that would remain as extra cards; checks the referenced image files exist on disk |
| `verify` | asserts the CPT count equals the canonical count, every post has `_project_image`, and no post resolves to an `uploads/` image; exits 1 on FAIL so it can gate a scripted deploy |
| `render` | buffers `template-parts/section-projects.php`, prints the real `jc-card-img` count and every image source with its kind (theme asset vs uploads), exits 1 if any `uploads/` URL survives |

Design notes:

- It reads the canonical item list by **reflecting into `Malachy_Seeder::get_project_items()`**,
  so it cannot drift from what the seeder will actually write. The item data is not
  duplicated anywhere.
- It mirrors the seeder's own match order (canonical title, then legacy title,
  status-agnostic).
- Written to PHP 7.0 syntax (no arrow functions, no spread, no nullsafe, no trailing
  commas in calls) because the docs disagree about production's PHP minor version —
  see §5.

Run modes: `preflight` (always exits 0), `verify` / `render` (exit 1 on FAIL).

### 3.1 Verification evidence — full cycle on a production-equivalent state

I could not point this at production, so I reproduced production's CPT state on the
local dev WordPress and ran the whole cycle against it.

**Step 1 — emulate production.** Deleted the three posts production lacks; stripped
`_project_image` from the surviving three so they match production's meta-less posts:

```
$ wp post list --post_type=project --fields=ID,post_title     # after edit
154  Test Automation Framework
156  Infrastructure as Code
158  Custom WordPress Platform
$ wp post meta get 154 _project_image     # → empty
```

**Step 2 — preflight correctly predicts the production outcome:**

```
=== PREFLIGHT: project reseed (read-only, nothing is written) ===
Canonical items: 6

      ACTION: UPDATE id=154 (matched existing post)
      ACTION: CREATE new post (no title/legacy-title match)
      ACTION: UPDATE id=158 (matched existing post)
      ACTION: CREATE new post (no title/legacy-title match)
      ACTION: UPDATE id=156 (matched existing post)
      ACTION: CREATE new post (no title/legacy-title match)

--- summary ---
  would update : 3
  would create : 3
  posts after  : 6
  theme images missing on disk: 0

  no unmatched posts — after the migration the CPT holds exactly the canonical set.

Nothing was written. To apply:  wp malachy migrate_projects
```

**Step 3 — apply:**

```
$ wp malachy migrate_projects
  → 6 projects upserted (HO-051 showcase content).
Success: Project migration complete.
```

**Step 4 — verify:** `PASS: 6/6 posts, every card renders a theme asset image.`
The three matched posts kept ids 154/156/158; the three created ones got 229/232/235.

**Step 5 — render check on the actual template output:**

```
=== RENDER: what the project section actually outputs ===
cards rendered (jc-card-img): 6

image sources in this section:
  [theme asset] .../assets/images/work-test-automation-framework-800.webp
  [theme asset] .../assets/images/work-specforge-tooling-800.webp
  [theme asset] .../assets/images/work-custom-wordpress-platform-800.webp
  [theme asset] .../assets/images/work-estate-portfolio-manager-800.webp
  [theme asset] .../assets/images/work-infrastructure-as-code-800.webp
  [theme asset] .../assets/images/work-imad-automation-platform-800.webp

--- result ---
  theme-asset images : 6
  uploads/ images    : 0
PASS: 6 cards, no uploads/ fallbacks — the section is on the new showcase assets.
```

`php -l` clean. The three create-branch posts were created in the local dev DB, which
is why their IDs differ from staging's; local content is otherwise canonical again.

---

## 4. Production runbook

Per HO-054 C6, production is updated by **a manual `git pull` on the shared host,
performed by Malachy**. No agent pushes files to the server.

### 4.1 Before anything — confirm the correct PHP binary

```bash
# If wp-cli is on PATH:
wp --info | head -3

# If not:
php -v | head -1          # expect the host's PHP
# and use a downloaded phar:
#   curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
#   php wp-cli.phar <command> --path=<wp-root>
```

### 4.2 Pull the theme

```bash
cd <wp-root>                                    # the directory containing wp-config.php
cd wp-content/themes/zubbyik_press_imad && git pull --ff-only && git log -1 --oneline
```

Expect `d796ca5` or later. If `wp malachy` is "not a known command" afterwards, the
theme files did not update — stop and report, don't improvise.

### 4.3 Preflight — read-only, run this first

```bash
cd <wp-root>
wp eval-file wp-content/themes/zubbyik_press_imad/docs/ops/project-reseed-preflight.php preflight
```

**Expected output — `would update: 3`, `would create: 3`, `posts after: 6`,
`theme images missing on disk: 0`, and no unmatched posts.**

Abort and report if instead you see:

- "would create" greater than 3 (unexpected extra posts would be created), or
- any `! UNMATCHED posts that will REMAIN on the page as extra cards` other than the
  three known legacy IDs (34, 36, 38) — those three are matched, so this section
  should be empty, and
- any `[MISSING ON DISK]` line, which would mean the pull didn't bring the images.

### 4.4 Apply

```bash
wp malachy migrate_projects
wp malachy migrate_experience
```

### 4.5 Verify — must PASS before you stop

```bash
wp eval-file wp-content/themes/zubbyik_press_imad/docs/ops/project-reseed-preflight.php verify
wp eval-file wp-content/themes/zubbyik_press_imad/docs/ops/project-reseed-preflight.php render
```

Then purge Cloudflare and hard-reload the site; the project section should show
**6 cards** with the new imagery, and no `uploads/2026/07/project-*.jpg` anywhere in
section `#work`.

### 4.6 Rollback

Re-running `wp malachy migrate_projects` is safe and idempotent — it will re-level
any partial state. There is no destructive step in the project path other than the
featured-image replacement described in §5, which was verified as safe. If the run
must be undone entirely, the three created posts can be deleted with
`wp post delete 229 232 235 --force` equivalent **on production's own IDs** (read
them from the `verify` output), and the three matched posts keep working as they do
today because the migration only added meta to them.

---

## 5. Risks and unknowns, stated plainly

1. **Featured-image attachment replacement.** `set_featured_image()` is called with
   `$force = true`, and that path force-deletes the previous attachment
   (`wp_delete_attachment( $old_id, true )`). On production this deletes media **41,
   42, 43** (`project-wordpress.jpg`, `project-qa.jpg`, `project-sysadmin.jpg`) and
   sideloads copies of the theme webp files in their place. I checked whether
   anything else references those three files, over production's own REST API:
   `search` for each filename → 0 hits; and no post or page body contains any of the
   three filenames. So the deletion is safe *as far as the database content goes* —
   but it is irreversible, and any inbound hotlink to those upload URLs from outside
   the site (an old email, a social card) would break.
2. **Production's PHP version is not nailed down in the docs.** `HO-001` says PHP 8.2
   (that entry describes the VPS/Docker stack), `R-001` says the InMotion shared host
   runs PHP 8.0, and older context says 7.x. The new tool is written to 7.0 syntax so
   it runs on any of them; the seeder itself is pre-existing and already runs on
   staging.
3. **wp-cli availability on the shared host is unverified from here.** Malachy's
   setup notes say WP-CLI is in use, and HO-054 C6 confirms the manual pull; but I
   have not executed anything on the host. §4.1 covers the phar fallback.
4. **Production post slugs will not match staging's** after the run (see §2), because
   matching is by title and the legacy slugs are retained. Rendering is unaffected.
5. **`migrate_experience` is force-delete capable.** It is a no-op on production today
   (§2.1, 7 distinct posts), but it stops being a no-op the moment anyone duplicates
   an experience entry.

---

## 6. Open questions for Malachy

- **OQ-HO057-1:** Production's `project` CPT will keep its own post IDs and slugs
  (34/36/38 keep `test-automation-framework`, `infrastructure-as-code`,
  `custom-wordpress-platform`; the new three get fresh slugs). Staging's slugs differ
  entirely. Does anything in the wild link to `/projects/<slug>/` on production? If
  yes, we should decide whether to align slugs with staging in the same run rather
  than leave the two environments permanently inconsistent.
- **OQ-HO057-2:** The three legacy posts are being *repurposed* rather than retired —
  "Test Automation Framework", "Infrastructure as Code" and "Custom WordPress
  Platform" survive as three of the six canonical cards, but their write-ups, TLDRs
  and images are replaced wholesale by HO-051 content. Confirm that replacing the
  existing copy in place is intended, rather than keeping the legacy posts and adding
  six new ones.
- **OQ-HO057-3:** Do you want the preflight/verify/render tool kept permanently as a
  repo ops tool (`docs/ops/`, a new directory), or promoted into the theme as
  `wp malachy preflight_projects` / `wp malachy verify_projects` subcommands? The
  latter is nicer to run but is a theme code change needing review.

---

## 7. State at time of writing

- **Not pushed.** Local `main` == `origin/main` at `d796ca5`; the new file is an
  uncommitted working change. Standing rule from the project context §7 — "no
  commit/push until he says so" — and HO-055 §2.3 asked that go-aheads be stated
  plainly rather than assumed. **The push needs an explicit go-ahead.**
- **Nothing has been run against production or staging.** The migration was exercised
  only against the local dev WordPress, whose `project` CPT is deliberately
  disposable and is now back to canonical state.
- Two pieces of unrelated repo hygiene found while investigating, for the record:
  `f2a2981` accidentally committed a stray file literally named `vim` (a 52-line
  review brief, still tracked at `HEAD`, should move under `docs/` or be deleted);
  and that commit's message says "39 files" where HO-054's own post-push audit
  records the staged set as 32.
