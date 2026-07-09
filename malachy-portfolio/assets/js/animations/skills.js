/**
 * Skills Section Animation
 *
 * - Cards stagger upward on scroll reveal.
 * - Hover lift (CSS already accomplishes this).
 *
 * @package Malachy_Portfolio
 */

document.addEventListener('DOMContentLoaded', function () {
  const section = document.getElementById('skills');
  const grid = document.getElementById('skills-grid');
  if (!section || !grid) return;

  const cards = grid.querySelectorAll('.skill-card');

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
      stagger: 0.12,
      ease: 'power2.out',
      immediateRender: false,
    }
  );
});
