# Animation Architecture — GSAP 3.12.5

The Malachy Portfolio theme uses **GSAP 3.12.5** with the **ScrollTrigger** plugin for all scroll-driven animations. The architecture is modular, responsive, and respects user accessibility preferences.

---

## Architecture Overview

```
GSAP Core (gsap.min.js)         ← Bundled vendor, deferred
    └── ScrollTrigger plugin    ← Bundled vendor, deferred
            └── AnimationManager.js     ← Orchestrator (global instance)
                    ├── hero.js         ← Section animation module
                    ├── about.js        ← Section animation module
                    ├── skills.js       ← Section animation module
                    ├── projects.js     ← Section animation module
                    ├── experience.js   ← Section animation module
                    ├── contact.js      ← Section animation module
                    ├── global.js       ← Cross-section effects
                    └── navigation.js   ← Always loaded (fallback-safe)
```

## Key Design Decisions

### Bundled, No CDN
GSAP and ScrollTrigger are served from `assets/js/vendor/` as local copies. This eliminates external dependencies, ensures offline functionality, and avoids CORS/blocking issues on restrictive networks.

### Module-Based Architecture
Each front-page section has its own animation module (`hero.js`, `about.js`, `skills.js`, etc.) that is enqueued only when `is_front_page()` is true. This keeps individual modules small and prevents unnecessary JS on archive/single pages.

### Mobile Awareness
GSAP is **entirely skipped on mobile** — the enqueue logic in `functions.php` gates all GSAP scripts behind `! wp_is_mobile()`. Mobile users get the fallback IntersectionObserver-based navigation and native CSS transitions. This improves performance on low-powered devices and avoids touch-scroll conflicts with ScrollTrigger.

### Reduced Motion Support
All animations respect `prefers-reduced-motion: reduce`. The `AnimationManager` constructor checks this on initialisation and skips all timeline creation when the user's OS setting requests reduced motion.

### Graceful Degradation
Navigation has a built-in fallback: when GSAP isn't loaded (mobile or JS error), `navigation.js` detects the absence of `gsap`/`ScrollTrigger` and switches to `IntersectionObserver` for scroll-based header styling and active link highlighting.

---

## AnimationManager Orchestrator

**File:** `assets/js/animations/AnimationManager.js`

The `AnimationManager` class is instantiated once as `window.MalachyAnim`. It provides:

### Section Registration

```javascript
// Each module registers itself:
MalachyAnim.register('hero', (manager) => {
  // Return a timeline, array of tweens, or null
  // Access manager.breakpoints for matchMedia
});
```

### Lifecycle

1. **Constructor:** Checks `prefers-reduced-motion`, attaches `load` and `document.fonts.ready` events for ScrollTrigger refresh, registers `beforeunload` cleanup.
2. **register(name, createTimeline, options):** Creates the timeline for a named section. Returns early if reduced motion is active. Catches errors gracefully.
3. **destroy():** Called on page unload — kills all timelines, clears the sections map, kills matchMedia instances, kills all ScrollTrigger instances.

### Features

| Feature | Implementation |
|---|---|
| Reduced motion gate | Constructor checks `window.matchMedia('(prefers-reduced-motion: reduce)')` |
| Responsive breakpoints | `gsap.matchMedia()` via `manager.breakpoints` getter |
| Font/image refresh | `ScrollTrigger.refresh()` on `window.load` and `document.fonts.ready` |
| Memory cleanup | `beforeunload` → `destroy()` kills all timelines and ScrollTriggers |

---

## Per-Section Animation Details

### Hero Section (`hero.js`)

| Animation | Technique | Trigger | Breakpoint |
|---|---|---|---|
| Pin hero | `ScrollTrigger.create({ pin: true })` | `top top → +=120%` | ≥768px |
| Portrait scale/opacity | `gsap.to` with `scrub: 1.5` | `top top → bottom 80%` | ≥768px |
| Text upward translate | `gsap.to` with `scrub: 1` | `top top → bottom 60%` | ≥768px |
| Marquee scroll | `gsap.to({ xPercent: -50 })` with `scrub: 1` | `top top → bottom top` | ≥768px |
| Simple fade reveal | `gsap.from` with `scrub: 1` | `top 80% → top 40%` | ≤767px |
| CTA button hover | `gsap.to` on `mouseenter`/`mouseleave` | Direct event | All |

**Key detail:** The hero pin uses `anticipatePin: 1` and `pinSpacing: true` to avoid layout jumps. Below 768px, pin is completely disabled — the hero degrades to a simple opacity/translate scroll reveal.

### About Section (`about.js`)

| Animation | Trigger | Easing |
|---|---|---|
| `.about-reveal` items stagger upward | `top 80% → top 40%`, `scrub: 1`, stagger `0.2` | `power2.out` |
| Stats stagger | `top 85% → top 50%`, `scrub: 1`, stagger `0.15` | `power2.out` |
| Illustration parallax (`y: -80`) | `top bottom → bottom top`, `scrub: 1.5` | `power1.out` |

### Skills Section (`skills.js`)

| Animation | Trigger | Properties |
|---|---|---|
| `.skill-card` stagger reveal | `top 80% → bottom 40%`, `scrub: 1`, stagger `0.12` | `y: 50`, `opacity: 0` → 1 |

### Projects Section (`projects.js`)

| Animation | Trigger | Properties |
|---|---|---|
| Card image slides left (`x: -80`) | Per-card `top 80% → top 40%`, `scrub: 1` | `opacity: 0` |
| Card body slides right (`x: 80`) | Same trigger per-card | `opacity: 0` |

