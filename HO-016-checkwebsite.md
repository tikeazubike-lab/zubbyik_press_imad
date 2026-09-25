# Playwright Visual Regression Baseline Capture

## Role

Act as a senior Playwright test engineer and visual-regression engineer.

The existing portfolio website is already running locally/on the server. Your task is to create an automated Playwright screenshot system that captures the **entire existing website**, including all accessible pages and important UI states.

These screenshots will become the **visual reference/baseline** for comparing the current implementation against the intended reference design.

Do not modify the website during this task unless a temporary test-only change is absolutely necessary. The objective is to observe and capture the current implementation exactly as it exists.

---

# 1. First inspect the project

Before writing the Playwright tests:

* Inspect the repository structure.
* Identify the frontend/framework being used.
* Identify the website's configured base URL.
* Identify all existing routes.
* Inspect navigation links.
* Inspect footer links.
* Inspect CTA links.
* Inspect project/article links.
* Inspect any dynamically generated routes.
* Identify interactive components.
* Identify sections that use GSAP/ScrollTrigger.
* Identify responsive breakpoints.
* Identify whether the site contains lazy-loaded images.
* Identify whether animations are triggered by scrolling.

Do not assume that `/`, `/about`, `/projects`, etc. are the only routes.

Build the route inventory from the actual application.

---

# 2. Route discovery

Create a deterministic list of every internal page that should be captured.

Discover routes from:

1. Application/router configuration.
2. Navigation menus.
3. Footer navigation.
4. Internal `<a href>` links.
5. Buttons containing internal navigation.
6. Project links.
7. Blog/article links.
8. Sitemap if available.
9. Any other application-specific route configuration.

Only capture URLs belonging to the same website/domain.

Do NOT follow:

* external websites
* social media
* mailto links
* telephone links
* downloadable files
* external APIs

Remove duplicate URLs.

Normalize URLs so that equivalent URLs are not captured twice.

Save the final route inventory locally.

Example:

```text
visual-baseline/
├── routes.json
├── screenshots/
│   ├── desktop/
│   ├── tablet/
│   └── mobile/
└── metadata/
```

---

# 3. Viewports

Capture every page at these exact viewport sizes:

### Desktop

```text
1440 × 900
```

### Tablet

```text
1024 × 768
```

### Mobile

```text
390 × 844
```

Use the exact viewport dimensions rather than relying on the browser window size.

The purpose is to detect:

* responsive layout problems
* overflow
* typography changes
* broken navigation
* image scaling problems
* animation problems
* spacing differences
* mobile-specific rendering issues

---

# 4. Browser

Use Chromium through Playwright.

Configure:

```text
deviceScaleFactor: 1
```

Do not use random browser settings between screenshots.

Keep the capture environment deterministic.

---

# 5. Screenshot requirements

For every route and every viewport:

1. Open the page.
2. Wait for the DOM to load.
3. Wait for fonts.
4. Wait for images.
5. Wait for network activity to settle where practical.
6. Allow GSAP initialization to complete.
7. Allow ScrollTrigger to initialize.
8. Capture the page at its natural initial scroll position.
9. Capture the complete page using a full-page screenshot.
10. Save the screenshot locally.

Screenshots must NOT be clipped to the visible viewport unless explicitly requested.

Use full-page screenshots.

---

# 6. GSAP / animation handling

The website uses GSAP and ScrollTrigger.

Do NOT disable animations globally.

The purpose of this test is specifically to verify that the animation implementation behaves correctly.

However, animations must be captured deterministically.

Before capturing the initial page screenshot:

* Wait for GSAP initialization.
* Wait for fonts.
* Wait for images.
* Wait for initial entrance animations to complete.
* Call `ScrollTrigger.refresh()` if appropriate.
* Allow the browser to render at least several animation frames.

Do not arbitrarily use extremely long sleeps.

Prefer deterministic conditions such as:

```javascript
await page.evaluate(() => document.fonts.ready);
```

and explicit application-ready conditions where available.

---

# 7. Scroll-state screenshots

Because the website relies heavily on scroll-driven GSAP animations, capturing only the initial page state is insufficient.

For each page, create additional screenshots at meaningful scroll positions.

