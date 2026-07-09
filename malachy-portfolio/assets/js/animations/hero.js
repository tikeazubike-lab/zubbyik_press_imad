/**
 * Hero Section Animation
 *
 * - Pinned hero (desktop/tablet, mobile pinning exception).
 * - Portrait scales slowly on scroll.
 * - Text translates upward.
 * - CTA buttons have subtle hover motion.
 * - Marquee scrolls infinitely.
 *
 * Mobile Pinning Exception: pin is gated behind matchMedia at <= 767px.
 * Below that, hero degrades to simple fade reveal.
 *
 * @package Malachy_Portfolio
 */

document.addEventListener('DOMContentLoaded', function () {
  const hero = document.getElementById('home');
  const portrait = hero?.querySelector('.hero-portrait');
  const textCol = hero?.querySelector('.hero-text-col');
  const ctaRow = hero?.querySelector('.hero-cta-row');
  const marquee = document.getElementById('hero-marquee-track');
  const scrollHint = hero?.querySelector('.hero-scroll-hint');

  if (!hero) return;

  const mm = gsap.matchMedia();

  // Desktop/Tablet: pin + scrub animations
  mm.add('(min-width: 768px)', () => {
    // Pin the hero section
    ScrollTrigger.create({
      trigger: hero,
      start: 'top top',
      end: '+=120%',
      pin: true,
      pinSpacing: true,
      anticipatePin: 1,
    });

    // Portrait slow scale
    if (portrait) {
      gsap.to(portrait, {
        scrollTrigger: {
          trigger: hero,
          start: 'top top',
          end: 'bottom 80%',
          scrub: 1.5,
        },
        scale: 0.85,
        opacity: 0.7,
        y: 60,
        ease: 'power1.out',
      });
    }

    // Text translates upward
    if (textCol) {
      gsap.to(textCol, {
        scrollTrigger: {
          trigger: hero,
          start: 'top top',
          end: 'bottom 60%',
          scrub: 1,
        },
        y: -80,
        opacity: 0.6,
        ease: 'power1.out',
      });
    }

    // Marquee scroll
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

  // Mobile: simple fade-only, no pin
  mm.add('(max-width: 767px)', () => {
    gsap.fromTo(hero,
      { opacity: 0.8, y: 30 },
      {
        opacity: 1, y: 0,
        scrollTrigger: {
          trigger: hero,
          start: 'top 80%',
          end: 'top 40%',
          scrub: 1,
        },
        ease: 'power1.out',
        immediateRender: false,
      }
    );
  });

  // CTA hover (CSS-only, but smooth GSAP for enhanced feel)
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
