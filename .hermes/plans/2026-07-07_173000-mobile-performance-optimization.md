# Mobile Performance & UX Optimization Plan

> **For Hermes:** Implement using subagent-driven-development.
> **Goal:** Improve mobile Lighthouse score (21→70+), add dark+glassmorphism hero on mobile, remove all animations on mobile, add browser caching.
> **Architecture:** CSS media queries for mobile-only hero redesign. Server-side script enqueuing (wp_is_mobile) to skip GSAP/animations on mobile. .htaccess for caching headers. navigation.js fallback to native smooth scroll.
> **Tech Stack:** WordPress, PHP, CSS media queries, Apache .htaccess, native scrollIntoView
> **Audience:** Implementer with WP+CSS+Apache knowledge, no prior context.

---

### Preconditions (verified)
- Docker WP container is Apache-based — .htaccess and mod_headers are supported
- Theme root: `/home/zubbyik/wordpress_project/malachy-portfolio/`
- WP root (mapped volume): `/home/zubbyik/wordpress_project/`
- GSAP 3.12.5 is a bundled vendor file (~70KB)
- Current hero uses `hero-portrait-wrap > img.hero-portrait` (1.4MB PNG)

---

### Task 1: Mobile hero — dark background + glassmorphism card (CSS only)

**Objective:** On screens < 768px, hide the portrait image and overlay the hero text in a glassmorphism card against a dark background.

**Files:**
- Modify: `assets/css/main.css` (add mobile hero override block)

**Step 1: Add mobile-only hero overrides**

Add at the end of main.css, before any final closing — or in a new section at the very bottom:

```css
/* =========================================================================
   MOBILE HERO — Dark background + glassmorphism
   ========================================================================= */
@media (max-width: 767px) {
  /* Hide portrait image on mobile */
  .hero-portrait-wrap {
    display: none !important;
  }

  .hero-light-left,
  .hero-veil,
  .hero-floor,
  .hero-floor-line {
    display: none !important;
  }

  /* Dark solid background for hero on mobile */
  .hero-section {
    background-color: var(--background) !important;
    background-image: radial-gradient(
      circle at 20% 30%,
      color-mix(in oklab, var(--primary) 12%, transparent) 0%,
      transparent 60%
    );
  }

  /* Glassmorphism text card */
  .hero-content {
    padding: 2rem 1.5rem;
    min-height: 90vh;
  }

  .hero-text-col {
    max-width: 100%;
    background: color-mix(in oklab, var(--card) 70%, transparent);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    border: 1px solid color-mix(in oklab, var(--border) 50%, transparent);
    border-radius: 1.5rem;
    padding: 1.75rem;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.12);
  }

  /* Ensure text is fully legible on the glass card */
  .hero-text-col * {
    color: var(--foreground);
  }
}
```

**Step 2: Verify in browser**

Open the site on mobile emulation (412px width). The portrait should be gone, and the hero text should sit in a frosted glass card against a dark background with a subtle primary-color glow.

---

### Task 2: Remove all animations on mobile — functions.php

**Objective:** On mobile (server-side detected), don't enqueue GSAP, ScrollTrigger, or any animation JS files. Save ~70KB + parse time.

**Files:**
- Modify: `functions.php` (enqueue logic)

**Step 1: Conditionally enqueue animation scripts**

Find the malachy_enqueue_assets function. Wrap the GSAP/ScrollTrigger/animation script enqueues inside a `! wp_is_mobile()` check.

Current code structure (approximate lines 50–end of enqueue_assets):

```php
function malachy_enqueue_assets() {
    // Google Fonts — always
    // main.css — always

    // GSAP + ScrollTrigger + animations — only on desktop/tablet
    if ( ! wp_is_mobile() ) {
        wp_enqueue_script('gsap', ...);
        wp_enqueue_script('gsap-scroll-trigger', ...);
        wp_enqueue_script('malachy-anim-manager', ...);
        wp_enqueue_script('malachy-anim-hero', ...);
        wp_enqueue_script('malachy-anim-about', ...);
        wp_enqueue_script('malachy-anim-skills', ...);
        wp_enqueue_script('malachy-anim-projects', ...);
        wp_enqueue_script('malachy-anim-experience', ...);
        wp_enqueue_script('malachy-anim-contact', ...);
        wp_enqueue_script('malachy-anim-global', ...);
    }

    // navigation.js — always (rewritten to handle GSAP absence)
    // contact.js (AJAX handler) — always
}
```

