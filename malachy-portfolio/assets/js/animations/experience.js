/**
 * Experience Section Animation
 *
 * Reference: polished-portfolio timeline animation.
 * - Vertical line grows on scroll.
 * - Nodes scale in with stagger.
 * - Items reveal.
 *
 * @package Malachy_Portfolio
 */

document.addEventListener('DOMContentLoaded', function () {
  const section = document.getElementById('experience');
  const list = section ? section.querySelector('.experience-list') : null;
  const line = section ? section.querySelector('.experience-line') : null;

  if (!section || !list) return;

  const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  if (prefersReducedMotion) return;

  if (line) {
    gsap.from(line, {
      scaleY: 0,
      transformOrigin: 'top center',
      ease: 'none',
      scrollTrigger: {
        trigger: list,
        start: 'top 75%',
        end: 'bottom 75%',
        scrub: true,
      },
    });
  }

  const nodes = list.querySelectorAll('.experience-node');
  if (nodes.length > 0) {
    gsap.from(nodes, {
      scale: 0.4,
      opacity: 0,
      stagger: 0.2,
      ease: 'back.out(1.7)',
      scrollTrigger: {
        trigger: list,
        start: 'top 72%',
        once: true,
      },
    });
  }

  const items = list.querySelectorAll('.experience-item');
  items.forEach(function (item) {
    gsap.from(item, {
      opacity: 0,
      y: 34,
      duration: 0.75,
      ease: 'power3.out',
      scrollTrigger: {
        trigger: item,
        start: 'top 88%',
        once: true,
      },
    });
  });
});
