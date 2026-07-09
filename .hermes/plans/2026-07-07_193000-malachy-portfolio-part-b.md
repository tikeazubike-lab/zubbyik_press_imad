# Malachy Portfolio — Part B: CPT Seeding, Perf, Packaging & Docs

> **For Hermes:** Use subagent-driven-development to implement this plan task-by-task.
> **Goal:** Complete the remaining 6 phases of the WordPress theme conversion — dynamic CPT data, performance optimization, accessibility hardening, staging validation, shared-hosting packaging, and final documentation.
> **Architecture:** All CPT queries already use `WP_Query`; this phase seeds actual data, optimizes for production, validates, packages, and documents.
> **Tech Stack:** WordPress (PHP 8.x), GSAP 3.12.5, CSS custom properties, Docker Compose (staging), Traefik.
> **Status:** All section templates, animations, meta boxes, CPT registrations complete as of v1.0.9. Current Lighthouse: Perf 73, A11y 100, BP 100, SEO 100.

---

## Phase Inventory — What's Done vs What Remains

| Phase | Part A (Done) | Part B (Planned) |
|-------|---------------|------------------|
| 0–4 | Theme skeleton, all section templates, CSS, header/footer, front-page | — |
| 5 | CPT registrations (project, experience, skill, testimonial, publication) in `inc/post-types.php` | **Data seeding** — create WP-CLI seed script for existing portfolio content |
| 6 | Meta box registrations in `inc/meta-boxes.php` for all CPTs | **Data seeding** — seed meta values alongside post creation |
| 7 | All 9 animation modules + AnimationManager | — |
| 8 | WebP conversion, fetchpriority, unsized images fix, color contrast | **Performance Phase 8** — responsive images, critical CSS, font preconnect, GSAP deferred loading |
| 9 | Color contrast, aria labels, heading hierarchy, skip link, form labels | **Accessibility Phase 9** — focus states, keyboard nav audit, reduced-motion validation, structured data |
| 10 | Multiple Lighthouse runs, iterative fixes | **Staging Validation** — final comprehensive Lighthouse, phpcs, visual regression, contact form test |
| 11 | — | **Package for shared hosting** — minify, strip, zip |
| 12 | — | **Final documentation** — 9 markdown docs |

---

## Phase 5B: CPT Data Seeding

**Objective:** Create a one-time WP-CLI command or migration script that inserts the existing portfolio content (3 projects, 4 experience milestones, 8 skills, 2 blog posts) as CPT entries with proper meta fields. The section templates already use `WP_Query` with correct `post_type` parameters — they just need data to query.

### Files to create/modify:
- Create: `inc/data-seeder.php`
- Modify: `functions.php` (include data-seeder, register CLI command)
- Modify: `template-parts/section-skills.php` (remove fallback hardcoded skill icons — use CPT meta)

### Task 5B.1: Create data seed script

A WP-CLI command that runs once:
```
wp malachy seed
```

Creates these posts with proper meta:

**Projects (3):**
1. "Test Automation Framework" — post_type `project`, meta: `_project_url`, `_github_url`, `_tech_stack`, `_project_duration`
2. "Infrastructure as Code" — post_type `project`, meta same
3. "Custom WordPress Platform" — post_type `project`, meta same

**Experience (4):**
1. "Senior QA Engineer & Systems Consultant" — post_type `experience`, meta: `_company`, `_role`, `_location`, `_duration`, `_achievement`
2. "QA Automation Engineer" — same
3. "System Administrator" — same
4. "IT Support Specialist" — same

**Skills (8):**
1-8 skill entries — post_type `skill`, meta: `_skill_level` (80-95), `_skill_icon` (predefined SVG key)

**Blog posts (2):**
Create standard `post` entries (already seeded via WordPress defaults; verify they exist)

### Task 5B.2: Wire up section-skills.php for CPT data

The skills section currently queries CPT but may need SVG icon rendering from meta. Ensure `_skill_icon` meta maps to the correct inline SVG from the 8 original React icons.

### Task 5B.3: Wire up section-projects.php for CPT data

Already queries `project` CPT. Verify `_tech_stack` array meta renders as tech tag badges with correct styling.

### Task 5B.4: Wire up section-experience.php for CPT data

Already queries `experience` CPT. Verify `_company`, `_role`, `_location`, `_duration`, `_achievement` render in the timeline layout.

### Task 5B.5: Run seeder + verify

```bash
docker exec malachy-wp wp malachy seed
# Then browse front-page to verify all 3 sections show correct dynamic data
```

---

## Phase 8B: Performance Optimization

**Current baseline:** Perf 73 (LCP 2.0s, TBT 190ms, CLS 0.001)
**Target:** Perf 85+

