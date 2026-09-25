# Testimonials Section — Pinned Parallax + Typewriter Build Spec

**Placement:** new section between Experience and Blog Preview (per
earlier recommendation — right before the final Contact push).
**Architecture rule (non-negotiable, per HO-012):** ONE master GSAP
timeline, ONE ScrollTrigger with `pin: true` + `scrub: true`. No tweens
created inside `onUpdate` or other scroll callbacks — that exact bug
already cost a full debugging cycle on this project once.

---

## 1. Content source — avoid a 4th contradicting data source

The project has already suffered from 3 different, contradicting
experience narratives living in 3 different files. **Do not hardcode
quotes in the JS.** Render them server-side from the `testimonial` CPT
into data attributes (or a small inline JSON blob) that the JS reads
once at init:

```php
<?php
// In section-testimonials.php — pull the CPT posts, hand them to JS as data.
$testimonials = get_posts([
    'post_type'      => 'testimonial',
    'posts_per_page' => 4, // curated subset — see note in Section 4
    'orderby'        => 'menu_order',
    'order'          => 'ASC',
]);
$quote_payload = array_map(function ($post) {
    return [
        'quote'   => get_the_title($post), // or your quote meta field
        'author'  => get_post_meta($post->ID, 'client_name', true),
        'project' => get_post_meta($post->ID, 'project_label', true),
    ];
}, $testimonials);
?>
<section id="testimonials" class="testimonials-pin"
         data-quotes='<?php echo esc_attr(wp_json_encode($quote_payload)); ?>'>
  <div class="testimonials-bg" aria-hidden="true"></div>
  <div class="testimonials-content">
    <p class="testimonials-eyebrow">09 / What clients say</p>
    <h3 class="testimonial-text" aria-live="polite"></h3>
    <span class="typewriter-cursor" aria-hidden="true">|</span>
    <p class="testimonial-attribution"></p>
  </div>
</section>
```

## 2. JS — single master timeline, single ScrollTrigger

```js
// assets/js/animations/testimonials.js
// Follows the same AnimationManager registration pattern as the other
// section animation files — desktop-only, reduced-motion aware.

gsap.registerPlugin(ScrollTrigger, TextPlugin);

export function initTestimonials() {
  const section = document.querySelector('.testimonials-pin');
  if (!section) return;

  // Respect prefers-reduced-motion — same gate used everywhere else
  // in this theme. Reduced-motion users get a static stacked list,
  // rendered separately in CSS/PHP fallback, no GSAP at all.
  const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  if (reduceMotion) return;

  const quotes = JSON.parse(section.dataset.quotes || '[]');
  if (!quotes.length) return;

  const bg = section.querySelector('.testimonials-bg');
  const textEl = section.querySelector('.testimonial-text');
  const attrEl = section.querySelector('.testimonial-attribution');

  // Give each quote a generous, even slice of the pin's scroll
  // distance — cramming too many quotes into one pin makes the type/
  // delete cycle feel rushed relative to scroll speed. Recommend 4-5
  // curated quotes max (see Section 4), not all 9.
  const perQuoteScroll = 100; // vh per quote — tune after a real scroll test
  const totalScroll = quotes.length * perQuoteScroll;

  // ONE timeline. Built once. Never touched again after this.
  const tl = gsap.timeline({ paused: true });

  // Background "fall" — power2.in gives the acceleration feel.
  // This is what makes it feel like it's speeding up, independent of
  // how fast the user physically scrolls — the ease curve does the
  // work, not scroll-speed detection.
  tl.to(bg, {
    yPercent: -40,           // adjust based on final background asset size
    ease: 'power2.in',
    duration: totalScroll,   // spans the WHOLE pin, not per-quote
  }, 0);

  // Type/delete each quote in sequence, at even label positions
  // along the same timeline.
  quotes.forEach((q, i) => {
    const start = i * perQuoteScroll;

    tl.to(textEl, {
      text: q.quote,
      duration: perQuoteScroll * 0.6,
      ease: 'none',
      onStart: () => { attrEl.textContent = ''; }, // clear attribution while typing
    }, start)
    .to(attrEl, {
      text: `${q.author} — ${q.project}`,
      duration: perQuoteScroll * 0.15,
      ease: 'none',
    }, start + perQuoteScroll * 0.6)
    // Delete back down to nothing before the next quote starts typing —
    // skip this on the very last quote so it holds instead of vanishing.
    .to(textEl, {
      text: i < quotes.length - 1 ? '' : q.quote,
      duration: perQuoteScroll * 0.2,
      ease: 'none',
    }, start + perQuoteScroll * 0.75);
  });

  ScrollTrigger.create({
    trigger: section,
    start: 'top top',
    end: `+=${totalScroll}%`,
    pin: true,
    scrub: 1, // slight lag smooths out fast/jerky scroll input — reduces jolt
    animation: tl,
    // markers: true, // enable while tuning, remove before shipping
  });
}
```

## 3. Background — recommendation for continuity, no jolt

**Avoid:** a photographic image, anything with faces, readable text, or
hard geometric edges — fast vertical translation exaggerates any
distortion or seams in those, and it'll read as jittery rather than
smooth no matter how well the easing is tuned.

**Recommend:** an abstract vertical gradient/grain texture built from
the theme's existing OKLCH design tokens — same color system already
used sitewide, so it reads as continuous with the rest of the page
rather than a jarring new visual language. Concretely:

- A tall, soft-edged gradient mesh (or subtle noise/grain texture) at
  roughly **180–200% of the section's viewport height**, so the `yPercent:
  -40` translation never exposes a hard top/bottom edge.
- No sharp contrast bands — smooth OKLCH lightness transitions only,
  so movement reads as depth/atmosphere rather than a "sliding image."
- Cross-fade the background's opacity in over the first ~5% and out
  over the last ~5% of the pin's scroll range, so entering/leaving the
  pinned section is a fade, not a hard cut — this is usually where
  "jolt" actually comes from, more than the parallax speed itself.
- `will-change: transform` on `.testimonials-bg`, and only ever animate
  `y`/`yPercent` (never `top` or `margin`) — keeps it on the GPU
  compositor thread, avoiding layout-triggered jank.

## 4. Recommendation: curate 4-5 quotes, not all 9

Cycling all 9 testimonials through one pinned scroll would make the
section very long and dilute the strongest ones. Suggested curated set,
ordered for narrative flow (general reliability → specific offer match →
close):

1. Tig Michael (QA/testing — matches your own background)
2. Lee J. (troubleshooting persistence)
3. **Mkenny Properties (strongest — direct offer match, feature near the end)**
4. Mimi R. or Stanley H. (general delivery/professionalism, pick one)

The remaining 5 can still exist as CPT posts and be pulled into a
simpler static list elsewhere (e.g. a "more feedback" link), rather than
being dropped entirely.

## 5. Reduced-motion / no-JS fallback (required, not optional)

Per the same standard already applied to every other section in this
theme: if `prefers-reduced-motion` is set, or on mobile (where GSAP
isn't enqueued at all per the existing `wp_is_mobile()` gate), render
the same 4-5 quotes as a static stacked list — no pin, no parallax, no
typewriter. The `aria-live="polite"` on `.testimonial-text` should stay
in the markup either way, but only matters once text is actually being
swapped programmatically.
