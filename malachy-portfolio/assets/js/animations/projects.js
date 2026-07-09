/**
 * Projects Section Animation
 *
 * GSAP ScrollTrigger pinning for stacked card reveal.
 * Each card pins for a full viewport before releasing to the next.
 * Falls back to simple fade-in on mobile (no pinning).
 *
 * @package Malachy_Portfolio
 */
document.addEventListener('DOMContentLoaded', function () {
  const section = document.getElementById('projects');
  const stack = document.getElementById('projects-stack');
  if (!section || !stack) return;

  const isDesktop = window.matchMedia('(min-width: 768px)').matches;
  if (!isDesktop) return;

  const cards = stack.querySelectorAll('.proj-card');
  if (cards.length === 0) return;

  // Kill any existing ScrollTriggers in this section
  ScrollTrigger.getAll().forEach(function (t) {
    if (t.vars && t.vars.trigger && stack.contains(t.vars.trigger)) {
      t.kill();
    }
  });

  // Animate each card's content
  cards.forEach(function (card) {
    var image = card.querySelector('.proj-card-image');
    var body = card.querySelector('.proj-card-body');

    if (image) {
      gsap.fromTo(image,
        { x: -60, opacity: 0 },
        {
          x: 0, opacity: 1,
          scrollTrigger: {
            trigger: card,
            start: 'top 80%',
            end: 'top 40%',
            scrub: 1,
          },
          ease: 'power2.out',
          immediateRender: false,
        }
      );
    }

    if (body) {
      gsap.fromTo(body,
        { x: 60, opacity: 0 },
        {
          x: 0, opacity: 1,
          scrollTrigger: {
            trigger: card,
            start: 'top 80%',
            end: 'top 40%',
            scrub: 1,
          },
          ease: 'power2.out',
          immediateRender: false,
        }
      );
    }
  });

  // Pin each card for a scroll distance equal to its own height
  // so it fully scrolls into view before the next card takes over
  cards.forEach(function (card, i) {
    var isLast = i === cards.length - 1;

    ScrollTrigger.create({
      trigger: card,
      start: 'top 4rem',
      end: isLast ? 'bottom top' : 'bottom top',
      pin: true,
      pinSpacing: false,
      anticipatePin: 1,
    });
  });

  // Refresh on resize
  window.addEventListener('resize', function () {
    ScrollTrigger.refresh();
  });
});