### Files to modify:
- Modify: `template-parts/section-hero.php` (add picture/srcset)
- Modify: `functions.php` (add preconnect, defer GSAP, add critical CSS)
- Modify: `header.php` (add preload hints)
- Create: `assets/critical-hero.css` (extracted above-the-fold CSS)

### Task 8B.1: Add responsive image srcset

Generate size variants of the portrait and about-illustration, add `srcset` + `sizes` attributes:

```php
// Portrait responsive sizes example
srcset="/assets/images/malachy-portrait-400.webp 400w,
        /assets/images/malachy-portrait-600.webp 600w,
        /assets/images/malachy-portrait-1024.webp 1024w"
sizes="(max-width: 640px) 50vw, 30vw"
```

Use `cwebp -resize` to generate 400w, 600w, and 1024w variants of both images.

### Task 8B.2: Add font preconnect + preload

In `header.php`, add:
```html
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
```

And preload Fraunces + Inter (the CSS `@font-face` rules will match these).

### Task 8B.3: Defer non-critical GSAP scripts

Currently GSAP + ScrollTrigger are render-blocking. Change to `defer`:

```php
wp_enqueue_script('gsap', ..., ['strategy' => 'defer']);
wp_enqueue_script('gsap-scroll-trigger', ..., ['strategy' => 'defer']);
```

All animation modules already have `true` as the 6th param (footer), which is correct.

### Task 8B.4: Inline critical hero CSS

Extract the hero section CSS from `main.css` into `assets/critical-hero.css` (~15KB → ~3KB). Inline it in `<head>` via `<style>...</style>` using `file_get_contents`, then load `main.css` with `media="print" onload="this.media='all'"` (loadCSS pattern).

### Task 8B.5: Run Lighthouse and verify

```
Run desktop Lighthouse:
- LCP < 1.8s
- TBT < 100ms
- CLS < 0.1
- Perf score >= 85
```

---

## Phase 9B: Accessibility Hardening

**Current baseline:** A11y 100 (all automated checks pass)
**Target:** Verified manual accessibility pass

### Task 9B.1: Keyboard navigation audit

Walk tab order:
- Skip link → Nav (Home → About → Skills → Projects → Experience → Blog → Contact → Theme toggle) → Hero CTA buttons → Form fields → Social links
- All interactive elements must receive visible `:focus-visible` outline
- Form submit button must be keyboard-activatable

### Task 9B.2: Add structured data (JSON-LD)

Add `Person` schema to `header.php` or via dedicated plugin function:

```php
<!-- Person schema for Google -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Person",
  "name": "Malachy Egbuna",
  "jobTitle": "QA Engineer, SysAdmin & IT Support Specialist",
  "url": "https://imadconsult.zubbystudio.shop",
  "sameAs": [
    "https://github.com/malachy",
    "https://linkedin.com/in/malachy-egbuna"
  ]
}
</script>
```

### Task 9B.3: Add autocomplete attributes to contact form

```php
'input' => 'name'  →  autocomplete="name"
'input' => 'email' →  autocomplete="email"
'textarea'         →  autocomplete="off"
```

### Task 9B.4: Verify prefers-reduced-motion

All animation modules already check `AnimationManager.reduced`. Test with OS-level reduce-motion setting: animations should disable, all content visible without scroll-triggered reveals.

### Task 9B.5: Run Lighthouse

Verify A11y = 100, no new issues.

---

## Phase 10: Staging Validation (Final)

### Task 10.1: Full Lighthouse suite

Run Lighthouse on both desktop and mobile:
- Desktop: Perf >= 85, A11y = 100, BP = 100, SEO = 100
- Mobile: Perf >= 70, A11y = 100, BP = 100, SEO = 100

Fix any regressions.

### Task 10.2: Contact form end-to-end test

Submit the contact form with test data, verify:
- AJAX submission returns success
- Email is sent to `malachy.egbuna@imadconsulting.co.uk`
- Honeypot field traps bots (hidden field filled = suppressed)
- Nonce mismatch returns error
- Rate limiting works (rapid submissions blocked)

### Task 10.3: Visual regression check

