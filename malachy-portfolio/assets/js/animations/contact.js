/**
 * Contact Section Animation
 *
 * Reference: polished-portfolio reveal animation.
 *
 * @package Malachy_Portfolio
 */

document.addEventListener('DOMContentLoaded', function () {
  const section = document.getElementById('contact');
  if (!section) return;

  const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  if (prefersReducedMotion) return;

  const reveals = section.querySelectorAll('.reveal');

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
