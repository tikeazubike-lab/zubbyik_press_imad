/**
 * Testimonials Section Animation (HO-019)
 *
 * Pinned parallax + typewriter quote build.
 *
 * Architecture rule (HO-012, non-negotiable): ONE master GSAP timeline,
 * ONE ScrollTrigger with `pin: true` + `scrub`. No tweens are created
 * inside `onUpdate` or any other scroll callback.
 *
 * Quotes are read once at init from the server-rendered `data-quotes`
 * attribute on .testimonials-pin — never hardcoded here.
 *
 * Reduced-motion / no-JS / mobile: the static stacked list rendered by
 * section-testimonials.php stays visible (JS never activates the pinned
 * stage), so the section degrades gracefully.
 *
 * @package Malachy_Portfolio
 */

document.addEventListener('DOMContentLoaded', function () {
  const section = document.querySelector('.testimonials-pin');
  if (!section) return;

  // Respect prefers-reduced-motion — same gate used everywhere else in
  // this theme. Reduced-motion users get the static stacked list.
  const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  if (reduceMotion) return;

  let quotes = [];
  try {
    quotes = JSON.parse(section.dataset.quotes || '[]');
  } catch (e) {
    quotes = [];
  }
  if (!quotes.length) return;

  const bg = section.querySelector('.testimonials-bg');
  const textEl = section.querySelector('.testimonial-text');
  const attrEl = section.querySelector('.testimonial-attribution');

  // Shared-hosting safety (production): if this file ever loads without
  // GSAP / ScrollTrigger / TextPlugin — or a TextPlugin tween throws —
  // bail BEFORE activating the pinned stage so the static fallback list
  // stays visible instead of the section rendering as an empty surface.
  if (typeof gsap === 'undefined' || typeof ScrollTrigger === 'undefined') return;
  if (typeof TextPlugin === 'undefined') return;

  // Deactivate the animated presentation on any init failure.
  try {
    // Activate the pinned/typewriter presentation and hide the static list.
    section.classList.add('testimonials-is-active');

    // Give each quote a generous, even slice of the pin's scroll distance.
    const perQuoteScroll = 100; // config units per quote ("vh-equivalents")
    const totalScroll = quotes.length * perQuoteScroll;
    const fadeSpan = totalScroll * 0.05; // cross-fade bg over first/last 5%

    // ONE timeline. Built once. Never touched again after this.
    const tl = gsap.timeline({ paused: true });

    // Background cross-fade in/out at the pin's edges (spec §3).
    tl.fromTo(bg, { opacity: 0 }, {
      opacity: 1,
      duration: fadeSpan,
      ease: 'none',
    }, 0);

    // Background "fall" — power2.in gives the acceleration feel. Spans the
    // WHOLE pin, not per-quote. Only yPercent is animated (GPU compositor).
    tl.to(bg, {
      yPercent: -25, // element is 160% of section height; -25%×160% = -40% of section
      ease: 'power2.in',
      duration: totalScroll,
    }, 0);

    tl.to(bg, {
      opacity: 0,
      duration: fadeSpan,
      ease: 'none',
    }, totalScroll - fadeSpan);

    // Type/delete each quote in sequence at even label positions along the
    // same timeline.
    quotes.forEach(function (q, i) {
      const start = i * perQuoteScroll;

      // Attribution line under the quote: "Author — Project".
      const attribution = q.author + (q.project ? ' — ' + q.project : '');

      tl.to(textEl, {
        text: q.quote,
        duration: perQuoteScroll * 0.6,
        ease: 'none',
        onStart: function () {
          textEl.textContent = '';
          attrEl.textContent = '';
        },
      }, start);

      tl.to(attrEl, {
        text: attribution,
        duration: perQuoteScroll * 0.15,
        ease: 'none',
      }, start + perQuoteScroll * 0.6);

      // Delete back down to nothing before the next quote starts typing —
      // skip on the very last quote so it holds instead of vanishing.
      tl.to(textEl, {
        text: i < quotes.length - 1 ? '' : q.quote,
        duration: perQuoteScroll * 0.2,
        ease: 'none',
      }, start + perQuoteScroll * 0.75);
    });

    // Opens & completes hidden; ScrollTrigger scrub drives both values.
    gsap.set(bg, { opacity: 0 });

    ScrollTrigger.create({
      trigger: section,
      start: 'top top',
      end: '+=' + totalScroll + '%',
      pin: true,
      scrub: 1, // slight lag smooths out fast/jerky scroll input
      animation: tl,
      // markers: true, // enable while tuning, remove before shipping
    });
  } catch (err) {
    // Anything thrown above falls back to the static stacked list.
    section.classList.remove('testimonials-is-active');
  }
});