Specifically, the scripts to gate behind `! wp_is_mobile()`:
1. `gsap` (gsap.min.js)
2. `gsap-scroll-trigger` (ScrollTrigger.min.js)
3. `malachy-anim-manager` (AnimationManager.js)
4. `malachy-anim-hero` (hero.js)
5. `malachy-anim-about` (about.js)
6. `malachy-anim-skills` (skills.js)
7. `malachy-anim-projects` (projects.js)
8. `malachy-anim-experience` (experience.js)
9. `malachy-anim-contact` (contact.js — the animation one)
10. `malachy-anim-global` (global.js)

Keep outside the gate:
- `malachy-navigation` (navigation.js — needs rewrite for mobile)
- `malachy-contact` (contact.js — AJAX form handler)

**Step 2: Verify scripts are not loaded on mobile**

View the page in mobile emulation. Check that no gsap or animation scripts appear in `<head>`. Run `document.querySelectorAll('script[src*="gsap"], script[src*="animations/"]')` in console — should return empty.

---

### Task 3: Rewrite navigation.js for mobile fallback

**Objective:** navigation.js currently uses `gsap.to(window, { scrollTo: ... })` for smooth scroll. On mobile (no GSAP), fall back to native `scrollIntoView({ behavior: 'smooth' })`.

**Files:**
- Rewrite: `assets/js/animations/navigation.js`

**Step 1: Add GSAP-availability check**

```javascript
/**
 * Navigation scroll handler.
 * Uses GSAP scrollTo when available (desktop), falls back to
 * native scrollIntoView when GSAP is not loaded (mobile).
 */
document.addEventListener('DOMContentLoaded', function () {
  const hasGsap = typeof gsap !== 'undefined';

  // Smart anchor click handler
  document.querySelectorAll('[data-section-link]').forEach(function (link) {
    link.addEventListener('click', function (e) {
      const href = this.getAttribute('href');
      const sectionId = href ? href.replace(/^.*#/, '') : '';
      const target = document.getElementById(sectionId);

      if (target) {
        e.preventDefault();

        if (hasGsap) {
          gsap.to(window, {
            duration: 1,
            scrollTo: { y: target, offsetY: 80 },
            ease: 'power3.inOut',
          });
        } else {
          target.scrollIntoView({ behavior: 'smooth', block: 'start' });
          // Offset for fixed header: scroll up a bit more
          window.scrollBy(0, -80);
        }

        // Update URL hash without scrolling
        history.pushState(null, '', href);

        // Close mobile menu if open
        const header = document.getElementById('site-header');
        if (header && header.classList.contains('nav-open')) {
          header.classList.remove('nav-open');
        }
      }
      // If target doesn't exist, allow native navigation (cross-page)
    });
  });

  // Page-load hash scroll handler
  if (window.location.hash) {
    const id = window.location.hash.replace('#', '');
    const el = document.getElementById(id);
    if (el) {
      // Wait for ScrollTrigger to settle on desktop, or scroll immediately on mobile
      setTimeout(function () {
        if (hasGsap && typeof ScrollTrigger !== 'undefined') {
          ScrollTrigger.refresh();
          gsap.to(window, {
            duration: 0.6,
            scrollTo: { y: el, offsetY: 80 },
            ease: 'power2.out',
            onComplete: function () {
              ScrollTrigger.refresh();
            },
          });
        } else {
          el.scrollIntoView({ behavior: 'smooth', block: 'start' });
          window.scrollBy(0, -80);
        }
      }, 300);
    }
  }

  // Nav background on scroll — no GSAP needed, pure CSS
  // (already handled by CSS: .nav-scrolled class)
  if (!hasGsap) {
    // Use IntersectionObserver for scroll-based nav background
    const header = document.getElementById('site-header');
    if (header) {
      const observer = new IntersectionObserver(
        function (entries) {
          entries.forEach(function (entry) {
            if (!entry.isIntersecting) {
              header.classList.add('nav-scrolled');
            } else {
              header.classList.remove('nav-scrolled');
            }
          });
        },
        { threshold: 0 }
      );
      // Observe a 1px sentinel at the top of the page
      const sentinel = document.createElement('div');
      sentinel.style.position = 'absolute';
      sentinel.style.top = '0';
      sentinel.style.left = '0';
      sentinel.style.width = '1px';
      sentinel.style.height = '1px';
      sentinel.style.pointerEvents = 'none';
      document.body.prepend(sentinel);
      observer.observe(sentinel);
    }
  }
});
```

**Verification:** On desktop, smooth scroll should work as before. On mobile (no GSAP), clicking nav links should smoothly scroll via native API.

---

### Task 4: Add .htaccess browser caching

**Objective:** Set long Cache-Control headers for static assets (images, CSS, JS, fonts) to enable browser caching on repeat visits.

**Files:**
- Create: `/home/zubbyik/wordpress_project/.htaccess` (WordPress root)

