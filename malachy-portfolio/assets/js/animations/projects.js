/**
 * Projects Section Animation
 *
 * Stacked sticky cards on lg+ only.
 * Below 1024px the cards render as a normal responsive grid.
 * Respects prefers-reduced-motion.
 *
 * @package Malachy_Portfolio
 */
document.addEventListener('DOMContentLoaded', function () {
  const section = document.getElementById('projects');
  const stack   = document.getElementById('projects-stack');
  if (!section || !stack) return;

  const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  if (prefersReducedMotion) return;

  const mm = window.matchMedia('(min-width: 1024px)');
  let activeTween = null;

  function resetCardStyles () {
    stack.style.position = '';
    stack.style.height = '';
    stack.style.overflow = '';
    stack.querySelectorAll('.proj-card').forEach(function (card) {
      card.style.position = '';
      card.style.top = '';
      card.style.left = '';
      card.style.width = '';
      card.style.height = '';
      card.style.zIndex = '';
      card.style.marginBottom = '';
    });
  }

  function buildStack () {
    // Clean up any previous ScrollTriggers / tweens tied to this stack
    ScrollTrigger.getAll().forEach(function (t) {
      if (t.vars && t.vars.trigger &&
          (t.vars.trigger === stack || stack.contains(t.vars.trigger))) {
        t.kill();
      }
    });
    if (activeTween) {
      activeTween.kill();
      activeTween = null;
    }
    gsap.killTweensOf(stack.querySelectorAll('.proj-card'));

    if (!mm.matches) {
      resetCardStyles();
      return;
    }

    const cards = stack.querySelectorAll('.proj-card');
    const cardArray = Array.from(cards);
    if (cardArray.length === 0) return;

    const maxCardHeight = Math.max.apply(null, cardArray.map(function (c) { return c.offsetHeight; }));
    const stackHeight = Math.max(maxCardHeight, window.innerHeight);

    stack.style.position = 'relative';
    stack.style.height   = stackHeight + 'px';
    stack.style.overflow = 'hidden';

    cardArray.forEach(function (card, i) {
      card.style.position   = 'absolute';
      card.style.top        = '0';
      card.style.left       = '0';
      card.style.width      = '100%';
      card.style.height     = '100%';
      card.style.zIndex     = (i + 1).toString();
      card.style.marginBottom = '0';
    });

    const tl = gsap.timeline({
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
    activeTween = tl;

    cardArray.forEach(function (card, i) {
      var img  = card.querySelector('.proj-card-image');
      var body = card.querySelector('.proj-card-body');

      if (i === 0) {
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

      tl.fromTo(prev,
        { scale: 1, opacity: 1 },
        { scale: 0.92, opacity: 0.7, duration: 1, ease: 'none' },
        i - 1
      );

      tl.fromTo(card,
        { yPercent: 100 },
        { yPercent: 0, duration: 1, ease: 'none' },
        i - 1
      );

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
  }

  buildStack();

  mm.addEventListener('change', function () {
    buildStack();
  });

  window.addEventListener('resize', function () {
    ScrollTrigger.refresh();
  });
});
