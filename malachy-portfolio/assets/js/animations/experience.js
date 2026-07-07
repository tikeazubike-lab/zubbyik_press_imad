/**
 * Experience Section Animation
 *
 * - Vertical growing timeline line.
 * - Milestones reveal as user scrolls.
 *
 * @package Malachy_Portfolio
 */

document.addEventListener('DOMContentLoaded', function () {
  const section = document.getElementById('experience');
  const timeline = document.getElementById('exp-timeline');
  const lineFill = document.getElementById('exp-line-fill');
  if (!section || !timeline) return;

  const items = timeline.querySelectorAll('.exp-item');

  // Animate the vertical line growing
  if (lineFill) {
    gsap.to(lineFill, {
      scrollTrigger: {
        trigger: timeline,
        start: 'top 80%',
        end: 'bottom 20%',
        scrub: 1.5,
        refreshPriority: -10,
      },
      scaleY: 1,
      transformOrigin: 'top center',
      ease: 'none',
    });
  }

  // Animate each milestone item
  items.forEach((item, i) => {
    gsap.from(item, {
      scrollTrigger: {
        trigger: item,
        start: 'top 85%',
        end: 'top 50%',
        scrub: 1,
      },
      y: 50,
      opacity: 0,
      ease: 'power2.out',
    });
  });
});
