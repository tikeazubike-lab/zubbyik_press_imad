/**
 * Global Animations
 *
 * - Section title eyebrow reveal.
 * - Theme toggle persistence.
 *
 * @package Malachy_Portfolio
 */

document.addEventListener('DOMContentLoaded', function () {

  // ---- Theme Toggle ----
  const toggle = document.getElementById('theme-toggle');
  const sun = document.getElementById('theme-sun');
  const moon = document.getElementById('theme-moon');

  function setTheme(dark) {
    document.documentElement.classList.toggle('dark', dark);
    localStorage.setItem('theme', dark ? 'dark' : 'light');
    if (sun && moon) {
      sun.style.display = dark ? 'none' : 'block';
      moon.style.display = dark ? 'block' : 'none';
    }
  }

  // Initial state
  const saved = localStorage.getItem('theme');
  const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
  setTheme(saved === 'dark' || (!saved && prefersDark));

  toggle?.addEventListener('click', () => {
    setTheme(!document.documentElement.classList.contains('dark'));
  });

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
