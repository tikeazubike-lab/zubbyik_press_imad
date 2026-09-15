/**
 * Hero Section Animation
 *
 * Reference: polished-portfolio hero animations.
 * - Fade-up stagger for kicker, title, copy, actions.
 * - Portrait scale/fade entrance.
 * - Subtle parallax on scroll.
 *
 * @package Malachy_Portfolio
 */

document.addEventListener('DOMContentLoaded', function () {
  const hero = document.getElementById('home');
  if (!hero) return;

  const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  if (prefersReducedMotion) return;

  const copyWrap = hero.querySelector('.hero-copy-wrap');
  const portrait = hero.querySelector('.hero-portrait');

  // Entrance animations
  gsap.from('.hero-kicker, .hero-title, .hero-copy, .hero-actions', {
    opacity: 0,
    y: 28,
    duration: 0.8,
    stagger: 0.08,
    ease: 'power3.out',
  });

  if (portrait) {
    gsap.from(portrait, {
      opacity: 0,
      scale: 1.08,
      duration: 1.2,
      ease: 'power3.out',
    });
  }

  // Scroll-driven parallax
  if (copyWrap) {
    gsap.to(copyWrap, {
      yPercent: -12,
      ease: 'none',
      scrollTrigger: {
        trigger: hero,
        start: 'top top',
        end: 'bottom top',
        scrub: true,
      },
    });
  }

  if (portrait) {
    gsap.to(portrait, {
      yPercent: 8,
      scale: 0.96,
      ease: 'none',
      scrollTrigger: {
        trigger: hero,
        start: 'top top',
        end: 'bottom top',
        scrub: true,
      },
    });
  }
});
