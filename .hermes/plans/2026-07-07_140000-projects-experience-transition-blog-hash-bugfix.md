# Bug Fix Plan: Projects→Experience Scroll Transition + Blog Hash Appending

> **For Hermes:** Use subagent-driven-development to implement this plan task-by-task.

**Goal:** Fix two animation/navigation bugs in the Malachy Portfolio WordPress theme.

**Architecture:**
1. **Projects→Experience freeze/jump** — The stacked sticky cards in `#projects` pin via ScrollTrigger with `end: '+=' + (cards.length * vh * 0.8)`. When pin releases, the page snaps abruptly to real scroll position before the Experience section's ScrollTriggers kick in, creating a visible freeze-then-jump.
2. **Blog nav hash appending** — Section links (`#home`, `#about`, etc.) use bare hash hrefs. On the `/blog/` page (separate WP route), clicking these appends the hash to `/blog/#home` instead of navigating to the front page.

**Tech Stack:** PHP (WordPress theme), GSAP 3.12.5, vanilla JS

---

## Context Audit — Preconditions

- Theme root: `/home/zubbyik/wordpress_project/malachy-portfolio/`
- Docker stack: `malachy-wp` + `malachy-db` on `openagile_openagile_network`
- WP_DEBUG is ON (user preference)
- Nav links in `template-parts/navigation.php` have the Blog link as a route link (`href="/blog"`) and all others as bare hash links (`href="#home"`)
- `global.js` (smooth scroll handler) is only enqueued on `is_front_page()` via functions.php
- Projects section uses `ScrollTrigger.create({ pin: true, end: '+=' + (3 * vh * 0.8) })` — 3 cards, 0.8 factor, 2.4 viewports total
- Experience section uses `scrollTrigger.start: 'top 85%'`

---

## Bug 1 Root Cause: Projects→Experience Freeze/Jump

`projects.js` lines 27-34:
```javascript
ScrollTrigger.create({
  trigger: stack,
  start: 'top 5rem',
  end: () => '+=' + (cards.length * window.innerHeight * 0.8),
  pin: true,
  pinSpacing: true,
  anticipatePin: 1,
});
```

**Why it freezes:**
1. Pin end = `3 × vh × 0.8 = 2.4vh`. This is too short — the cards may be taller than 80% of the viewport, so the pin ends while the last card is still only partially revealed.
2. `pinSpacing: true` inserts a margin-bottom spacer equal to the pin distance. When the pin releases, the spacer is removed and the page snaps to the "real" scroll position, which is now much further down.
3. The Experience section's ScrollTrigger fires at `start: 'top 85%'`, but if there's a gap between the released pin and the experience section, nothing animates during that gap → freeze.
4. No easing or overlap — the transition is instantaneous.

**Fix:** 
- Increase pin end to account for actual card heights: use `1.2` factor per card (3.6vh total)
- Add `onLeave` callback to initialize experience animations before the pin fully releases
- De-prioritize the experience ScrollTrigger to fire after pin release

## Bug 2 Root Cause: Blog Hash Appending

`navigation.php` lines 10-14:
```php
array( 'href' => '#home', 'label' => 'Home' ),
array( 'href' => '#about', 'label' => 'About' ),
array( 'href' => '#skills', 'label' => 'Skills' ),
array( 'href' => '#projects', 'label' => 'Projects' ),
array( 'href' => '#experience', 'label' => 'Experience' ),
array( 'href' => '/blog', 'label' => 'Blog', 'route' => true ),  // route link
array( 'href' => '#contact', 'label' => 'Contact' ),
```

`global.js` lines 37-47:
```javascript
document.querySelectorAll('a[href^="#"]').forEach(link => {
  link.addEventListener('click', function (e) {
    const target = document.querySelector(this.getAttribute('href'));
    if (target) { e.preventDefault(); ... smooth scroll ... }
  });
});
```

**On the front page:** smooth scrolling works fine.
**On `/blog/` page:** `global.js` is NOT enqueued (front-page-only). Clicking `#home` triggers default browser hash navigation → URL becomes `/blog/#home`. Since `#home` element doesn't exist in `home.php`, nothing visible happens but the URL is polluted.

**Fix:**
- Change all bare-hash links to absolute-path-hash links: `href="/"` for Home, `href="/#about"` for About, etc.
- In `global.js`, make the smooth scroll handler smart: if the target exists on current page, smooth-scroll with `history.pushState` update; otherwise, let normal navigation happen (which loads the front page with the hash fragment).
- Add `history.pushState` to update URL during smooth-scroll so bookmarks work.

---

## Implementation Plan

### Task 1: Fix projects→experience pin transition

**Objective:** Increase pin end and add smooth overlap so the transition from the last project card to the experience section is fluid.

**Files:**
- Modify: `assets/js/animations/projects.js` (lines 24-34)
- Modify: `assets/js/animations/experience.js` (add overlap)

**Step 1: Edit projects.js — fix pin end and add onLeave**

