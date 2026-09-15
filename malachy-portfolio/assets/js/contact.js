/**
 * Contact Form + Discovery Panel
 *
 * Handles AJAX submission for both the main contact form and the discovery
 * panel form. Manages the slide-out / bottom-sheet panel open/close.
 *
 * @package Malachy_Portfolio
 */

document.addEventListener('DOMContentLoaded', function () {
  // ============================================================
  //  Discovery Panel
  // ============================================================
  const overlay    = document.getElementById('discovery-overlay');
  const panel      = document.getElementById('discovery-panel');
  const openBtn    = document.getElementById('open-discovery');
  const closeBtn   = document.getElementById('close-discovery');
  const discoveryForm = document.getElementById('discovery-form');

  function openPanel() {
    if (!panel || !overlay) return;
    overlay.classList.add('active');
    panel.classList.add('active');
    panel.setAttribute('aria-hidden', 'false');
    overlay.setAttribute('aria-hidden', 'false');
    if (openBtn) openBtn.setAttribute('aria-expanded', 'true');
    document.body.style.overflow = 'hidden';
    // Focus first interactive element
    setTimeout(() => { (closeBtn || panel).focus(); }, 100);
  }

  function closePanel() {
    if (!panel || !overlay) return;
    overlay.classList.remove('active');
    panel.classList.remove('active');
    panel.setAttribute('aria-hidden', 'true');
    overlay.setAttribute('aria-hidden', 'true');
    if (openBtn) openBtn.setAttribute('aria-expanded', 'false');
    document.body.style.overflow = '';
    if (openBtn) openBtn.focus();
  }

  if (openBtn)  openBtn.addEventListener('click', openPanel);
  if (closeBtn) closeBtn.addEventListener('click', closePanel);
  if (overlay)  overlay.addEventListener('click', closePanel);

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && panel && panel.classList.contains('active')) {
      closePanel();
    }
  });

  // Discovery form submission
  if (discoveryForm) {
    discoveryForm.addEventListener('submit', async function (e) {
      e.preventDefault();
      const btn = document.getElementById('discovery-submit');
      const status = document.getElementById('discovery-status');

      if (btn) { btn.disabled = true; btn.innerHTML = 'Sending...'; }
      if (status) status.textContent = '';

      try {
        const fd = new FormData(discoveryForm);
        const resp = await fetch(malachyAjax.ajaxurl, {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: new URLSearchParams(fd).toString(),
        });
        const result = await resp.json();

        if (result.success) {
          if (status) { status.textContent = result.data.message; status.style.color = 'var(--secondary)'; }
          discoveryForm.reset();
        } else {
          if (status) { status.textContent = result.data.message || 'Something went wrong.'; status.style.color = 'var(--destructive)'; }
        }
      } catch (err) {
        if (status) { status.textContent = 'Network error. Please try again.'; status.style.color = 'var(--destructive)'; }
      } finally {
        if (btn) { btn.disabled = false; btn.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 2 11 13M22 2l-7 20-4-9-9-4 20-7z"/></svg> Send Message'; }
      }
    });
  }

  // ============================================================
  //  Main Contact Form
  // ============================================================
  const form   = document.getElementById('contact-form');
  const status = document.getElementById('contact-status');
  const submitBtn = document.getElementById('contact-submit');

  if (!form) return;

  // Service lookup for ?service=<slug> pre-fill from offer CTAs.
  const serviceMap = {
    'fix-spam': 'Fix Business Emails Going to Spam',
    'email-audit': 'Audit and Report on Your Business Email Security and Deliverability',
    'domain-security': 'Lock Down Your Domain and DNS Against Spoofing, Hijacking and Email Fraud',
    'email-monitoring': 'Provide Ongoing Email & DNS Health Monitoring for Your Business',
    'm365-setup': 'Set Up Microsoft 365 Business Email With Your Custom Domain',
    'migrate-email': 'Migrate Business Email to Microsoft 365, Google Workspace',
    'migrate-website-email': 'Migrate Your Website and Business Email to a New Host',
    'wp-chatbot-assessment': 'Assess Your WordPress Site for AI Chatbot Integration',
    'wp-rebuild': 'Migrate and Rebuild a WordPress Site Onto a Modern Stack for AI Chatbot Integration',
  };

  const params = new URLSearchParams(window.location.search);
  const serviceSlug = params.get('service');
  const serviceField = document.getElementById('malachy_service');
  const serviceContext = document.getElementById('contact-service-context');

  if (serviceSlug && serviceMap[serviceSlug] && serviceField) {
    serviceField.value = serviceSlug;
    if (serviceContext) {
      serviceContext.textContent = 'Enquiry about: ' + serviceMap[serviceSlug];
      serviceContext.style.display = 'block';
    }
  }

  form.addEventListener('submit', async function (e) {
    e.preventDefault();

    // Disable button and show loading state
    if (submitBtn) {
      submitBtn.disabled = true;
      submitBtn.innerHTML = 'Sending...';
    }
    if (status) status.textContent = '';

    const formData = new FormData(form);

    try {
      // Use admin-ajax as the primary handler
      const params = new URLSearchParams(formData);
      const response = await fetch(malachyAjax.ajaxurl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: params.toString(),
      });

      const result = await response.json();

      if (result.success) {
        if (status) {
          status.textContent = result.data.message;
          status.style.color = 'var(--secondary)';
        }
        form.reset();
      } else {
        if (status) {
          status.textContent = result.data.message || 'Something went wrong.';
          status.style.color = 'var(--destructive)';
        }
      }
    } catch (err) {
      if (status) {
        status.textContent = 'Network error. Please try again.';
        status.style.color = 'var(--destructive)';
      }
    } finally {
      if (submitBtn) {
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 2 11 13M22 2l-7 20-4-9-9-4 20-7z"/></svg> Send Message';
      }
    }
  });
});
