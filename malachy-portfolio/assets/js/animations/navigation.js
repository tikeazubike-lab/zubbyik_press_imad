/**
 * Navigation
 *
 * - Sticky glassmorphism header (scroll-based background).
 * - Mobile menu animation.
 * - Active link highlighting.
 * - Smart anchor navigation (works on all pages).
 *
 * Uses GSAP/ScrollTrigger on desktop; falls back to IntersectionObserver
 * and native smooth scrolling when GSAP is not available (mobile).
 *
 * @package Malachy_Portfolio
 */

document.addEventListener('DOMContentLoaded', function () {
  var header = document.getElementById('site-header');
  var logo = header && header.querySelector('.nav-logo');
  var toggle = document.getElementById('mobile-menu-toggle');
  var menu = document.getElementById('mobile-menu');
  var links = menu && menu.querySelectorAll('a[data-section-link]');
  var hasGsap = typeof gsap !== 'undefined' && typeof ScrollTrigger !== 'undefined';

  if (!header) return;

  // ---- Scroll-based glassmorphism nav background ----
  if (hasGsap) {
    // Desktop: GSAP ScrollTrigger
    gsap.to(header, {
      scrollTrigger: {
        trigger: document.body,
        start: 'top -80px',
        end: 'top -120px',
        onEnter: function () { header.classList.add('nav-scrolled'); },
        onLeaveBack: function () { header.classList.remove('nav-scrolled'); },
      },
    });
  } else {
    // Mobile: IntersectionObserver on a 1px sentinel at page top
    var sentinel = document.createElement('div');
    sentinel.style.position = 'absolute';
    sentinel.style.top = '0';
    sentinel.style.left = '0';
    sentinel.style.width = '1px';
    sentinel.style.height = '1px';
    sentinel.style.pointerEvents = 'none';
    sentinel.style.opacity = '0';
    document.body.prepend(sentinel);

    var navObserver = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (!entry.isIntersecting) {
          header.classList.add('nav-scrolled');
        } else {
          header.classList.remove('nav-scrolled');
        }
      });
    }, { threshold: 0 });
    navObserver.observe(sentinel);
  }

  // ---- Mobile menu toggle ----
  if (toggle && menu) {
    toggle.addEventListener('click', function () {
      var isOpen = menu.style.display !== 'none';
      menu.style.display = isOpen ? 'none' : 'block';
      var openIcon = document.getElementById('menu-icon-open');
      var closeIcon = document.getElementById('menu-icon-close');
      if (openIcon) openIcon.style.display = isOpen ? 'block' : 'none';
      if (closeIcon) closeIcon.style.display = isOpen ? 'none' : 'block';
    });
  }

  // Close mobile menu on link click
  if (links) {
    links.forEach(function (link) {
      link.addEventListener('click', function () {
        menu.style.display = 'none';
        var openIcon = document.getElementById('menu-icon-open');
        var closeIcon = document.getElementById('menu-icon-close');
        if (openIcon) openIcon.style.display = 'block';
        if (closeIcon) closeIcon.style.display = 'none';
      });
    });
  }

  // ---- Desktop link active state based on scroll position ----
  var sectionIds = ['home', 'about', 'skills', 'projects', 'experience', 'blog', 'contact'];
  var navLinks = header.querySelectorAll('.nav-desktop-link');

  function setActive(id) {
    navLinks.forEach(function (link) {
      var href = link.getAttribute('href');
      var isActive = (id === 'home' && href === '/') ||
                      href === '/#' + id ||
                      href === '#' + id;
      if (isActive) {
        link.classList.add('active');
      } else {
        link.classList.remove('active');
      }
    });
  }

  if (hasGsap) {
    // Desktop: ScrollTrigger for smooth active highlighting
    sectionIds.forEach(function (id) {
      var el = document.getElementById(id);
      if (!el) return;
      ScrollTrigger.create({
        trigger: el,
        start: 'top center',
        end: 'bottom center',
        onEnter: function () { setActive(id); },
        onEnterBack: function () { setActive(id); },
      });
    });
  } else {
    // Mobile: IntersectionObserver for active highlighting
    var activeObserver = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          setActive(entry.target.id);
        }
      });
    }, { rootMargin: '-40% 0px -40% 0px' });

    sectionIds.forEach(function (id) {
      var el = document.getElementById(id);
      if (el) activeObserver.observe(el);
    });
  }

  // ---- Smart Anchor Navigation ----
  // Intercept clicks on section links to smooth-scroll.
  // Falls back to native navigation when section doesn't exist on current page.
  document.querySelectorAll('a[data-section-link]').forEach(function (link) {
    link.addEventListener('click', function (e) {
      var sectionId = this.getAttribute('data-section-link');
      if (!sectionId) return;

      var target = document.getElementById(sectionId);
      if (target) {
        e.preventDefault();
        var offset = 100;
        var top = target.getBoundingClientRect().top + window.scrollY - offset;
        window.scrollTo({ top: top, behavior: 'smooth' });
        // Update URL to reflect current section (bookmarkable)
        history.pushState(null, '', sectionId === 'home' ? '/' : '/#' + sectionId);
      }
      // If target doesn't exist, default browser navigation applies.
    });
  });

  // ---- Hash-On-Load Scroll ----
  // Scrolls to the correct section after a cross-page navigation (e.g. /blog/ -> /#about).
  if (window.location.hash) {
    var hashId = window.location.hash.replace('#', '');
    if (hashId) {
      var hashEl = document.getElementById(hashId);
      if (hashEl) {
        setTimeout(function () {
          var offset = 100;
          var top = hashEl.getBoundingClientRect().top + window.scrollY - offset;
          window.scrollTo({ top: top, behavior: 'smooth' });
        }, 300);
      }
    }
  }
});
