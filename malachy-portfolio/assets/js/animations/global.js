/**
 * Global Animations
 *
 * Reference: polished-portfolio reveal animation for journal cards.
 *
 * @package Malachy_Portfolio
 */

document.addEventListener('DOMContentLoaded', function () {
  const journal = document.getElementById('blog');
  if (!journal) return;

  const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  if (prefersReducedMotion) return;

  const reveals = journal.querySelectorAll('.reveal');

  reveals.forEach(function (element) {
    gsap.from(element, {
      opacity: 0,
      y: 34,
      duration: 0.75,
      ease: 'power3.out',
      scrollTrigger: {
        trigger: element,
        start: 'top 88%',
        once: true,
      },
    });
  });
});
