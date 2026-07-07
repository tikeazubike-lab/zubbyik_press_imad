# Theme Structure — Malachy Portfolio

**Version:** 1.1.0 · **Domain:** imadconsult.zubbystudio.shop

Complete file listing with the purpose of every file in the theme.

---

## Root Files

| File | Purpose |
|---|---|
| `style.css` | Theme header (name, version, author, license). Required by WordPress. |
| `functions.php` | Theme setup (supports, menus), asset enqueuing (GSAP, fonts, CSS, JS), module inclusion. |
| `index.php` | Fallback template — standard WordPress loop. |
| `front-page.php` | Single-page portfolio layout — includes all 7 section template parts. |
| `home.php` | Blog archive template — queries posts with WP_Query, pagination, category/date meta. |
| `single.php` | Single blog post template — title, category, date, content, back link. |
| `page.php` | Generic page template — title + content. |
| `404.php` | 404 error page — centered message with "Go home" button. |
| `header.php` | `<head>` with schema.org JSON-LD, dark-mode inline script, `wp_head()`, nav wrapper. |
| `footer.php` | Closes `</main>` and `.site-wrapper`, renders footer template part, `wp_footer()`. |
| `robots.txt` | Allows all crawlers; references `wp-sitemap.xml`. |

---

## Template Parts (`template-parts/`)

| File | Purpose |
|---|---|
| `navigation.php` | Sticky glassmorphism header — desktop nav links, mobile hamburger menu, theme toggle. Links mapped from a `$nav_links` array. |
| `section-hero.php` | Full-screen hero — portrait image (responsive srcset), heading + lede, CTA buttons (View Projects, Download CV), tech marquee badges. |
| `section-about.php` | About section — bio text, stats (8+ years, 40+ projects, 99.9% uptime), decorative illustration. |
| `section-skills.php` | Skills grid — queries `skill` CPT; falls back to 8 hardcoded skills with inline SVG icons. |
| `section-projects.php` | Project cards — queries `project` CPT; falls back to 3 hardcoded projects. Stacked layout with image, tech tags, Live Demo / Source links. |
| `section-experience.php` | Timeline — queries `experience` CPT; falls back to 4 entries. Vertical line with growing fill, dot markers, year/role/org/description. |
| `section-blog-preview.php` | Blog preview section — latest 3 posts with category, date, excerpt. Fallback hardcoded articles. |
| `section-contact.php` | Contact form — honeypot-protected, nonce-verified, AJAX-submitted. Social links (GitHub, LinkedIn, email), floating orbs. |
| `footer.php` | Site footer — copyright + "Available for engagements" pulse dot. |

---

## PHP Includes (`inc/`)

| File | Purpose |
|---|---|
| `post-types.php` | Registers 5 CPTs: `project`, `experience`, `skill`, `testimonial`, `publication`. 3 taxonomies: `project_category`, `technology_stack`, `skill_category`. |
| `meta-boxes.php` | Native meta boxes (no ACF) for all CPTs + admin settings page. Meta keys: `_project_url`, `_project_github`, `_project_tech`, `_exp_org`, `_exp_year`, `_skill_icon`, `_skill_level`, `_testimonial_role`, `_testimonial_org`. Theme mods: `malachy_portrait`, `malachy_hero_subtitle`, `malachy_resume_url`. |
| `data-seeder.php` | WP-CLI command (`wp malachy seed`) that seeds 3 projects, 4 experience entries, and 8 skills with default portfolio content. |
| `contact-handler.php` | REST API route (`malachy/v1/contact`) + admin-ajax handler with nonce verification, honeypot, IP-based rate limiting (3/hour), and `wp_mail()` delivery. |

---

## Assets — CSS

| File | Lines | Purpose |
|---|---|---|
| `assets/css/main.css` | 1,681 | Single compiled stylesheet. Design tokens in OKLCH, light/dark themes, base/reset, layout (container, grid, flex), component styles for every section, responsive breakpoints. |

---

## Assets — JavaScript

### Vendor Libraries

| File | Purpose |
|---|---|
| `assets/js/vendor/gsap.min.js` | GSAP 3.12.5 — core animation engine (bundled, no CDN dependency). |
| `assets/js/vendor/ScrollTrigger.min.js` | ScrollTrigger 3.12.5 plugin — scroll-driven animations. |

### Animation Modules (`assets/js/animations/`)

| File | Purpose |
|---|---|
| `AnimationManager.js` | Orchestrator class — section registration/cleanup, `matchMedia` support, `prefers-reduced-motion` respect. |
| `global.js` | Theme toggle (dark/light) with `localStorage` persistence, blog card scroll reveal. |
| `navigation.js` | Sticky glassmorphism header (GSAP on desktop, IntersectionObserver on mobile), mobile menu toggle, active link highlighting, smart anchor navigation, hash-on-load scroll. |
| `hero.js` | Pinned hero on desktop/tablet (matchMedia at 768px), portrait slow-scale on scroll, text upward translation, marquee scrub, CTA button hover. |
| `about.js` | Fade-up stagger for about-reveal elements, stats stagger, illustration parallax. |
| `skills.js` | Scroll-triggered stagger reveal for skill cards. |
| `projects.js` | Per-card entry animations (image slides left, body slides right) triggered as each card enters viewport. CSS handles sticky stacking. |
| `experience.js` | Vertical timeline line grows (`scaleY`) on scroll, milestone items fade-up stagger. |
| `contact.js` | Heading/form/social fade-up reveals, continuous floating orbs with yoyo repeat. |

### Utilities

| File | Purpose |
|---|---|
| `assets/js/contact.js` | Client-side AJAX form submission via `admin-ajax.php`. Shows loading state, success/error messages inline. |

---

## Assets — Images (`assets/images/`)

| File | Variants | Purpose |
|---|---|---|
| `malachy-portrait.*` | `.webp` + `.png` · 400w, 600w, 1024w | Hero section portrait (600×800) |
| `about-illustration.*` | `.webp` + `.png` · 400w, 768w, 1024w | About section decorative illustration (1024×1024) |
| `project-qa.png` | — | QA Automation Framework project card image |
| `project-sysadmin.png` | — | Infrastructure as Code project card image |
| `project-wordpress.png` | — | Custom WordPress Platform project card image |
| `favicon.ico` | — | Site favicon |

---

## Build & Deploy (`bin/`)

| File | Purpose |
|---|---|
| `bin/build.sh` | Build script — rsyncs theme files (excluding dev artifacts) and creates production `.zip`. Output: `../build/malachy-portfolio.zip`. |
| `bin/package.sh` | Shared hosting packaging — copies theme, strips dev files (`.git`, `node_modules`, `docs`, config files), creates versioned `.zip`. Output: `../malachy-portfolio-v1.0.0.zip`. |

---

## Archives

| File | Purpose |
|---|---|
| `malachy-portfolio-v1.0.0.zip` | Pre-built deployment archive for upload via WP Admin or cPanel. |
