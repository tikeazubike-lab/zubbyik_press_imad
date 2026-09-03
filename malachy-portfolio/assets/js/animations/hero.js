/**
 * Hero Section Animation
 *
 * Pure scroll-driven transforms (no pin) to avoid duplicate pinned layers.
 * Portrait scales/fades, text rises, marquee scrubs.
 * Falls back to a simple fade on mobile and respects prefers-reduced-motion.
 *
 * @package Malachy_Portfolio
 */

document.addEventListener('DOMContentLoaded', function () {
  const hero = document.getElementById('home');
  const portrait = hero?.querySelector('.hero-portrait');
  const textCol = hero?.querySelector('.hero-text-col');
  const ctaRow = hero?.querySelector('.hero-cta-row');
  const marquee = document.getElementById('hero-marquee-track');

  if (!hero) return;

  const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  if (prefersReducedMotion) return;

  const mm = gsap.matchMedia();

  // Desktop/Tablet: scrub transforms only, no pin
  mm.add('(min-width: 768px)', () => {
    if (portrait) {
      gsap.to(portrait, {
        scrollTrigger: {
          trigger: hero,
          start: 'top top',
          end: 'bottom top',
          scrub: 1.5,
        },
        scale: 0.88,
        opacity: 0.6,
        y: 60,
        ease: 'none',
      });
    }

    if (textCol) {
      gsap.to(textCol, {
        scrollTrigger: {
          trigger: hero,
          start: 'top top',
          end: 'bottom top',
          scrub: 1,
        },
        y: -60,
        opacity: 0.5,
        ease: 'none',
      });
    }

    if (marquee) {
      gsap.to(marquee, {
        xPercent: -50,
        ease: 'none',
        scrollTrigger: {
          trigger: hero,
          start: 'top top',
          end: 'bottom top',
          scrub: 1,
        },
      });
    }
  });

  // Mobile: simple fade-only
  mm.add('(max-width: 767px)', () => {
    gsap.fromTo(hero,
      { opacity: 0.8, y: 20 },
      {
        opacity: 1, y: 0,
        scrollTrigger: {
          trigger: hero,
          start: 'top 85%',
          end: 'top 45%',
          scrub: 1,
        },
        ease: 'power1.out',
        immediateRender: false,
      }
    );
  });

  // CTA hover
  if (ctaRow) {
    ctaRow.querySelectorAll('a').forEach(btn => {
      btn.addEventListener('mouseenter', () => {
        gsap.to(btn, { y: -3, duration: 0.3, ease: 'power2.out' });
      });
      btn.addEventListener('mouseleave', () => {
        gsap.to(btn, { y: 0, duration: 0.3, ease: 'power2.out' });
      });
    });
  }
});