**Key detail:** Unlike a traditional GSAP pin-based card stack, this section uses **CSS `position: sticky`** for the stacking effect. GSAP only adds scroll-triggered entry animations (fade/translate) to each card's image and body as the card scrolls into view. This avoids the freeze/jump that occurs when pinned ScrollTrigger releases.

### Experience Section (`experience.js`)

| Animation | Trigger | Properties |
|---|---|---|
| Timeline fill grows (`scaleY: 1`) | `top 80% → bottom 20%`, `scrub: 1.5`, `transformOrigin: top center` | `refreshPriority: -10` |
| Milestone items fade-up | Per-item `top 85% → top 50%`, `scrub: 1` | `y: 50`, `opacity: 0` |

### Contact Section (`contact.js`)

| Animation | Type | Details |
|---|---|---|
| Heading fade-up | Scroll-triggered (`scrub: 1`) | `y: 60`, `opacity: 0` |
| Form fields stagger | Scroll-triggered (`scrub: 1`, stagger `0.1`) | `y: 40`, `opacity: 0` |
| Social links fade-up | Scroll-triggered (`scrub: 1`) | `y: 30`, `opacity: 0` |
| Orb A float (continuous) | `gsap.to` with `repeat: -1`, `yoyo: true`, `duration: 6` | `y: 40`, `x: 20`, `ease: sine.inOut` |
| Orb B float (continuous) | `gsap.to` with `repeat: -1`, `yoyo: true`, `duration: 8` | `y: -30`, `x: -15`, `ease: sine.inOut` |

### Navigation (`navigation.js`)

| Feature | GSAP Available | Fallback (Mobile/No GSAP) |
|---|---|---|
| Sticky header background | `ScrollTrigger` on `document.body`, `start: top -80px` | `IntersectionObserver` on 1px sentinel |
| Active link highlighting | `ScrollTrigger.create` per section (`top/bottom center`) | `IntersectionObserver` with `rootMargin: '-40% 0px'` |
| Mobile menu toggle | CSS `display` toggle | Same |
| Smart anchor scroll | `window.scrollTo({ behavior: 'smooth' })` (native) | Same |
| Hash-on-load scroll | `setTimeout` + native scroll | Same |

### Global (`global.js`)

| Feature | Implementation |
|---|---|
| Theme toggle | `localStorage`-persisted dark/light mode |
| Blog card reveal | `gsap.from` with stagger, `scrub: 1` (front page only) |

---

## Enqueue Strategy

In `functions.php`, GSAP scripts are enqueued with these dependencies:

```
gsap (defer)
  └── gsap-scroll-trigger (defer)
        └── malachy-anim-manager (footer)
              ├── malachy-anim-hero (footer)
              ├── malachy-anim-about (footer)
              ├── malachy-anim-skills (footer)
              ├── malachy-anim-projects (footer)
              ├── malachy-anim-experience (footer)
              ├── malachy-anim-contact (footer)
              ├── malachy-anim-global (footer)
              └── malachy-anim-navigation (footer, always loaded)
```

- **Deferred vendor scripts:** GSAP and ScrollTrigger are deferred (not render-blocking).
- **Footer-loaded modules:** All animation modules load in the footer (after page content) for non-blocking initial render.
- **Front-page only:** Section-specific modules only enqueue when `is_front_page()` is true.
- **Navigation always loads:** `navigation.js` is enqueued on all pages, with GSAP dependency only when available.

---

## Mobile Pinning Exception

Pinned ScrollTrigger animations (hero pin, potential future pinned sections) are gated behind `gsap.matchMedia()` at a **768px breakpoint**:

| Viewport | Pin Behaviour | Animation |
|---|---|---|
| ≥768px (desktop/tablet) | Pinned hero, full scrub animations | Portrait scale, text translate, marquee |
| ≤767px (mobile) | No pin | Simple fade reveal via `gsap.from` |

This is enforced inside each module's `mm.add('(min-width: 768px)', ...)` / `mm.add('(max-width: 767px)', ...)` blocks. Additionally, the entire GSAP bundle is conditionally enqueued via `! wp_is_mobile()` in PHP — mobile users never receive GSAP bytes.

---

## Adding a New Animation Module

1. Create `assets/js/animations/your-section.js`:

```javascript
document.addEventListener('DOMContentLoaded', function () {
  const section = document.getElementById('your-section');
  if (!section) return;

  gsap.from(section.querySelectorAll('.your-element'), {
    scrollTrigger: {
      trigger: section,
      start: 'top 80%',
      end: 'top 40%',
      scrub: 1,
    },
    y: 50,
    opacity: 0,
    stagger: 0.15,
    ease: 'power2.out',
  });
});
```

2. Register in `functions.php` inside the `is_front_page()` block:

```php
$animations = array( 'hero', 'about', 'skills', 'projects', 'experience', 'contact', 'global', 'your-section' );
```

3. If the module uses the AnimationManager orchestrator, wrap it:

```javascript
window.MalachyAnim?.register('your-section', (manager) => {
  // Use manager.breakpoints for responsive behaviour
  // Return timeline(s) or tweens
});
```

---

## Debugging

| Issue | Check |
|---|---|
| Animations don't start | ScrollTrigger.refresh() not called after DOM changes |
| Pin causes layout jump | `pinSpacing: true` and `anticipatePin: 1` need to be set |
| Stagger too fast/slow | Adjust `stagger` value (seconds between each element) |
| Scrub feels janky | Increase `scrub` value (e.g., `scrub: 1.5` for smoother interpolation) |
| Reduced motion not respected | Check `window.matchMedia('(prefers-reduced-motion: reduce)')` in AnimationManager constructor |
| Mobile/tablet pin issue | Verify `matchMedia` breakpoint matches CSS breakpoint |
