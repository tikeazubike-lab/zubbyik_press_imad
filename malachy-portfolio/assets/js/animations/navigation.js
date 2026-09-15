/**
 * Navigation
 *
 * - Mobile menu toggle (adds .is-open to .site-nav).
 * - Close menu on link click.
 * - Active link highlighting.
 * - Smart anchor navigation.
 *
 * @package Malachy_Portfolio
 */

document.addEventListener('DOMContentLoaded', function () {
  var header = document.getElementById('site-header');
  var nav = document.getElementById('site-nav');
  var toggle = document.getElementById('mobile-menu-toggle');
  var openIcon = document.getElementById('menu-icon-open');
  var closeIcon = document.getElementById('menu-icon-close');
  var navLinks = nav ? nav.querySelectorAll('a') : [];
  var hasGsap = typeof gsap !== 'undefined' && typeof ScrollTrigger !== 'undefined';

  if (!header || !nav || !toggle) return;

  function setMenuOpen(isOpen) {
    if (isOpen) {
      nav.classList.add('is-open');
      toggle.setAttribute('aria-expanded', 'true');
      if (openIcon) openIcon.style.display = 'none';
      if (closeIcon) closeIcon.style.display = 'block';
    } else {
      nav.classList.remove('is-open');
      toggle.setAttribute('aria-expanded', 'false');
      if (openIcon) openIcon.style.display = 'block';
      if (closeIcon) closeIcon.style.display = 'none';
    }
  }

  toggle.addEventListener('click', function () {
    setMenuOpen(!nav.classList.contains('is-open'));
  });

  navLinks.forEach(function (link) {
    link.addEventListener('click', function () {
      setMenuOpen(false);
    });
  });

  // ---- Active link highlighting ----
  var sectionIds = ['work', 'experience', 'contact', 'blog'];
  var desktopLinks = header.querySelectorAll('.site-nav a');

  function setActive(id) {
    desktopLinks.forEach(function (link) {
      var href = link.getAttribute('href') || '';
      var isActive = href.indexOf('#' + id) !== -1 || (id === 'blog' && href.indexOf('/blog') !== -1);
      link.classList.toggle('active', isActive);
    });
  }

  if (hasGsap) {
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

  // ---- Smart anchor navigation ----
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
        history.pushState(null, '', sectionId === 'home' ? '/' : '/#' + sectionId);
      }
    });
  });

  // ---- Hash-on-load scroll ----
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
