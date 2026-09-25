# Malachy Portfolio — React → WordPress Theme Conversion Plan

> **For Hermes:** Use subagent-driven-development to implement this plan phase-by-phase.
> **Goal:** Convert the existing React portfolio (`malachy-s-cinematic-canvas`) into a pixel-identical, self-contained WordPress theme deployable to shared hosting with zero build steps.
> **Architecture:** Native WordPress theme with CPTs for dynamic content, GSAP for animations, native meta boxes for custom fields, custom REST API route for contact form. No ACF, no Gutenberg, no page builders.
> **Tech Stack:** WordPress (PHP 8.x), GSAP 3.15 (bundled), CSS custom properties (oklch color system), Fraunces + Inter fonts, no build step at runtime.
>
> **Source of truth:** `/home/zubbyik/wordpress_project/malachy-s-cinematic-canvas/` — all component JSX, styles, and assets define exactly what must be replicated.

---

## Phase 0: Prerequisites & Workspace Setup

**Check:** Do we have Docker available? A WordPress container ready? What PHP/MySQL version is available?

**Objective:** Set up the staging WordPress environment on the Netcup VPS so we can build and test iteratively.

**Files to create:**
- `docker-compose.yml` (WordPress + MySQL for staging — throwaway, not in final deliverable)
- `.env` (staging DB credentials)

**Step 1: Verify tools**
Run:
```bash
docker --version
docker compose version
wp cli version 2>/dev/null || echo "WP-CLI not found"
php --version
```

**Step 2: Spin up WordPress via Docker Compose**
Create a staging `docker-compose.yml` with:
- WordPress container (with Traefik labels for `testdrive.malachy.zubbystudio.site` or similar)
- MySQL 8 container
- The theme mounted as a volume for live editing

**Step 3: Install WP-CLI in the container**
```bash
docker exec wp-malachy apk add --no-cache wp-cli  # or via curl install
```

**Step 4: Scaffold empty theme skeleton**
```bash
wp scaffold theme malachy-portfolio --theme_name="Malachy Portfolio" --author="IMaD Consulting" --underscores=false --sass=false
```
This gives us a starting structure we can then replace.

---

## Phase 1: Complete React Codebase Audit

> **Already completed.** Summary of findings:

| Asset | Details |
|-------|---------|
| Framework | React 19, TanStack Router + Start, Tailwind v4 |
| Animation | GSAP 3.15 + ScrollTrigger |
| Design System | 4-brand colors (Brick #BD2222, Ebony #5C6C59, Champagne #fae3c6, Ink #000), OKLCH format, light + dark mode |
| Typography | Fraunces (display), Inter (sans) — Google Fonts |
| Sections | Hero, About, Skills, Projects, Experience, BlogPreview, Contact, Footer |
| Routes | `/` (single-page portfolio), `/blog` (blog listing) |
| Images | portrait (via Lovable CDN, needs download), about-illustration.png, 3 project PNGs |
| Content | All hardcoded as JS data arrays |

**Implicit assets to acquire before Phase 3:**
- The portrait image at the Lovable CDN URL referenced in `portrait.asset.json` — must download to `/assets/images/malachy-portrait.png`
- Google Fonts (Fraunces + Inter) — to bundle as local `woff2` or use CDN in enqueue

---

## Phase 2: Map React Components → WordPress Templates

**Objective:** Define the template hierarchy before writing any code.

| React Component | WordPress Template | Notes |
|----------------|-------------------|-------|
| `RootShell` (html/head/body) | `header.php` + `footer.php` | Theme wrapper, dark mode script injection |
| `Navigation` | `template-parts/navigation.php` | Included in `header.php` |
| `Hero` | `template-parts/section-hero.php` | Front-page only |
| `About` | `template-parts/section-about.php` | Front-page only |
| `Skills` | `template-parts/section-skills.php` | Front-page only, data from CPT |
| `Projects` | `template-parts/section-projects.php` | Front-page only, data from CPT |
| `Experience` | `template-parts/section-experience.php` | Front-page only, data from CPT |
| `BlogPreview` | `template-parts/section-blog-preview.php` | Front-page only, query WP posts |
| `Contact` | `template-parts/section-contact.php` | Front-page only |
| `Footer` | Merged into `footer.php` | — |
| `BlogPage` (blog.tsx) | `home.php` | Blog listing |
| 404 (root.tsx) | `404.php` | — |

**Template map:**
- `front-page.php` → includes all section template-parts in order
- `home.php` → blog listing page
- `single.php` → single blog post
- `404.php` → not-found page
- `page.php` → generic page (future use)
- `header.php` → `<html>`, `<head>`, opening `<body>`, Navigation
- `footer.php` → closing tags, Footer component

---

## Phase 3: Build Theme Skeleton & CSS Architecture

### Task 3.1: Create `style.css` (theme identifier)

**File:** `wp-content/themes/malachy-portfolio/style.css`

```css
/*
Theme Name: Malachy Portfolio
Theme URI: https://imadconsulting.com
Author: IMaD Consulting
Author URI: https://imadconsulting.com
Description: Cinematic portfolio theme for Malachy — QA Engineer, SysAdmin & IT Support Specialist. Converts the original React design pixel-perfectly with GSAP animations.
Version: 1.0.0
Requires PHP: 8.0
License: GPL v2 or later
Text Domain: malachy-portfolio
*/
```

### Task 3.2: Create `assets/css/main.css` — Full CSS from styles.css

**Objective:** Port all 193 lines of `styles.css` to a standalone CSS file (no Tailwind dependency). Convert Tailwind utility classes to semantic CSS classes.

**Key design tokens to capture (from React styles.css:21-110):**
```css
:root {
  /* Palette */
  --brick: oklch(0.512 0.181 27.5);
  --ebony: oklch(0.451 0.028 138);
  --champagne: oklch(0.938 0.043 82);
  --ink: oklch(0.15 0 0);
  /* Semantic colors */
  --background: oklch(0.985 0.012 82);
  --foreground: oklch(0.18 0.01 60);
  --primary: var(--brick);
  --primary-foreground: oklch(0.98 0.01 82);
  /* ... all remaining oklch values from :root and .dark */
  /* Typography */
  --font-display: 'Fraunces', ui-serif, Georgia, serif;
  --font-sans: 'Inter', ui-sans-serif, system-ui, sans-serif;
  --radius: 1rem;
}

.dark {
  /* Dark mode overrides from styles.css:112-145 */
}

/* Utility classes converted from Tailwind */
.container-x { margin-inline: auto; padding-inline: 1.5rem; max-width: 80rem; }
.text-balance { text-wrap: balance; }
.grain-bg { ... }
```

**Every Tailwind class used across components must have a corresponding CSS utility or component class.** Scan all 9 `.tsx` components for Tailwind classes and create CSS classes for each pattern. Group by component section:

- `.hero-*` (hero-eyebrow, hero-title, hero-lede, hero-cta)
- `.nav-*` (nav-link, nav-logo, nav-toggle)
- `.about-reveal`, `.about-stat`
- `.skill-head`, `.skill-card`, `.skill-grid`
- `.proj-head`, `.proj-card`
- `.exp-head`, `.exp-item`, `.exp-timeline`, `.exp-line-fill`
- `.blog-head`, `.blog-card`, `.blog-grid`
- `.contact-reveal`, `.contact-orb-a`, `.contact-orb-b`
- `.footer-*`

**Base styles (from styles.css:147-176):**
```css
* { border-color: var(--border); }
body { background-color: var(--background); color: var(--foreground); font-family: var(--font-sans); ... }
h1, h2, h3, h4 { font-family: var(--font-display); letter-spacing: -0.02em; }
html { scroll-behavior: smooth; }
@media (prefers-reduced-motion: reduce) { ... }
```

### Task 3.3: Create `assets/css/wordpress-editor.css`

Styles for the WP admin editor to match the frontend design (Fraunces heading font, proper colors, etc). Enqueued on `add_editor_style()`.

### Task 3.4: Create `header.php`

```php
<!DOCTYPE html>
<html <?php language_attributes(); ?> class="<?php echo is_user_logged_in() ? '' : ''; ?>">
<head>
  <meta charset="<?php bloginfo('charset'); ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <?php wp_head(); ?>
  <!-- Dark mode inline script (same as React __root.tsx:113-116) -->
  <script>
    try {
      var t = localStorage.getItem('theme');
      var m = window.matchMedia('(prefers-color-scheme: dark)').matches;
      if (t === 'dark' || (!t && m)) { document.documentElement.classList.add('dark'); }
    } catch(e) {}
  </script>
</head>
<body <?php body_class('antialiased'); ?>>
<?php wp_body_open(); ?>
<div class="relative">
  <?php get_template_part('template-parts/navigation'); ?>
  <main>
```

### Task 3.5: Create `footer.php`

```php
  </main>
  <?php get_template_part('template-parts/footer'); ?>
</div><!-- .relative -->
<?php wp_footer(); ?>
</body>
</html>
```

### Task 3.6: Create `functions.php` — Asset Enqueueing

```php
<?php
function malachy_enqueue_assets() {
  // Google Fonts
  wp_enqueue_style('malachy-fonts', 'https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600;9..144,700&family=Inter:wght@400;500;600;700&display=swap', [], null);

  // Main stylesheet
  wp_enqueue_style('malachy-main', get_template_directory_uri() . '/assets/css/main.css', [], '1.0.0');

  // GSAP vendor (bundled copy)
  wp_enqueue_script('gsap', get_template_directory_uri() . '/assets/js/vendor/gsap.min.js', [], '3.15.0', false);
  wp_enqueue_script('gsap-scroll-trigger', get_template_directory_uri() . '/assets/js/vendor/ScrollTrigger.min.js', ['gsap'], '3.15.0', false);

  // Animation modules (enqueued on front-page only except navigation which is global)
  if (is_front_page()) {
    $animations = ['navigation', 'hero', 'about', 'skills', 'projects', 'experience', 'contact', 'global'];
    foreach ($animations as $a) {
      wp_enqueue_script("malachy-anim-$a", get_template_directory_uri() . "/assets/js/animations/{$a}.js", ['gsap', 'gsap-scroll-trigger'], '1.0.0', true);
    }
  } else {
    wp_enqueue_script('malachy-anim-navigation', get_template_directory_uri() . '/assets/js/animations/navigation.js', ['gsap', 'gsap-scroll-trigger'], '1.0.0', true);
  }

  // Contact form JS (if needed)
  wp_enqueue_script('malachy-contact', get_template_directory_uri() . '/assets/js/contact.js', [], '1.0.0', true);
  wp_localize_script('malachy-contact', 'malachyAjax', ['ajaxurl' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('malachy_contact_nonce')]);

  // Theme supports
  add_theme_support('title-tag');
  add_theme_support('post-thumbnails');
  add_theme_support('html5', ['search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script']);
  add_theme_support('custom-logo');
  add_theme_support('responsive-embeds');
  add_editor_style('assets/css/wordpress-editor.css');
}
add_action('wp_enqueue_scripts', 'malachy_enqueue_assets');
```

---

## Phase 4: Build All Section Template Parts

### Task 4.1: `template-parts/navigation.php`
**Source:** `Navigation.tsx` — glassmorphism header, 7 nav links, theme toggle, mobile hamburger.

Critical CSS classes needed:
- `.nav-header`, `.nav-scrolled` (backdrop blur, bg on scroll)
- `.nav-logo`, `.nav-links`, `.nav-link`, `.nav-toggle-dark`, `.nav-toggle-mobile`
- `.nav-mobile-menu` (animated dropdown)

Key behaviors:
- On scroll > 12px, add `nav-scrolled` class
- Theme toggle: flip `.dark` class on `<html>`, save to localStorage
- Mobile menu toggle: `nav-open` class

JS for scroll detection in `navigation.js`.

### Task 4.2: `template-parts/section-hero.php`
**Source:** `Hero.tsx` — Full-screen section with portrait, text column, skill marquee.

Key elements:
- Warm champagne grain background
- Portrait image (right-aligned, overlapping)
- Lighting gradients (left key light, readability veil, dark floor)
- Content: "Portfolio · 2026" eyebrow, "Hi, I'm Malachy.", titles, lede paragraph
- Two CTA buttons: "View My Projects" (primary, link to #projects), "Download CV" (outline)
- Bottom skill badge marquee (auto-scrolling)

**GSAP animations (in `hero.js`):**
- Intro timeline: eyebrow, title spans stagger, lede, CTAs, portrait fade
- Continuous marquee scroll (seamless loop)
- Scroll-linked: portrait scale from 0.86→1.18 / xPercent + / yPercent, text fades + translates up, marquee fades out
- Hero pin (desktop/tablet only — mobile pinning exception via matchMedia)

### Task 4.3: `template-parts/section-about.php`
**Source:** `About.tsx`

Key elements:
- About eyebrow + heading "Engineering quality, from code to server rack."
- Two paragraphs of bio text
- Three stat cards: 8+ Years, 40+ Projects, 99.9% Uptime
- About illustration image (right side, parallax on scroll)

### Task 4.4: `template-parts/section-skills.php`
**Source:** `Skills.tsx` — 8 skill cards in a 4-column grid.

8 skills with inline SVG icons:
1. QA Automation (Playwright, Cypress, Pytest)
2. System Administration (Linux, Nginx, Bash)
3. Containerization (Docker, Compose, CI)
4. Scripting (Python, Shell)
5. Version Control (Git workflows)
6. Linux Servers (Debian/Ubuntu ops)
7. WordPress (themes, plugins)
8. LLM & Agentic Coding

Each card: icon (rounded bg), title, description. Hover: lift -1px, shadow increase.

**GSAP:** Cards stagger upward on scroll reveal.

### Task 4.5: `template-parts/section-projects.php`
**Source:** `Projects.tsx` — 3 stacked sticky cards.

Projects (hardcoded now, will come from CPT in Phase 5):
1. Automated QA Suite (Playwright, TypeScript, Docker, GitHub Actions)
2. Infrastructure Console (Linux, Docker, Python, Nginx)
3. WordPress Theme Framework (WordPress, PHP, Tailwind, Vite)

Each card: 2-column grid (image side + text side) with tag, title, desc, tech tags, Live Demo + GitHub buttons.

**GSAP:** Stacked sticky card effect — each card pins, current scales to 0.92, next slides over. Desktop/tablet only (mobile pinning exception).

### Task 4.6: `template-parts/section-experience.php`
**Source:** `Experience.tsx` — Timeline with 4 milestones.

Milestones:
1. 2024—Now: Senior QA Engineer & Systems Consultant, IMaD Consulting
2. 2021—2024: QA Automation Engineer, Enterprise SaaS
3. 2019—2021: System Administrator, Managed Services Firm
4. 2017—2019: IT Support Specialist, Regional Bank

**GSAP:** Vertical line fills from top (scaleY 0→1), items stagger in on scroll.

### Task 4.7: `template-parts/section-blog-preview.php`
**Source:** `BlogPreview.tsx` — 3-card grid, links to `/blog`.

Uses `WP_Query` to fetch latest 3 posts. If no posts exist, show placeholder text.

**GSAP:** Cards stagger in on scroll.

### Task 4.8: `template-parts/section-contact.php`
**Source:** `Contact.tsx` — Form + social links + animated background orbs.

Form fields: Name, Email, Message (textarea), Submit button.
Social links: email, GitHub, LinkedIn, X/Twitter.

**GSAP:** Fade-up stagger, continuous orb float animation (CSS or JS).

**Backend:** Contact form submits via `admin-ajax.php` with nonce verification, honeypot, rate-limiting. Uses `wp_mail()`.

### Task 4.9: `template-parts/footer.php`
**Source:** `Footer.tsx`

Simple: copyright year + "Available for select engagements" with pulsing dot.

### Task 4.10: `front-page.php`
```php
<?php
get_header();
get_template_part('template-parts/section-hero');
get_template_part('template-parts/section-about');
get_template_part('template-parts/section-skills');
get_template_part('template-parts/section-projects');
get_template_part('template-parts/section-experience');
get_template_part('template-parts/section-blog-preview');
get_template_part('template-parts/section-contact');
get_footer();
```

### Task 4.11: `home.php` (Blog listing)
**Source:** `blog.tsx` — Full blog listing page.

### Task 4.12: `single.php` + `content-single.php`
Standard WordPress single post template. The content pulls `the_content()` directly (no Gutenberg markup expected since block editor is disabled).

### Task 4.13: `404.php`
Matches the 404 component from `__root.tsx` — centered 404 message with "Go home" link styled as primary button.

---

## Phase 5: Implement Custom Post Types & Taxonomies

### Task 5.1: Register CPTs in `functions.php` or `inc/post-types.php`

- **Projects** (`project`) — title, excerpt, thumbnail, custom meta, categories, tech stack taxonomy
- **Experience** (`experience`) — title, excerpt, custom meta (company, role, duration, location, achievement)
- **Skills** (`skill`) — title, custom meta (skill level, icon selector), skill categories taxonomy
- **Testimonials** (`testimonial`) — future-ready, register but minimal
- **Publications** (`publication`) — future-ready, register but minimal

### Task 5.2: Register custom taxonomies

- **Project Categories** (`project_category`) — hierarchical, for `project`
- **Technology Stack** (`tech_stack`) — non-hierarchical, for `project`
- **Skill Categories** (`skill_category`) — hierarchical, for `skill`

### Task 5.3: Create `inc/post-types.php`

Contains all `register_post_type()` and `register_taxonomy()` calls. Include in `functions.php` via `require_once`.

**Data seeding:** Create WP-CLI commands or a one-time migration script that inserts the existing portfolio content (the 3 projects, 4 experience milestones, 8 skills, 3 blog posts) as CPT entries so the front-page renders from dynamic data.

---

## Phase 6: Custom Fields (Native Meta Boxes)

### Task 6.1: Create `inc/meta-boxes.php`

All meta boxes use `add_meta_box()` + `register_meta()`:

**Project fields:**
- `_project_url` (text)
- `_github_url` (text)
- `_demo_url` (text)
- `_tech_stack` (repeater: array of strings, serialized)
- `_project_duration` (text, e.g. "3 months")

**Experience fields:**
- `_company` (text)
- `_role` (text)
- `_location` (text)
- `_duration` (text, e.g. "Jan 2021 — Dec 2024")
- `_achievement` (textarea)

**Skill fields:**
- `_skill_level` (number, 1-100)
- `_skill_icon` (select from predefined SVG set matching the 8 original icons)

**Hero/Site fields (attached to Settings or Page 0):**
- `_hero_subtitle` (text)
- `_cta_buttons` (repeater: array of {label, url, style})
- `_portrait_image` (media attachment ID)
- `_resume_download` (media attachment ID)
- Social links (repeater: array of {platform, url})

### Task 6.2: Save/validation callbacks

Each meta box needs proper `save_post` callback with nonce verification, sanitization, and `update_post_meta()`.

### Task 6.3: Repeater UI

For tech stack and social links, create a simple JavaScript-enhanced meta box that allows adding/removing rows. Store as serialized array meta.

---

## Phase 7: GSAP Animation Modules

### Task 7.1: Download GSAP vendor files

```bash
npm pack gsap@3.15.0  # on staging VPS
# Extract gsap.min.js and ScrollTrigger.min.js to assets/js/vendor/
```

### Task 7.2: Create AnimationManager architecture

**File:** `assets/js/animations/AnimationManager.js`

A lightweight manager that:
- Registers sections (each returns a cleanup function)
- Provides `matchMedia` helper for breakpoint gating
- Checks `prefers-reduced-motion` globally
- Refreshes ScrollTrigger on demand
- Cleans up all animations on section unload

```js
const AnimationManager = {
  cleanups: [],
  reduced: window.matchMedia('(prefers-reduced-motion: reduce)').matches,
  mq: window.matchMedia('(min-width: 768px)'), // desktop/tablet breakpoint

  register(fn) {
    if (this.reduced) return;
    const cleanup = fn(this);
    if (typeof cleanup === 'function') this.cleanups.push(cleanup);
  },

  destroyAll() {
    this.cleanups.forEach(fn => fn());
    this.cleanups = [];
    ScrollTrigger.getAll().forEach(t => t.kill());
  }
};
```

### Task 7.3: `assets/js/animations/hero.js`

Full replication of `Hero.tsx` `useEffect`:
- Intro timeline (eyebrow, title spans, lede, CTAs, portrait)
- Continuous marquee scroll via `gsap.to` with `repeat: -1`
- Scroll-linked pin + scrub animations:
  - Portrait scale 0.86→1.18, xPercent 3→-4, yPercent 4→-6
  - Text column y: 0→-120, opacity: 1→0.15
  - Marquee y: 0→40, opacity: 1→0
- **Desktop/tablet only:** gated behind `window.matchMedia('(min-width: 768px)')`. Below that, use simple fade-in.

### Task 7.4: `assets/js/animations/about.js`

- `.about-reveal` elements: y:40, opacity:0 → fade up, stagger 0.12
- Image parallax: `gsap.to(imgRef, { y: -80, scrub: 0.5 })` — only if !reduced

### Task 7.5: `assets/js/animations/skills.js`

- `.skill-head` elements: fade up
- `.skill-card` elements: y:40, stagger:0.08, scroll-triggered

### Task 7.6: `assets/js/animations/projects.js`

- `.proj-head`: fade up
- Stacked sticky cards: for each card except last, scale→0.92, opacity→0.55, y→-30 relative to next card's scroll position. **Desktop/tablet only.** Falls back to simple stagger on mobile.

### Task 7.7: `assets/js/animations/experience.js`

- `.exp-head`: fade up
- `.exp-item`: y:40, stagger:0.15
- `.exp-line-fill`: scaleY 0→1, scrub:0.5

### Task 7.8: `assets/js/animations/contact.js`

- `.contact-reveal`: fade up stagger
- Orbs: continuous float animation (CSS keyframes or GSAP yoyo)

### Task 7.9: `assets/js/animations/navigation.js`

- Scroll detection: add/remove `.nav-scrolled` class based on scroll position
- This is a lightweight script (no heavy GSAP needed for nav, just class toggling)

### Task 7.10: `assets/js/animations/global.js`

- Reduce motion check
- Refresh ScrollTrigger on window resize (debounced)
- Any global parallax or shared utility

---

## Phase 8: Performance Optimization

- **Minify assets:** Use a one-time build step on the staging VPS to minify all CSS/JS under `assets/`. Store the minified versions alongside source (or replace source if not needed).
- **Image optimization:** Run `jpegoptim` / `optipng` / `webp` conversion on all assets. Provide `srcset` for responsive images.
- **Lazy loading:** All section images get `loading="lazy"`.
- **Font loading:** Preconnect to Google Fonts, or optionally self-host Fraunces + Inter as woff2 for better performance.
- **CSS critical path:** Inline critical CSS in `<head>` for above-the-fold (hero section) to avoid layout shift.
- **GSAP bundle size:** Only enqueue animation modules on pages that need them (front-page gets all, blog pages only get navigation).

---

## Phase 9: Accessibility

- All links have `aria-label` where needed
- Focus states: visible `:focus-visible` outlines (maintain design system colors)
- Keyboard navigation: nav links, CTA buttons, form inputs all navigable via Tab
- Semantic HTML: `section` with `id`, `article` for projects/posts, `nav` for nav, `main` for content
- Heading hierarchy: h1 (hero title) → h2 (section headings) → h3 (card titles)
- Form inputs have associated labels
- `prefers-reduced-motion` respected throughout all animation modules
- Skip-to-content link added at top of page

---

## Phase 10: Staging Validation

- Spin up the WordPress container with the theme active
- Verify every section renders with correct layout, colors, typography
- **Visual regression check:** side-by-side with the original React app (build and serve the React app)
- Verify all GSAP animations fire correctly (hero pin, stacked cards, timeline fill, etc.)
- Verify dark mode toggle works
- Verify mobile responsiveness and the mobile pinning exception
- Verify contact form submission (test mail delivery)
- Run Lighthouse audit (performance, accessibility, SEO)
- Run `phpcs` against WordPress Coding Standards
- **User approval checkpoint — DO NOT PROCEED PAST THIS WITHOUT SIGN-OFF**

---

## Phase 11: Package for Shared Hosting

Build script (runs once on staging VPS):
1. Minify CSS/JS
2. Replace all absolute staging URLs with relative paths
3. Strip: `node_modules/`, `.git/`, `docker-compose.yml`, `.env`, any build configs
4. Create zip archive of the theme

**Packaging output:**
```
malachy-portfolio.zip
├── style.css
├── index.php
├── functions.php
├── front-page.php
├── home.php
├── single.php
├── page.php
├── 404.php
├── header.php
├── footer.php
├── inc/
│   ├── post-types.php
│   └── meta-boxes.php
├── template-parts/
│   ├── navigation.php
│   ├── section-hero.php
│   ├── section-about.php
│   ├── section-skills.php
│   ├── section-projects.php
│   ├── section-experience.php
│   ├── section-blog-preview.php
│   ├── section-contact.php
│   └── footer.php
├── assets/
│   ├── css/
│   │   ├── main.css
│   │   └── wordpress-editor.css
│   ├── js/
│   │   ├── vendor/
│   │   │   ├── gsap.min.js
│   │   │   └── ScrollTrigger.min.js
│   │   ├── animations/
│   │   │   ├── AnimationManager.js
│   │   │   ├── navigation.js
│   │   │   ├── hero.js
│   │   │   ├── about.js
│   │   │   ├── skills.js
│   │   │   ├── projects.js
│   │   │   ├── experience.js
│   │   │   ├── contact.js
│   │   │   └── global.js
│   │   └── contact.js
│   ├── images/
│   │   ├── malachy-portrait.png
│   │   ├── about-illustration.png
│   │   ├── project-qa.png
│   │   ├── project-sysadmin.png
│   │   └── project-wordpress.png
│   └── fonts/  (optional, if self-hosting)
└── languages/  (theme text domain)
```

---

## Phase 12: Final Documentation

Docs to produce:

| # | Document | Content |
|---|----------|---------|
| 1 | `docs/theme-structure.md` | Complete file listing + purpose of each file |
| 2 | `docs/theme-setup.md` | How to install (Upload Theme → Activate → Set front page) |
| 3 | `docs/plugin.md` | If a companion plugin is separated (likely not needed) |
| 4 | `docs/installation-guide.md` | Step-by-step from WP admin |
| 5 | `docs/deploy-shared-hosting.md` | FTP upload, permalink flush, mail test checklist |
| 6 | `docs/staging-development.md` | Docker Compose, WP-CLI workflow for the Netcup VPS |
| 7 | `docs/animation-architecture.md` | AnimationManager, matchMedia gating, reduced motion, mobile pinning exception rationale |
| 8 | `docs/maintenance.md` | How to add new projects/skills/experience, update content |
| 9 | `docs/packaging-guide.md` | Exactly which files to strip before zip, post-upload smoke test |

---

## Key Risks & Mitigations

| Risk | Mitigation |
|------|-----------|
| **Tailwind dependency** — React uses Tailwind utility classes extensively. We cannot use Tailwind on the WP side (no build step). | Manually convert every Tailwind class to semantic CSS classes. This is the single largest scope item. |
| **Portrait image URL** — Lives on Lovable CDN, not locally. | Download the image once and bundle it. If URL is inaccessible, need user to re-upload portrait. |
| **GSAP ScrollTrigger pin** — complex scroll hijacking on hero + projects. Requires careful matchMedia gating. | AnimationManager handles breakpoint logic. Test thoroughly on mobile. |
| **Radix UI primitives** — React uses Radix for interactive elements (dropdown, dialog, etc.). WordPress doesn't have these. | Navigation mobile menu, theme toggle, and any interactive elements must be implemented with vanilla JS or lightweight custom solutions. |
| **Inline SVG icons** — Skills section has 8 custom SVGs embedded in TSX. | Convert SVGs to a PHP sprite sheet or include inline in template-part. |
| **Dark mode persistence** — Requires JS for localStorage + system preference detection. | The inline script in `<head>` handles this identically to the React version. |
| **Environment parity** — Docker staging vs shared hosting (PHP version, mod_rewrite, mail). | Document all differences. Test mail independently. Don't let staging failures force unnecessary plugin deps. |

---

## Open Questions

1. **Portrait image:** The `portrait.asset.json` references a Lovable CDN URL. Can you provide the actual portrait PNG file, or should I attempt to download it from the CDN URL? The URL in the JSON is: `/__l5e/assets-v1/6300ed05-f1bf-44fe-8485-1d8438e1ae49/malachy-portrait.png`
2. **Domain:** What domain/subdomain should the staging WordPress instance live at? (e.g., `testdrive.malachy.zubbystudio.site`)
3. **Contact email:** The React has `hello@imadconsulting.com` — is this the correct email for the contact form, or should it be configurable?
4. **Existing WordPress install:** Is there an existing WordPress setup on this VPS, or should I create a fresh one via Docker?
5. **GSAP license:** GSAP 3.15 requires a license for commercial use on a public website. Do you have a GSAP license, or should I note "GSAP CDN with local fallback" given this will be deployed on a public portfolio site?