At minimum capture:

```text
0%
25%
50%
75%
100%
```

of the document scroll range.

At each position:

1. Scroll to the calculated position.
2. Wait for ScrollTrigger to update.
3. Wait for the browser to render.
4. Capture the viewport screenshot.
5. Save it locally.

Do NOT use full-page screenshots for these scroll-state captures.

These screenshots are intended to verify animation states.

Example:

```text
screenshots/
└── desktop/
    └── home/
        ├── initial-full.png
        ├── scroll-00.png
        ├── scroll-25.png
        ├── scroll-50.png
        ├── scroll-75.png
        └── scroll-100.png
```

---

# 8. Important animation states

Identify sections where the design clearly depends on scroll position.

Examples:

* pinned hero
* parallax portrait
* text reveal
* project stacking
* sticky project cards
* timeline animation
* animated navigation
* background transitions
* GSAP scrub animations

For these sections, capture additional screenshots at useful intermediate scroll positions if required.

Do not blindly generate hundreds of screenshots.

Use enough states to expose animation regressions.

---

# 9. Interaction states

Identify important interactive components.

Where practical, capture screenshots for:

### Navigation

* initial state
* scrolled state
* mobile menu closed
* mobile menu open

### Buttons

* normal state
* hover state

### Project cards

* normal state
* hover state

### Theme

* dark mode
* light mode, if supported

### Forms

* empty state
* focused state where visually relevant

### Other interactive UI

Capture important visual states discovered during inspection.

Do not invent states that do not exist.

---

# 10. Lazy-loaded content

The website may contain lazy-loaded images or content.

Before capturing a page:

* Ensure images currently required for the screenshot have loaded.
* Detect broken images.
* Scroll through the page when necessary to trigger lazy loading.
* Wait for newly loaded assets before taking final screenshots.

Do not permanently change the application's lazy-loading behavior.

---

# 11. Detect visual/runtime problems

During the capture process, collect diagnostics.

Report:

* JavaScript errors
* uncaught exceptions
* failed network requests
* HTTP 4xx responses
* HTTP 5xx responses
* missing images
* missing fonts
* failed CSS
* failed JavaScript
* console errors
* console warnings where relevant
* broken internal links
* horizontal overflow
* unexpected page crashes

Capture these in:

```text
visual-baseline/
└── metadata/
    ├── console.json
    ├── network-errors.json
    ├── broken-links.json
    └── pages.json
```

---

# 12. Detect horizontal overflow

For every viewport, check whether:

```javascript
document.documentElement.scrollWidth >
document.documentElement.clientWidth
```

If horizontal overflow exists, record:

* URL
* viewport
* scrollWidth
* clientWidth

Do not automatically fix it.

This is a diagnostic baseline.

---

# 13. Screenshot naming

Use deterministic filenames.

Example:

```text
home__desktop__initial-full.png
home__desktop__scroll-25.png
home__desktop__scroll-50.png

about__desktop__initial-full.png

projects__mobile__initial-full.png
projects__mobile__scroll-50.png

contact__tablet__initial-full.png
```

Do not use timestamps in screenshot filenames.

This makes screenshot comparison deterministic.

---

# 14. Metadata

Create a metadata record for every captured page.

Example:

```json
{
  "url": "/projects",
  "viewport": {
    "width": 1440,
    "height": 900
  },
  "screenshot": "screenshots/desktop/projects__desktop__initial-full.png",
  "documentHeight": 4820,
  "horizontalOverflow": false,
  "consoleErrors": 0,
  "networkErrors": 0
}
```

Also record:

* capture timestamp
* route
* viewport
* user agent
* document height
* screenshot path
* scroll positions
* console errors
* network failures
* broken images
* overflow status

---

# 15. Reference design comparison

The screenshots generated by this task will later be compared against the original/reference design.

Therefore:

* Preserve exact screenshot dimensions.
* Keep viewport sizes deterministic.
* Do not resize screenshots after capture.
* Do not compress them aggressively.
* Do not crop screenshots.
* Do not add annotations directly to the reference screenshots.

Create a separate comparison/report directory if diagnostic overlays are needed.

Never modify the original baseline screenshots.

---