Change the ScrollTrigger pin creation from:
```javascript
end: () => '+=' + (cards.length * window.innerHeight * 0.8),
```
to:
```javascript
end: () => '+=' + (stack.offsetHeight + window.innerHeight * 0.5),
```

This makes the pin last exactly as long as the stack content height, plus half a viewport of breathing room for a smooth transition.

Also add `onLeave` that refreshes ScrollTrigger to activate experience animations:
```javascript
onLeave: () => {
  ScrollTrigger.refresh();
},
```

**Step 2: Edit experience.js — stagger priority to fire after pin release**

Add `refreshPriority: -10` to the experience timeline's ScrollTrigger config so it refreshes after the pin trigger.

Change the line fill ScrollTrigger to:
```javascript
scrollTrigger: {
  trigger: timeline,
  start: 'top 85%',
  end: 'bottom 20%',
  scrub: 1.5,
  id: 'exp-line',
  refreshPriority: -10,
},
```

And each milestone item:
```javascript
scrollTrigger: {
  trigger: item,
  start: 'top 85%',
  end: 'top 50%',
  scrub: 1,
  refreshPriority: -10,
},
```

**Verification:**
1. Load `https://imadconsult.zubbystudio.shop/`
2. Scroll past the 3 project cards into the experience section
3. Expected: smooth transition — last project card fades out as first experience milestone fades in, no freeze, no jump

---

### Task 2: Fix nav links — use absolute paths with hashes

**Objective:** Change all bare-hash nav links to absolute-path-hash links so they work correctly from non-front-page routes.

**Files:**
- Modify: `template-parts/navigation.php` (nav_links array)

**Step 1: Edit navigation.php**

Change the nav_links array from bare hashes to absolute paths:

```php
$nav_links = array(
    array( 'href' => '/', 'label' => 'Home', 'section' => 'home' ),
    array( 'href' => '/#about', 'label' => 'About', 'section' => 'about' ),
    array( 'href' => '/#skills', 'label' => 'Skills', 'section' => 'skills' ),
    array( 'href' => '/#projects', 'label' => 'Projects', 'section' => 'projects' ),
    array( 'href' => '/#experience', 'label' => 'Experience', 'section' => 'experience' ),
    array( 'href' => '/blog', 'label' => 'Blog', 'route' => true ),
    array( 'href' => '/#contact', 'label' => 'Contact', 'section' => 'contact' ),
);
```

Add a `section` key to each hash-based link that stores the target section ID.

**Step 2: Update rendering logic**

Add a new rendering case for `section` links in both desktop and mobile nav:

```php
<?php elseif ( isset( $link['section'] ) ) : ?>
    <a href="<?php echo esc_url( home_url( $link['href'] ) ); ?>" class="nav-desktop-link" data-section-link="<?php echo esc_attr( $link['section'] ); ?>">
        <?php echo esc_html( $link['label'] ); ?>
    </a>
```

