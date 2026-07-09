/**
 * Contact Section Animation
 *
 * - Smooth reveal with fade-up.
 * - Animated background orbs float.
 * - Minimal motion overall.
 *
 * @package Malachy_Portfolio
 */

document.addEventListener('DOMContentLoaded', function () {
  const section = document.getElementById('contact');
  const heading = section?.querySelector('.contact-heading');
  const form = section?.querySelector('.contact-form');
  const social = section?.querySelector('.contact-social');
  const orbA = document.getElementById('contact-orb-a');
  const orbB = document.getElementById('contact-orb-b');

  if (!section) return;

  // Heading fade-up
  if (heading) {
    gsap.fromTo(heading,
      { y: 60, opacity: 0 },
      {
        y: 0, opacity: 1,
        scrollTrigger: {
          trigger: section,
          start: 'top 80%',
          end: 'top 50%',
          scrub: 1,
        },
        ease: 'power2.out',
        immediateRender: false,
      }
    );
  }

  // Form stagger fade-up
  if (form) {
    gsap.fromTo(form.querySelectorAll('input, textarea, button'),
      { y: 40 },
      {
        y: 0,
        scrollTrigger: {
          trigger: form,
          start: 'top 80%',
          end: 'top 40%',
          scrub: 1,
        },
        ease: 'power2.out',
        immediateRender: false,
      }
    );
  }

  // Social links fade-up
  if (social) {
    gsap.fromTo(social,
      { y: 30, opacity: 0 },
      {
        y: 0, opacity: 1,
        scrollTrigger: {
          trigger: social,
          start: 'top 90%',
          end: 'top 60%',
          scrub: 1,
        },
        ease: 'power2.out',
        immediateRender: false,
      }
    );
  }

  // Orb float animation (continuous)
  if (orbA) {
    gsap.to(orbA, {
      y: 40,
      x: 20,
      duration: 6,
      repeat: -1,
      yoyo: true,
      ease: 'sine.inOut',
    });
  }

  if (orbB) {
    gsap.to(orbB, {
      y: -30,
      x: -15,
      duration: 8,
      repeat: -1,
      yoyo: true,
      ease: 'sine.inOut',
    });
  }
});
