/**
 * Contact Form + Discovery Panel + Conditional Hero CTA
 *
 * Handles AJAX submission for both the main contact form and the discovery
 * panel form. Manages the slide-out / bottom-sheet panel open/close.
 * Submits to both the imad-automation backend (Telegram) and WordPress
 * admin-ajax (email) via Promise.allSettled for redundancy.
 *
 * @package Malachy_Portfolio
 */

document.addEventListener('DOMContentLoaded', function () {
  // ============================================================
  //  Discovery Panel
  // ============================================================
  var overlay    = document.getElementById('discovery-overlay');
  var panel      = document.getElementById('discovery-panel');
  var openBtn    = document.getElementById('open-discovery');
  var closeBtn   = document.getElementById('close-discovery');
  var discoveryForm = document.getElementById('discovery-form');

  function openPanel() {
    if (!panel || !overlay) return;
    overlay.classList.add('active');
    panel.classList.add('active');
    panel.setAttribute('aria-hidden', 'false');
    overlay.setAttribute('aria-hidden', 'false');
    if (openBtn) openBtn.setAttribute('aria-expanded', 'true');
    document.body.style.overflow = 'hidden';
    setTimeout(function () { (closeBtn || panel).focus(); }, 100);
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

  // Discovery form submission (unchanged)
  if (discoveryForm) {
    discoveryForm.addEventListener('submit', async function (e) {
      e.preventDefault();
      var btn = document.getElementById('discovery-submit');
      var status = document.getElementById('discovery-status');

      if (btn) { btn.disabled = true; btn.innerHTML = 'Sending...'; }
      if (status) status.textContent = '';

      try {
        var fd = new FormData(discoveryForm);
        var resp = await fetch(malachyAjax.ajaxurl, {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: new URLSearchParams(fd).toString(),
        });
        var result = await resp.json();

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
  //  Main Contact Form — dual submit (Telegram + Email)
  // ============================================================
  var form   = document.getElementById('contact-form');
  var status = document.getElementById('contact-status');
  var submitBtn = document.getElementById('contact-submit');

  if (!form) return;

  var IMAD_API_URL = 'https://api.imadconsulting.co.uk/api/leads';
  var IMAD_FORM_SECRET = '801f2cef116683f7c6da5a0442a266a7cfa5247e3d229b9c7af3d148d58e05b1';

  // Service lookup for ?service=<slug> pre-fill from offer CTAs
  var serviceMap = {
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

  var urlParams = new URLSearchParams(window.location.search);
  var serviceSlug = urlParams.get('service');
  var serviceField = document.getElementById('malachy_service');
  var serviceContext = document.getElementById('contact-service-context');

  if (serviceSlug && serviceMap[serviceSlug] && serviceField) {
    serviceField.value = serviceSlug;
    if (serviceContext) {
      serviceContext.textContent = 'Enquiry about: ' + serviceMap[serviceSlug];
      serviceContext.style.display = 'block';
    }
  }

  // --- Idempotency key (stable per page load) ---
  if (!window.__imad_idem) {
    window.__imad_idem = crypto.randomUUID();
  }

  form.addEventListener('submit', function (e) {
    e.preventDefault();

    if (submitBtn) { submitBtn.disabled = true; submitBtn.innerHTML = 'Sending...'; }
    if (status) status.textContent = '';

    // Build JSON payload for imad-automation backend
    var payload = {
      name: form.malachy_name.value,
      contact: form.malachy_email.value,
      problem_text: form.malachy_message.value,
      website: form.website ? form.website.value : '',
      source_campaign: form.source_campaign ? form.source_campaign.value : null,
      idempotency_key: window.__imad_idem,
    };

    // Build form data for WordPress AJAX (email path)
    var wpFormData = new FormData(form);

    // Fire BOTH independently — lead only lost if BOTH fail
    Promise.allSettled([
      fetch(IMAD_API_URL, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Form-Secret': IMAD_FORM_SECRET,
        },
        body: JSON.stringify(payload),
      }),
      fetch(malachyAjax.ajaxurl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams(wpFormData).toString(),
      }),
    ]).then(function (results) {
      var backendOk = results[0].status === 'fulfilled' && results[0].value.ok;
      var emailOk = results[1].status === 'fulfilled' && results[1].value.ok;

      if (backendOk || emailOk) {
        if (status) {
          status.textContent = 'Thanks! I\'ll get back to you soon.';
          status.style.color = 'var(--secondary)';
        }
        form.reset();
        if (serviceContext) serviceContext.style.display = 'none';
      } else {
        if (status) {
          status.textContent = 'Could not send message. Please try again later.';
          status.style.color = 'var(--destructive)';
        }
      }
    }).catch(function () {
      if (status) {
        status.textContent = 'Network error. Please try again.';
        status.style.color = 'var(--destructive)';
      }
    }).finally(function () {
      if (submitBtn) {
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 2 11 13M22 2l-7 20-4-9-9-4 20-7z"/></svg> Send enquiry';
      }
    });
  });

  // ============================================================
  //  Task 3 — Conditional Hero CTA
  // ============================================================
  var utmSource = urlParams.get('utm_source');
  var utmCampaign = urlParams.get('utm_campaign');

  if (utmSource) {
    var viewProjectBtn = document.querySelector('.hero-actions .btn-primary');
    if (viewProjectBtn) {
      viewProjectBtn.textContent = 'Tell me your problem';
      viewProjectBtn.onclick = function (ev) {
        ev.preventDefault();
        document.getElementById('contact').scrollIntoView({ behavior: 'smooth' });
      };
    }

    if (utmCampaign) {
      var campaignField = document.getElementById('source_campaign');
      if (campaignField) campaignField.value = utmCampaign;
    }
  }
});
