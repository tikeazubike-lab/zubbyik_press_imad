/**
 * Offers Section Animation
 *
 * Staggered grid reveal from center outward using GSAP ScrollTrigger.
 * Each offer group's cards animate independently.
 *
 * @package Malachy_Portfolio
 */

document.addEventListener('DOMContentLoaded', function () {
  const section = document.getElementById('offers');
  if (!section) return;

  const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  if (prefersReducedMotion) return;

  const groups = section.querySelectorAll('.offers-group');

  groups.forEach(function (group) {
    const cards = group.querySelectorAll('.offer-card');
    if (cards.length === 0) return;

    gsap.from(cards, {
      scale: 0,
      opacity: 0,
      duration: 0.5,
      stagger: { amount: 0.6, from: 'center' },
      ease: 'back.out(1.4)',
      scrollTrigger: {
        trigger: group,
        start: 'top 80%',
        once: true,
      },
    });
  });
});
