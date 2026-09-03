/**
 * Offers Section Animation
 *
 * - Offer cards stagger upward on scroll reveal.
 * - Hover lift handled by CSS.
 *
 * @package Malachy_Portfolio
 */

document.addEventListener('DOMContentLoaded', function () {
  const section = document.getElementById('offers');
  const grid = document.getElementById('offers-groups');
  if (!section || !grid) return;

  const cards = grid.querySelectorAll('.offer-card');

  gsap.fromTo(cards,
    { y: 50, opacity: 0 },
    {
      y: 0, opacity: 1,
      scrollTrigger: {
        trigger: section,
        start: 'top 80%',
        end: 'bottom 40%',
        scrub: 1,
      },
      stagger: 0.1,
      ease: 'power2.out',
      immediateRender: false,
    }
  );
});
