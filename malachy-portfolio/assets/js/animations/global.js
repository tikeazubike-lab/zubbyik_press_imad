/**
 * Global Animations
 *
 * - Section title eyebrow reveal.
 * - Blog cards reveal.
 *
 * @package Malachy_Portfolio
 */

document.addEventListener('DOMContentLoaded', function () {

  // ---- Blog cards reveal ----
  const blogGrid = document.getElementById('blog-grid');
  if (blogGrid) {
    const blogCards = blogGrid.querySelectorAll('.blog-card');
    gsap.fromTo(blogCards,
      { y: 40, opacity: 0 },
      {
        y: 0, opacity: 1,
        scrollTrigger: {
          trigger: blogGrid,
          start: 'top 85%',
          end: 'top 40%',
          scrub: 1,
        },
        stagger: 0.15,
        ease: 'power2.out',
        immediateRender: false,
      }
    );
  }
});
