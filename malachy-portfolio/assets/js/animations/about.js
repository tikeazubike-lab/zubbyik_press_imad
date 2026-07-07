/**
 * About Section Animation
 *
 * - Fade upward with stagger.
 * - Image parallax.
 * - Section title reveal.
 *
 * @package Malachy_Portfolio
 */

document.addEventListener('DOMContentLoaded', function () {
  const section = document.getElementById('about');
  if (!section) return;

  const reveals = section.querySelectorAll('.about-reveal');
  const illustration = section.querySelector('.about-illustration img');
  const stats = section.querySelector('.about-stats');

  // Title and text stagger
  gsap.from(reveals, {
    scrollTrigger: {
      trigger: section,
      start: 'top 80%',
      end: 'top 40%',
      scrub: 1,
    },
    y: 60,
    opacity: 0,
    stagger: 0.2,
    ease: 'power2.out',
  });

  // Stats stagger upward
  if (stats) {
    gsap.from(stats.querySelectorAll('div'), {
      scrollTrigger: {
        trigger: stats,
        start: 'top 85%',
        end: 'top 50%',
        scrub: 1,
      },
      y: 40,
      opacity: 0,
      stagger: 0.15,
      ease: 'power2.out',
    });
  }

  // Illustration parallax
  if (illustration) {
    gsap.to(illustration, {
      scrollTrigger: {
        trigger: section,
        start: 'top bottom',
        end: 'bottom top',
        scrub: 1.5,
      },
      y: -80,
      ease: 'power1.out',
    });
  }
});
