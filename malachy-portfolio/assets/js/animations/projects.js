/**
 * Projects Section Animation
 *
 * Card stacking is handled via CSS position: sticky.
 * GSAP adds scroll-triggered entry animations (fade/translate) to
 * each card's image and body as the card comes into view.
 *
 * No GSAP pin — the sticky cards scroll naturally, eliminating the
 * freeze/jump that occurs when a pinned ScrollTrigger releases.
 *
 * @package Malachy_Portfolio
 */

document.addEventListener('DOMContentLoaded', function () {
  const section = document.getElementById('projects');
  const stack = document.getElementById('projects-stack');
  if (!section || !stack) return;

  const cards = stack.querySelectorAll('.proj-card');
  if (cards.length === 0) return;

  // Each card animates in as it enters the viewport
  cards.forEach((card) => {
    const image = card.querySelector('.proj-card-image');
    const body = card.querySelector('.proj-card-body');

    if (image) {
      gsap.from(image, {
        scrollTrigger: {
          trigger: card,
          start: 'top 80%',
          end: 'top 40%',
          scrub: 1,
        },
        x: -80,
        opacity: 0,
        ease: 'power2.out',
      });
    }

    if (body) {
      gsap.from(body, {
        scrollTrigger: {
          trigger: card,
          start: 'top 80%',
          end: 'top 40%',
          scrub: 1,
        },
        x: 80,
        opacity: 0,
        ease: 'power2.out',
      });
    }
  });
});
