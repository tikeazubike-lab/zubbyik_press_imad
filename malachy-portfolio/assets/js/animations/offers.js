/**
 * Offers Section Animation
 *
 * Keeps the fixed fromTo() pattern from the existing code.
 * Featured service + numbered service items reveal on scroll.
 *
 * @package Malachy_Portfolio
 */

document.addEventListener('DOMContentLoaded', function () {
  const section = document.getElementById('offers');
  if (!section) return;

  const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  if (prefersReducedMotion) return;

  const featured = section.querySelector('.featured-service');
  const items = section.querySelectorAll('.service-item');

  if (featured) {
    gsap.fromTo(featured,
      { y: 40, opacity: 0 },
      {
        y: 0,
        opacity: 1,
        duration: 0.75,
        ease: 'power3.out',
        clearProps: 'transform,opacity',
        scrollTrigger: {
          trigger: featured,
          start: 'top 85%',
          once: true,
        },
      }
    );
  }

  if (items.length > 0) {
    gsap.fromTo(items,
      { scale: 0, opacity: 0 },
      {
        scale: 1,
        opacity: 1,
        duration: 0.5,
        stagger: { amount: 0.6, from: 'start' },
        ease: 'back.out(1.4)',
        clearProps: 'transform,opacity',
        scrollTrigger: {
          trigger: section.querySelector('.service-list'),
          start: 'top 85%',
          once: true,
        },
      }
    );
  }
});
