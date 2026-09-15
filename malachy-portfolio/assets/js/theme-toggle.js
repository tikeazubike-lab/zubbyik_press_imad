/**
 * Theme Toggle
 *
 * Persists light/dark preference to localStorage.
 * Loads on all pages (not front-page-only).
 *
 * @package Malachy_Portfolio
 */

document.addEventListener('DOMContentLoaded', function () {
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

  // Sync icon with current state
  const isDark = document.documentElement.classList.contains('dark');
  if (sun && moon) {
    sun.style.display = isDark ? 'none' : 'block';
    moon.style.display = isDark ? 'block' : 'none';
  }

  toggle?.addEventListener('click', () => {
    setTheme(!document.documentElement.classList.contains('dark'));
  });
});