**Step 1: Write .htaccess with caching rules**

Create `.htaccess` at the WordPress root. It must preserve existing WordPress rewrite rules and add caching headers:

```apache
# BEGIN Malachy Portfolio Cache Control
<IfModule mod_expires.c>
  ExpiresActive On
  ExpiresByType image/jpg "access plus 1 year"
  ExpiresByType image/jpeg "access plus 1 year"
  ExpiresByType image/png "access plus 1 year"
  ExpiresByType image/gif "access plus 1 year"
  ExpiresByType image/webp "access plus 1 year"
  ExpiresByType image/avif "access plus 1 year"
  ExpiresByType image/svg+xml "access plus 1 year"
  ExpiresByType text/css "access plus 1 year"
  ExpiresByType text/javascript "access plus 1 year"
  ExpiresByType application/javascript "access plus 1 year"
  ExpiresByType font/woff2 "access plus 1 year"
  ExpiresByType font/woff "access plus 1 year"
  ExpiresByType application/font-woff2 "access plus 1 year"
</IfModule>

<IfModule mod_headers.c>
  # 1 year cache for images, CSS, JS, fonts
  <FilesMatch "\.(jpg|jpeg|png|gif|ico|svg|webp|avif|css|js|woff|woff2|ttf|eot)$">
    Header set Cache-Control "public, max-age=31536000, immutable"
  </FilesMatch>

  # HTML and XML: short cache (5 min)
  <FilesMatch "\.(html|htm|xml)$">
    Header set Cache-Control "public, max-age=300, must-revalidate"
  </FilesMatch>
</IfModule>
# END Malachy Portfolio Cache Control

# BEGIN WordPress
# The directives (lines) between "BEGIN WordPress" and "END WordPress" are
# dynamically generated, and should only be modified via WordPress filters.
# Any changes to the directives between these markers will be overwritten.
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
RewriteBase /
RewriteRule ^index\.php$ - [L]
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule . /index.php [L]
</IfModule>
# END WordPress
```

**Step 2: Verify caching headers**

Run: `curl -I https://imadconsult.zubbystudio.shop/wp-content/themes/malachy-portfolio/assets/css/main.css?ver=1.0.5 | grep -i cache`

Expected: `Cache-Control: public, max-age=31536000, immutable`

---

### Task 5: Add meta description for SEO

**Objective:** Lighthouse flagged missing meta description.

**Files:**
- Modify: `header.php`

**Step 1: Add meta description**

In `header.php`, inside `<head>`, after the charset meta tag:

```php
<meta name="description" content="<?php bloginfo('description'); ?>">
```

This uses the WordPress tagline set in Settings → General.

---

### Task 6: Verify everything

**Step 1: Mobile verification (412px viewport)**
- Load the site with Chrome DevTools mobile emulation (Moto G Power)
- Confirm: hero portrait is gone, dark background with glass card visible
- Confirm: no GSAP or animation scripts loaded (check Network tab)
- Confirm: nav links scroll smoothly (native)
- Confirm: blog page loads fast (under 3s on simulated 3G)
- Confirm: contact form works (submit a test message)

**Step 2: Desktop verification (1280px viewport)**
- Confirm: hero portrait loads normally
- Confirm: all GSAP animations work (ScrollTrigger animations visible)
- Confirm: nav links smooth-scroll as before
- Confirm: full page layout unchanged

**Step 3: Caching verification**
```bash
curl -I https://imadconsult.zubbystudio.shop/wp-content/themes/malachy-portfolio/assets/css/main.css?ver=1.0.5
```
Expected header: `Cache-Control: public, max-age=31536000, immutable`

---

### Risks & Open Questions

- **wp_is_mobile()** detects based on user agent server-side. Tablets in landscape (≥768px) are sometimes detected as mobile. This is acceptable — animations are visual polish, not critical.
- **.htaccess** may not take effect if the Docker Apache config disables `.htaccess` override. The official WP image enables it by default, but if not, we'll need to add it to the Docker config. Low risk.
- **Browser cache on first visit** — caching helps repeat visits, not first load. For first-load improvement we'd need image optimization (WebP) and CDN, but those are separate concerns. The Lighthouse test that reported `cache-insight` was measuring a fresh incognito session.

---

### Summary of changes

| File | Action | Purpose |
|------|--------|---------|
| `assets/css/main.css` | Add block | Mobile dark hero + glassmorphism card |
| `functions.php` | Modify | Gate GSAP/animations behind `! wp_is_mobile()` |
| `assets/js/animations/navigation.js` | Rewrite | Fallback to native smooth scroll |
| `wordpress_project/.htaccess` | Create | Browser caching headers for static assets |
| `header.php` | Modify | Add meta description tag |