Update the desktop rendering to handle three types:
1. `route` → full URL (Blog)
2. `section` → absolute path with hash, `data-section-link` attribute
3. Default → (shouldn't happen after change, but keep as fallback)

Do the same for mobile menu links.

**Verification:**
1. Visit `https://imadconsult.zubbystudio.shop/blog/`
2. Click "Home" in the nav
3. Expected: navigates to `https://imadconsult.zubbystudio.shop/` (not `/blog/#home`)
4. Click "About" from front page
5. Expected: smooth scrolls to #about, URL updates to `/#about`

---

### Task 3: Update smooth scroll handler for absolute-path-hash links

**Objective:** Update `global.js` to intercept both `href^="#"` and `href*="/#"` links, with smart fallback when target section doesn't exist on current page.

**Files:**
- Modify: `assets/js/animations/global.js` (smooth scroll handler)

**Step 1: Rewrite the smooth scroll handler**

Replace the existing `a[href^="#"]` handler with:

```javascript
// ---- Smart Anchor Navigation ----
document.querySelectorAll('a[href^="#"], a[href*="/#"]').forEach(link => {
    link.addEventListener('click', function (e) {
        const href = this.getAttribute('href');
        const sectionId = href.split('#')[1];
        if (!sectionId) return;

        const target = document.getElementById(sectionId);
        if (target) {
            e.preventDefault();
            const offset = 100;
            const top = target.getBoundingClientRect().top + window.scrollY - offset;
            window.scrollTo({ top, behavior: 'smooth' });
            // Update URL without page reload
            history.pushState(null, '', '/' + (sectionId === 'home' ? '' : '#' + sectionId));
        }
        // If target doesn't exist (e.g., on /blog/ page), let default
        // browser navigation load the front page with the hash fragment.
    });
});
```

**Key changes:**
- Also catches `href*="/#"` links (absolute-path-hash)
- `history.pushState` updates URL to `/#about`, etc. for shareability
- If section element doesn't exist, JS does nothing → browser navigates to the `href` URL normally

**Step 2: Ensure this runs on ALL pages, not just front page**

In `functions.php`, add `global.js` to the non-front-page enqueue block, or add the smart navigation to `navigation.js` which already runs on all pages.

Best approach: Move the smart anchor navigation code into `navigation.js` (which is enqueued on all pages) and keep other global.js content (theme toggle, blog cards) front-page-only.

So in `navigation.js`, add the smart anchor handler. Remove the old handler from `global.js`.

**Verification:**
1. Visit `https://imadconsult.zubbystudio.shop/`
2. Click each nav link
3. Expected: smooth scrolls to each section, URL updates to `/#about`, `/#skills`, etc.
4. Click "Blog" → navigates to `/blog/`
5. From `/blog/`, click "Home"
6. Expected: navigates to front page (URL = `/`)
7. From `/blog/`, click "About"
8. Expected: navigates to front page and scrolls to `#about` (URL = `/#about`)

---

### Task 4: Update navigation.js — active link scroll tracking for new href format

**Objective:** The active link highlighting in `navigation.js` uses `link.getAttribute('href')` which now returns `/#about` instead of `#about`. Update the matcher.

**Files:**
- Modify: `assets/js/animations/navigation.js` (setActive function)

**Step 1: Fix the active link matcher**

Change line 67:
```javascript
// From:
const href = link.getAttribute('href').replace('/', '');
// To:
const href = link.getAttribute('href');
```

And update the toggle logic:
```javascript
function setActive(id) {
    navLinks.forEach(link => {
        const href = link.getAttribute('href');
        // Match /#section or just / (home)
        const isMatch = (id === 'home' && href === '/') || 
                        href === '/#' + id || 
                        href === '#' + id;
        link.classList.toggle('active', isMatch);
    });
}
```

This handles both formats during transition.

**Verification:**
1. Load front page
2. Scroll through sections
3. Expected: corresponding nav link gets `.active` class
4. Browser URL updates to `/#skills`, `/#projects`, etc. as sections come into view

---

### Task 5: Validate and test

**Objective:** Verify both bugs are fixed, no regressions.

**Steps:**
1. Open `https://imadconsult.zubbystudio.shop/` in browser
2. Scroll through all sections — hero, about, skills, projects, experience, blog, contact
3. Verify projects→experience transition is smooth (no freeze/jump)
4. Click each nav link — verify smooth scroll works
5. Click "Blog" → navigate to `/blog/`
6. Click "Home" → verify front page loads
7. Click "About" from blog page → verify navigates to front page
8. Check mobile menu links work
9. Run `php -l` on changed PHP files to verify syntax

**Verification commands:**
```bash
# Syntax check PHP changes
docker exec malachy-wp bash -c 'for f in /var/www/html/wp-content/themes/malachy-portfolio/template-parts/navigation.php /var/www/html/wp-content/themes/malachy-portfolio/functions.php; do php -l "$f"; done'

# Check JS files for obvious errors (basic syntax)
node -e "
  const fs = require('fs');
  const files = [
    '/home/zubbyik/wordpress_project/malachy-portfolio/assets/js/animations/projects.js',
    '/home/zubbyik/wordpress_project/malachy-portfolio/assets/js/animations/experience.js',
    '/home/zubbyik/wordpress_project/malachy-portfolio/assets/js/animations/global.js',
    '/home/zubbyik/wordpress_project/malachy-portfolio/assets/js/animations/navigation.js'
  ];
  files.forEach(f => {
    try {
      new Function(fs.readFileSync(f, 'utf-8'));
      console.log('OK: ' + f);
    } catch(e) {
      console.log('ERROR: ' + f + ' — ' + e.message);
    }
  });
"
```

---

## Files Changed Summary

| File | Change | Risk |
|---|---|---|
| `assets/js/animations/projects.js` | Increase pin end, add onLeave | Low |
| `assets/js/animations/experience.js` | Add refreshPriority | Low |
| `template-parts/navigation.php` | Change href format, add section type | Medium — nav links are core UX |
| `assets/js/animations/global.js` | Rewrite anchor handler, add pushState | Medium — smooth scroll is core UX |
| `assets/js/animations/navigation.js` | Fix active link matcher | Low |

**Total: 5 files changed, ~40 lines added/removed**

---

## Risks & Open Questions

1. **History API**: `history.pushState` changes the URL. If user presses browser back, they go to the previous section, not the previous page. This is acceptable for a single-page portfolio.
2. **SEO**: Hash URLs (`/#about`) are Google-friendly — Google treats them as separate page states but doesn't penalize them.
3. **Edge case: rapid clicking**: If user clicks nav links rapidly, multiple ScrollTrigger refreshes may compete. The fix is idempotent — `ScrollTrigger.refresh()` is cheap and re-entrant.
4. **No questions for the user** — the bugs are well-understood and the fixes are straightforward.

---

## Execution Handoff

Plan complete. Ready to execute using subagent-driven-development — I'll dispatch tasks sequentially (each file change is small, no parallelism needed) with in-browser verification after Task 5.