Hard refresh and visually verify every section:
- Correct fonts loaded (Fraunces headings, Inter body)
- Dark mode toggle works, persists across page loads
- No layout shift on font load (CLS < 0.1)
- Animations fire on scroll (hero stagger, skill cards, projects stack, timeline line, blog cards, contact orbs)
- Mobile: no animations break (matchMedia gating works)
- Marquee scrolls continuously
- CTA buttons link to correct sections (#projects, #about, etc.)

### Task 10.4: PHP CodeSniffer

```bash
docker exec malachy-wp phpcs --standard=WordPress /var/www/html/wp-content/themes/malachy-portfolio/ --ignore=vendor,node_modules
```

Fix any errors (warnings are acceptable).

---

## Phase 11: Package for Shared Hosting

### Task 11.1: Minify CSS/JS assets

```bash
# Minify CSS
curl -X POST -s --data-urlencode 'input@assets/css/main.css' https://www.toptal.com/developers/cssminifier/api/raw > assets/css/main.min.css

# Minify JS
# Use terser or similar for all animation JS files + contact.js
```

Store minified as `*.min.css` / `*.min.js` alongside originals. Update enqueue handles to point to minified versions.

### Task 11.2: Create build script

**File:** `bin/build.sh` — one-time packaging script:

```bash
#!/bin/bash
# 1. Minify CSS/JS (uses local tools)
# 2. Strip dev files: .git/, node_modules/, docker-compose.yml, .env, .gitignore, README.md
# 3. Create deploy-ready zip
zip -r ../malachy-portfolio.zip . \
  -x "node_modules/*" \
  -x ".git/*" \
  -x "vendor/*" \
  -x "docker-compose.yml" \
  -x ".env" \
  -x ".gitignore" \
  -x "README.md" \
  -x "bin/*"
```

### Task 11.3: Verify zip integrity

Unzip to a temp directory, activate on a clean WordPress instance (or the Docker staging instance at a subpath), verify all sections render.

### Task 11.4: Replace staging URLs

Search for any hardcoded `imadconsult.zubbystudio.shop` URLs in PHP files and replace with `home_url('/')` or template constants. This ensures the theme works on any domain without manual find-and-replace.

---

## Phase 12: Final Documentation

### Task 12.1: `docs/theme-structure.md`

Complete file listing with purpose of each file.

### Task 12.2: `docs/theme-setup.md`

1. Upload theme via WP Admin → Appearance → Themes → Add New → Upload
2. Activate theme
3. Set Reading Settings: "Your homepage displays" → "A static page" → select "Front Page"
4. (Optional) Create permalink structure: Settings → Permalinks → "Post name"

### Task 12.3: `docs/deploy-shared-hosting.md`

- FTP steps
- Permalink flush after upload
- Mail test checklist (contact form)
- Troubleshooting: blank page (PHP version check), broken layout (permalinks)

### Task 12.4: `docs/animation-architecture.md`

AnimationManager pattern, matchMedia gating, reduced-motion support, mobile pinning exceptions.

### Task 12.5: `docs/maintenance.md`

How to add new projects/skills/experience via WP Admin, update content, change contact email.

### Task 12.6: `docs/staging-development.md`

Docker Compose workflow for the Netcup VPS, WP-CLI commands, theme hot-reload via volume mount.

### Task 12.7: `docs/packaging-guide.md`

Strip list, zip command, post-upload smoke test.

### Task 12.8: Verify all docs references

Check internal cross-links in docs are correct. All absolute domain references match `imadconsult.zubbystudio.shop`.

---

## Implementation Order (Recommended)

| Task | Est. Time | Dependencies | Via Subagent? |
|------|-----------|-------------|---------------|
| 5B.1 Data seed script | 10 min | None | Yes |
| 5B.2–5B.4 Wire templates for CPT data | 5 min each | 5B.1 | Yes (batch) |
| 5B.5 Run seeder | 2 min | 5B.1–5B.4 | No |
| 8B.1 Responsive image variants | 10 min | None | Yes |
| 8B.2 Font preconnect | 3 min | None | No |
| 8B.4 Critical CSS | 15 min | None | Yes |
| 8B.5 Lighthouse verify | 5 min | All 8B tasks | No |
| 9B.1–9B.4 Accessibility | 15 min | None | Yes (batch) |
| 9B.5 Lighthouse verify | 5 min | 9B.1–9B.4 | No |
| 10.1–10.4 Validation | 15 min | All 5B–9B | No |
| 11.1–11.4 Packaging | 20 min | Phase 10 | Yes |
| 12.1–12.8 Docs | 25 min | Phase 11 | Yes (batch) |
| Final Lighthouse | 5 min | All | No |

**Total estimated remaining effort: ~2.5 hours**

---

## Key Risks

| Risk | Mitigation |
|------|-----------|
| CPT data seeder fails mid-way | Use `wp_insert_post` in a transaction-like loop with `wp_defer_term_counting(true)` for speed |
| WebP not supported on target shared hosting | Keep original PNGs as fallback; the `<picture>` element in Task 8B.1 can serve both |
| GSAP CDN blocked on shared hosting | GSAP is already bundled locally in `assets/js/vendor/` — no CDN dependency |
| Contact form relies on `wp_mail()` which may not work on all hosting | Document `docs/deploy-shared-hosting.md` SMTP setup |
| Performance score target may not be achievable on simulated throttling | Focus on real improvements (image sizes, render-blocking, fonts) rather than targeting a specific number |
