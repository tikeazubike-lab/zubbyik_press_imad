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
    gsap.from(heading, {
      scrollTrigger: {
        trigger: section,
        start: 'top 80%',
        end: 'top 50%',
        scrub: 1,
      },
      y: 60,
      opacity: 0,
      ease: 'power2.out',
    });
  }

  // Form stagger fade-up
  if (form) {
    gsap.from(form.querySelectorAll('input, textarea, button'), {
      scrollTrigger: {
        trigger: form,
        start: 'top 80%',
        end: 'top 40%',
        scrub: 1,
      },
      y: 40,
      ease: 'power2.out',
    });
  }

  // Social links fade-up
  if (social) {
    gsap.from(social, {
      scrollTrigger: {
        trigger: social,
        start: 'top 90%',
        end: 'top 60%',
        scrub: 1,
      },
      y: 30,
      opacity: 0,
      ease: 'power2.out',
    });
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