# 16. Playwright implementation

Create a dedicated visual regression test suite.

Recommended structure:

```text
tests/
└── visual/
    ├── discover-routes.ts
    ├── capture-pages.spec.ts
    ├── capture-interactions.spec.ts
    ├── helpers/
    │   ├── screenshots.ts
    │   ├── animations.ts
    │   ├── routes.ts
    │   └── diagnostics.ts
    └── config/
        └── visual.config.ts

visual-baseline/
├── routes.json
├── screenshots/
│   ├── desktop/
│   ├── tablet/
│   └── mobile/
├── metadata/
│   ├── pages.json
│   ├── console.json
│   ├── network-errors.json
│   ├── broken-links.json
│   └── overflow.json
└── reports/
```

Keep screenshot/capture logic modular.

Do not put everything into one massive test file.

---

# 17. Determinism

Make the capture process repeatable.

Avoid:

* random delays
* random data
* timestamps displayed by the application
* random animations
* unstable selectors
* random screenshot names

If the application contains dynamic content, identify it and document it.

The same application state should produce substantially identical screenshots when the capture command is run repeatedly.

---

# 18. Important: do not fix deviations

This is an observation/baseline task.

If you discover:

* broken spacing
* incorrect typography
* missing images
* animation glitches
* responsive problems
* layout deviations
* visual inconsistencies

DO NOT fix them during this phase.

Record them.

The purpose is to establish the current implementation as an objective baseline before comparing it against the reference design.

---

# 19. Final report

After the capture process completes, generate:

```text
visual-baseline/reports/visual-baseline-report.md
```

The report must include:

## Route summary

Total routes discovered.

Total routes successfully captured.

Failed routes.

## Viewport summary

Desktop:

```text
1440 × 900
```

Tablet:

```text
1024 × 768
```

Mobile:

```text
390 × 844
```

## Screenshot summary

Number of screenshots generated.

Breakdown by:

* route
* viewport
* scroll position
* interaction state

## Runtime problems

List:

* console errors
* network failures
* broken assets
* broken links
* overflow
* failed pages

## Animation observations

List any:

* GSAP errors
* ScrollTrigger errors
* pinned-section problems
* jitter
* animation not triggering
* animation triggering too early
* animation triggering too late
* layout shifts caused by animation

## Final status

Clearly state:

```text
PASS
```

or

```text
FAIL
```

based on whether the complete capture process succeeded.

---

# 20. Commands

Provide a simple command to run the complete baseline capture.

For example:

```bash
npm run visual:capture
```

Also provide:

```bash
npm run visual:routes
```

to regenerate the route inventory.

And:

```bash
npm run visual:report
```

if a separate report command is appropriate.

---

# 21. Acceptance criteria

The implementation is complete only when:

* [ ] All discoverable internal routes are captured.
* [ ] Desktop screenshots exist.
* [ ] Tablet screenshots exist.
* [ ] Mobile screenshots exist.
* [ ] Full-page screenshots exist.
* [ ] Scroll-state screenshots exist.
* [ ] Important interactive states are captured.
* [ ] GSAP animations are allowed to initialize.
* [ ] ScrollTrigger behavior is tested.
* [ ] Lazy-loaded content is captured correctly.
* [ ] Screenshot dimensions are deterministic.
* [ ] Screenshot filenames are deterministic.
* [ ] Route inventory is saved.
* [ ] Metadata is saved.
* [ ] Console errors are recorded.
* [ ] Network errors are recorded.
* [ ] Broken links are recorded.
* [ ] Horizontal overflow is detected.
* [ ] A final Markdown report is generated.
* [ ] The capture process can be repeated with one command.
* [ ] No visual fixes are made during this baseline-capture phase.

---

## Final instruction

Treat this as a professional visual-regression baseline system, not a simple screenshot script.

The resulting screenshot collection must allow another engineer to answer:

> "Does the current website visually match the intended reference design at every route, viewport, and important scroll/interaction state?"

Do not stop after capturing the homepage.

Do not assume routes.

Discover them.

Do not disable GSAP merely to make screenshots easier.

Capture the real animation states.

Do not fix visual problems.

Record them so they can be compared and corrected in a subsequent phase.

