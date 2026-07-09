/**
 * Projects Section Animation
 *
 * Stacked card reveal via GSAP ScrollTrigger.
 * The #projects-stack container is pinned; cards are layered absolutely
 * so they overlap. Each subsequent card scrubs up from below while the
 * previous card scales down and dims, creating a true deck/stack effect.
 *
 * Falls back to simple fade-in on mobile (no pinning).
 *
 * @package Malachy_Portfolio
 */
document.addEventListener('DOMContentLoaded', function () {
  const section = document.getElementById('projects');
  const stack   = document.getElementById('projects-stack');
  if (!section || !stack) return;

  const isDesktop = window.matchMedia('(min-width: 768px)').matches;
  if (!isDesktop) return;

  const cards = stack.querySelectorAll('.proj-card');
  const cardArray = Array.from(cards);
  if (cardArray.length === 0) return;

  /* ---- Reset inline styles when dropping below desktop ---- */
  function resetCardStyles () {
    stack.style.height = '';
    stack.style.overflow = '';
    cardArray.forEach(function (card) {
      card.style.position = '';
      card.style.top = '';
      card.style.left = '';
      card.style.width = '';
      card.style.height = '';
      card.style.zIndex = '';
      card.style.marginBottom = '';
    });
  }

  /* ---- Cleanup any previous ScrollTriggers / tweens ---- */
  ScrollTrigger.getAll().forEach(function (t) {
    if (t.vars && t.vars.trigger &&
        (t.vars.trigger === stack || stack.contains(t.vars.trigger))) {
      t.kill();
    }
  });
  gsap.killTweensOf(cardArray);

  /* ---- Measure natural card heights, then switch to absolute stack ---- */
  var maxCardHeight = 0;
  cardArray.forEach(function (c) {
    maxCardHeight = Math.max(maxCardHeight, c.offsetHeight);
  });

  var stackHeight = Math.max(maxCardHeight, window.innerHeight);
  stack.style.position = 'relative';
  stack.style.height   = stackHeight + 'px';
  stack.style.overflow = 'hidden';

  cardArray.forEach(function (card, i) {
    card.style.position   = 'absolute';
    card.style.top        = '0';
    card.style.left       = '0';
    card.style.width      = '100%';
    card.style.height     = '100%';
    card.style.zIndex     = (i + 1).toString(); // higher index = on top
    card.style.marginBottom = '0';
  });

  /* ---- Scrubbed stacking timeline ---- */
  var tl = gsap.timeline({
    scrollTrigger: {
      trigger: stack,
      start: 'top 4rem',
      end: '+=' + (window.innerHeight * (cardArray.length - 1)),
      pin: true,
      scrub: 1,
      anticipatePin: 1,
      invalidateOnRefresh: true,
    }
  });

  cardArray.forEach(function (card, i) {
    var img  = card.querySelector('.proj-card-image');
    var body = card.querySelector('.proj-card-body');

    if (i === 0) {
      /* First card is visible immediately — just reveal its content */
      if (img) {
        tl.fromTo(img,
          { x: -60, opacity: 0 },
          { x: 0, opacity: 1, duration: 0.5, ease: 'power2.out' },
          0
        );
      }
      if (body) {
        tl.fromTo(body,
          { x: 60, opacity: 0 },
          { x: 0, opacity: 1, duration: 0.5, ease: 'power2.out' },
          0.1
        );
      }
      return;
    }

    var prev = cardArray[i - 1];

    /* 1. Previous card recedes (scales down + dims) */
    tl.fromTo(prev,
      { scale: 1, opacity: 1 },
      { scale: 0.92, opacity: 0.7, duration: 1, ease: 'none' },
      i - 1
    );

    /* 2. Current card slides up from below to cover the previous */
    tl.fromTo(card,
      { yPercent: 100 },
      { yPercent: 0, duration: 1, ease: 'none' },
      i - 1
    );

    /* 3. Current card content sweeps in */
    if (img) {
      tl.fromTo(img,
        { x: -60, opacity: 0 },
        { x: 0, opacity: 1, duration: 0.5, ease: 'power2.out' },
        (i - 1) + 0.2
      );
    }
    if (body) {
      tl.fromTo(body,
        { x: 60, opacity: 0 },
        { x: 0, opacity: 1, duration: 0.5, ease: 'power2.out' },
        (i - 1) + 0.3
      );
    }
  });

  ScrollTrigger.refresh();
  window.addEventListener('resize', function () {
    ScrollTrigger.refresh();
  });
});
