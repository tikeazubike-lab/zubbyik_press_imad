/**
 * Business Checkup — question set, deterministic scoring, and UI.
 *
 * Plain JavaScript, no dependencies, no build step, no framework (HO-063 §14).
 * Scoring is a pure function so it can be unit-tested without a DOM (§6, §22).
 *
 * Sources of truth: HO-061 (specification), HO-062 (specification response), HO-063 (implementation brief).
 *
 * Architecture note: this file deliberately avoids crypto, fetch and any secure-context-only
 * API so the checkup works on plain-HTTP origins as well as HTTPS.
 *
 * @package Malachy_Portfolio
 */
(function () {
  'use strict';

  // =========================================================================
  // 1. Question data (HO-063 §5)
  //    Every `points` value here is specified; do not normalise it.
  // =========================================================================

  var SERVICE_SLUGS = [
    'fix-spam',
    'email-audit',
    'domain-security',
    'email-monitoring',
    'm365-setup',
    'migrate-email',
    'migrate-website-email',
    'wp-chatbot-assessment',
    'wp-rebuild'
  ];

  // Existing offer wording — mirrors the contact form's own service map so the checkup
  // never invents a service or a claim (HO-063 §8).
  var SERVICE_OFFERS = {
    'fix-spam': 'Fix Business Emails Going to Spam',
    'email-audit': 'Audit and Report on Your Business Email Security and Deliverability',
    'domain-security': 'Lock Down Your Domain and DNS Against Spoofing, Hijacking and Email Fraud',
    'email-monitoring': 'Provide Ongoing Email & DNS Health Monitoring for Your Business',
    'm365-setup': 'Set Up Microsoft 365 Business Email With Your Custom Domain',
    'migrate-email': 'Migrate Business Email to Microsoft 365, Google Workspace',
    'migrate-website-email': 'Migrate Your Website and Business Email to a New Host',
    'wp-chatbot-assessment': 'Assess Your WordPress Site for AI Chatbot Integration',
    'wp-rebuild': 'Migrate and Rebuild a WordPress Site Onto a Modern Stack for AI Chatbot Integration'
  };

  var NEXT_ACTIONS = {
    'fix-spam': 'Start with the reason your mail is being filtered — that is quickest to diagnose and usually quickest to fix.',
    'email-audit': 'Have the whole setup checked and written up, so you know what is actually wrong before paying to fix anything.',
    'm365-setup': 'Get your business email onto your own domain, so quotes and invoices come from you rather than a free mailbox.',
    'wp-rebuild': 'Deal with the website next — it is the first impression most customers get of your business.',
    'email-monitoring': 'Set up monitoring so you hear about a problem before a customer does.'
  };

  // Questions in display order. `priorityOrder` is the deterministic tie-break order (HO-063 §7).
  var QUESTIONS = [
    {
      id: 'stage',
      required: true,
      scored: false,
      maxPoints: 0,
      label: 'Which best describes your business right now?',
      context: 'So the result makes sense for the size of business you run.',
      options: [
        { value: 'solo', label: 'Just me / freelancer', points: 0 },
        { value: 'small', label: 'Small team (2-10 people)', points: 0 },
        { value: 'growing', label: 'Growing business (11-50 people)', points: 0 },
        { value: 'larger', label: 'Larger business or several teams', points: 0 }
      ]
    },
    {
      id: 'conversations',
      dimension: 'business_health',
      required: true,
      scored: true,
      maxPoints: 3,
      label: 'Do you have one place where every customer conversation is recorded?',
      context: 'Enquiries, quotes, follow-ups — wherever they start.',
      options: [
        { value: 'always', label: 'Yes — every enquiry lands in one place I check', points: 3 },
        { value: 'mostly', label: 'Mostly — but some arrive in other channels', points: 2 },
        { value: 'scattered', label: 'No — they are scattered across phone, WhatsApp and email', points: 1 },
        { value: 'not_tracked', label: 'I do not really track enquiries', points: 0 }
      ],
      gapFinding: 'Enquiries arrive in several places at once, which is usually where they get dropped.',
      strengthFinding: 'You have one place where customer conversations land, so nothing depends on memory.'
    },
    {
      id: 'presence',
      dimension: 'business_health',
      required: true,
      scored: true,
      maxPoints: 3,
      label: 'When someone searches for your business, what do they find?',
      context: 'A customer checking you out before getting in touch.',
      options: [
        { value: 'current', label: 'A website I am happy with, and it is up to date', points: 3 },
        { value: 'outdated', label: 'A website, but it is out of date', points: 1 },
        { value: 'social_only', label: 'Only social media or directory listings', points: 1 },
        { value: 'unsure', label: 'Not much, or I am not sure what shows up', points: 0 }
      ],
      gapFinding: 'Someone checking you out online does not get a clear, current picture of the business.',
      strengthFinding: 'Your online presence is current, so a customer checking you out finds the real thing.',
      serviceSlug: 'wp-rebuild'
    },
    {
      id: 'email_identity',
      dimension: 'business_health',
      required: true,
      scored: true,
      maxPoints: 3,
      label: 'How do quotes, invoices and updates go out to customers?',
      context: 'What customers see in their inbox from you.',
      options: [
        { value: 'own_domain', label: 'From a business address on my own domain, consistently', points: 3 },
        { value: 'mostly_professional', label: 'Mostly professional, with the odd inconsistency', points: 2 },
        { value: 'mixed', label: 'A mix of personal or free mail and business email', points: 1 },
        { value: 'personal_only', label: 'Mainly personal or free mail', points: 0 }
      ],
      gapFinding: 'The address on your documents does not always look like a business, which costs you trust at the point of payment.',
      strengthFinding: 'Your documents go out from a consistent business address on your own domain.',
      serviceSlug: 'm365-setup'
    },
    {
      id: 'wa_profile',
      dimension: 'whatsapp',
      required: true,
      scored: true,
      maxPoints: 2,
      label: 'Do you have a WhatsApp Business profile, or just your normal WhatsApp?',
      context: 'The difference is what a customer sees before they message you.',
      options: [
        { value: 'business', label: 'Yes — WhatsApp Business', points: 2 },
        { value: 'used_not_business', label: 'I use WhatsApp but not as a business profile', points: 1 },
        { value: 'not_set_up', label: 'WhatsApp is not set up for the business', points: 0 }
      ],
      gapFinding: 'Your WhatsApp does not present as a business, so the first contact looks less established than you are.',
      strengthFinding: 'You are using WhatsApp Business, so customers see a business profile before they message.'
    },
    {
      id: 'wa_number',
      dimension: 'whatsapp',
      required: true,
      scored: true,
      maxPoints: 2,
      label: 'Is the WhatsApp number customers use the same one you advertise?',
      context: 'Website, listings, business cards, invoices.',
      options: [
        { value: 'consistent', label: 'Yes — the same number everywhere', points: 2 },
        { value: 'inconsistent', label: 'It is different in some places', points: 1 },
        { value: 'no_clear', label: 'There is no clear business WhatsApp number', points: 0 }
      ],
      gapFinding: 'Different numbers in different places means messages can land somewhere nobody is watching.',
      strengthFinding: 'One WhatsApp number is presented consistently wherever customers look.'
    },
    {
      id: 'wa_response',
      dimension: 'whatsapp',
      required: true,
      scored: true,
      maxPoints: 2,
      label: 'What usually happens when a new customer messages you on WhatsApp?',
      context: 'Be honest — this is the one that costs the most enquiries.',
      options: [
        { value: 'clear_process', label: 'Someone replies, and it is clear who owns that', points: 2 },
        { value: 'inconsistent', label: 'It gets answered, but not consistently', points: 1 },
        { value: 'no_process', label: 'There is no clear process for it', points: 0 }
      ],
      gapFinding: 'A message can sit unanswered, and the customer simply moves on to the next business.',
      strengthFinding: 'Messages get answered with a clear owner, which is what turns a message into a job.'
    },
    {
      id: 'priority',
      required: false,
      scored: false,
      maxPoints: 0,
      label: 'Which of these would you most like to fix first?',
      context: 'Optional — it tells me what to lead with.',
      options: [
        { value: 'emails_spam', label: 'Emails from my business are not reaching customers', points: 0, slug: 'fix-spam' },
        { value: 'website_outdated', label: 'My website is out of date and not bringing enquiries', points: 0, slug: 'wp-rebuild' },
        { value: 'email_not_domain', label: 'My business email is not on my own domain', points: 0, slug: 'm365-setup' },
        { value: 'check_everything', label: 'I would rather someone checked it all over', points: 0, slug: 'email-audit' }
      ]
    }
  ];

  var PRIORITY_ORDER = [
    'conversations',
    'presence',
    'email_identity',
    'wa_profile',
    'wa_number',
    'wa_response'
  ];

  // Band thresholds (HO-063 §6).
  var BANDS = {
    business_health: [
      { max: 3, band: 'at_risk', label: 'At risk' },
      { max: 6, band: 'partly_covered', label: 'Partly covered' },
      { max: 9, band: 'solid', label: 'Solid' }
    ],
    whatsapp: [
      { max: 2, band: 'at_risk', label: 'At risk' },
      { max: 4, band: 'partly_covered', label: 'Partly covered' },
      { max: 6, band: 'solid', label: 'Solid' }
    ],
    overall: [
      { max: 5, band: 'needs_attention', label: 'Needs attention now' },
      { max: 10, band: 'some_gaps', label: 'Some gaps' },
      { max: 15, band: 'in_good_shape', label: 'In good shape' }
    ]
  };

  var EXPLANATIONS = {
    needs_attention: 'Right now a customer trying to reach you can fall through the gap. The foundations are worth fixing before spending anything on growth.',
    some_gaps: 'The basics are there, but there are gaps a customer will notice. Closing the two or three below would make the biggest difference.',
    in_good_shape: 'You are in good shape. The gaps left are small, and worth closing before they turn into the kind of problem that costs enquiries.'
  };

  var DIMENSION_LABELS = {
    business_health: 'Business Health',
    whatsapp: 'WhatsApp Readiness'
  };

  var TOTAL_QUESTIONS = QUESTIONS.length;
  var STORAGE_KEY = 'malachy.checkup.v1';

  function questionById(id) {
    for (var i = 0; i < QUESTIONS.length; i += 1) {
      if (QUESTIONS[i].id === id) return QUESTIONS[i];
    }
    return null;
  }

  function questionMaxPoints(question) {
    if (typeof question.maxPoints === 'number') return question.maxPoints;

    // Fallback: derive from the declared options (kept in sync by a unit test).
    var max = 0;
    question.options.forEach(function (option) {
      if (option.points > max) max = option.points;
    });
    return max;
  }

  function bandFor(dimension, score) {
    var table = BANDS[dimension];
    for (var i = 0; i < table.length; i += 1) {
      if (score <= table[i].max) return { band: table[i].band, label: table[i].label };
    }
    return { band: table[table.length - 1].band, label: table[table.length - 1].label };
  }

  // =========================================================================
  // 2. Validation (HO-063 §20)
  // =========================================================================

  function resolveOption(question, value) {
    if (typeof value !== 'string') return null;
    for (var i = 0; i < question.options.length; i += 1) {
      if (question.options[i].value === value) return question.options[i];
    }
    return null;
  }

  /** Returns { valid, missing: [questionId] } — unscored Q8 is optional. */
  function validateAnswers(answers) {
    var source = answers || {};
    var missing = [];

    QUESTIONS.forEach(function (question) {
      if (!question.required) return;
      if (!resolveOption(question, source[question.id])) missing.push(question.id);
    });

    return { valid: missing.length === 0, missing: missing };
  }

  // =========================================================================
  // 3. Scoring — pure, deterministic (HO-063 §6, §7)
  // =========================================================================

  function scoreCheckup(answers) {
    var source = answers || {};
    var invalid = [];
    var perQuestion = {};

    QUESTIONS.forEach(function (question) {
      var option = resolveOption(question, source[question.id]);
      if (question.scored && !option && question.required) invalid.push(question.id);
      perQuestion[question.id] = {
        question: question,
        option: option,
        points: question.scored && option ? option.points : 0,
        maxPoints: question.scored ? questionMaxPoints(question) : 0
      };
    });

    var dimensionScore = { business_health: 0, whatsapp: 0 };
    var dimensionMax = { business_health: 0, whatsapp: 0 };
    var overall = 0;

    PRIORITY_ORDER.forEach(function (id) {
      var entry = perQuestion[id];
      dimensionScore[entry.question.dimension] += entry.points;
      dimensionMax[entry.question.dimension] += entry.maxPoints;
      overall += entry.points;
    });

    // Priority = largest shortfall. Every maximum is fully attainable, so a question at its
    // maximum can never be reported as the priority (HO-063 §7; maxima differ, 3 vs 2).
    // Ties resolve by PRIORITY_ORDER, which places Business Health before WhatsApp.
    var priority = null;
    PRIORITY_ORDER.forEach(function (id) {
      var entry = perQuestion[id];
      var lost = entry.maxPoints - entry.points;
      if (lost <= 0) return;
      if (priority === null || lost > priority.pointsLost) {
        priority = {
          id: id,
          dimension: entry.question.dimension,
          pointsLost: lost,
          label: entry.question.label,
          optionLabel: entry.option ? entry.option.label : '',
          slug: entry.question.serviceSlug || null
        };
      }
    });

    var statedPriority = null;
    var priorityQuestion = questionById('priority');
    var statedOption = resolveOption(priorityQuestion, source.priority);
    if (statedOption) {
      statedPriority = {
        value: statedOption.value,
        label: statedOption.label,
        slug: statedOption.slug
      };
    }

    // Findings: gaps first (largest shortfall first, question order breaking ties), then
    // strengths, three to five total.
    var gaps = [];
    var strengths = [];
    PRIORITY_ORDER.forEach(function (id, order) {
      var entry = perQuestion[id];
      var lost = entry.maxPoints - entry.points;
      if (lost > 0) {
        gaps.push({
          id: id,
          tone: 'gap',
          pointsLost: lost,
          order: order,
          text: entry.question.gapFinding
        });
      } else {
        strengths.push({
          id: id,
          tone: 'strength',
          pointsLost: 0,
          order: order,
          text: entry.question.strengthFinding
        });
      }
    });

    gaps.sort(function (a, b) {
      if (b.pointsLost !== a.pointsLost) return b.pointsLost - a.pointsLost;
      return a.order - b.order;
    });

    var findings = gaps
      .concat(strengths)
      .slice(0, 5)
      .map(function (finding) {
        return {
          id: finding.id,
          tone: finding.tone,
          pointsLost: finding.pointsLost,
          text: finding.text
        };
      });

    var businessHealth = {
      score: dimensionScore.business_health,
      max: dimensionMax.business_health,
      band: bandFor('business_health', dimensionScore.business_health).band,
      bandLabel: bandFor('business_health', dimensionScore.business_health).label
    };
    var whatsapp = {
      score: dimensionScore.whatsapp,
      max: dimensionMax.whatsapp,
      band: bandFor('whatsapp', dimensionScore.whatsapp).band,
      bandLabel: bandFor('whatsapp', dimensionScore.whatsapp).label
    };
    var overallBand = bandFor('overall', overall);

    return {
      businessHealth: businessHealth,
      whatsapp: whatsapp,
      overall: {
        score: overall,
        max: 15,
        band: overallBand.band,
        bandLabel: overallBand.label
      },
      priority: priority,
      statedPriority: statedPriority,
      recommendedSlug: statedPriority ? statedPriority.slug : priority ? priority.slug : null,
      findings: findings,
      invalid: invalid,
      answered: PRIORITY_ORDER.filter(function (id) {
        return Boolean(perQuestion[id].option);
      }).length
    };
  }

  // =========================================================================
  // 4. Attribution + CTA construction (HO-063 §9, §10, §11, §24)
  // =========================================================================

  function buildContactUrl(options) {
    var opts = options || {};
    var base = typeof opts.base === 'string' && opts.base ? opts.base : '/';
    var params = [];

    // Only fixed internal slugs may ever reach a visitor-facing URL.
    if (typeof opts.slug === 'string' && SERVICE_SLUGS.indexOf(opts.slug) !== -1) {
      params.push('service=' + encodeURIComponent(opts.slug));
    }
    if (opts.utmSource) params.push('utm_source=' + encodeURIComponent(opts.utmSource));
    if (opts.utmCampaign) params.push('utm_campaign=' + encodeURIComponent(opts.utmCampaign));

    var url = base.indexOf('?') === -1 ? base : base.replace(/\?$/, '');
    return (params.length ? url + '?' + params.join('&') : url) + '#contact';
  }

  function buildWhatsAppUrl(number) {
    if (typeof number !== 'string' && typeof number !== 'number') return '';
    var digits = String(number).replace(/[^0-9]/g, '');
    if (digits.length < 6 || digits.length > 15) return '';
    return 'https://wa.me/' + digits;
  }

  function readAttribution(search) {
    var query = typeof search === 'string' ? search : '';
    var cleaned = query.replace(/^\?/, '');
    var out = { source: '', campaign: '' };

    if (!cleaned) return out;
    cleaned.split('&').forEach(function (pair) {
      var parts = pair.split('=');
      var key = parts[0];
      var value = parts.length > 1 ? decodeURIComponent(parts.slice(1).join('=')) : '';
      // Defensive: parameters are untrusted input, never rendered as HTML and length-capped.
      value = value.replace(/[^A-Za-z0-9._-]/g, '').slice(0, 64);
      if (key === 'utm_source') out.source = value;
      if (key === 'utm_campaign') out.campaign = value;
    });

    return out;
  }

  var api = {
    QUESTIONS: QUESTIONS,
    SERVICE_SLUGS: SERVICE_SLUGS,
    SERVICE_OFFERS: SERVICE_OFFERS,
    NEXT_ACTIONS: NEXT_ACTIONS,
    PRIORITY_ORDER: PRIORITY_ORDER,
    BANDS: BANDS,
    EXPLANATIONS: EXPLANATIONS,
    TOTAL_QUESTIONS: TOTAL_QUESTIONS,
    STORAGE_KEY: STORAGE_KEY,
    validateAnswers: validateAnswers,
    scoreCheckup: scoreCheckup,
    buildContactUrl: buildContactUrl,
    buildWhatsAppUrl: buildWhatsAppUrl,
    readAttribution: readAttribution
  };

  if (typeof module !== 'undefined' && module.exports) {
    module.exports = api;
  }

  if (typeof document === 'undefined') {
    return;
  }

  // =========================================================================
  // 5. DOM application
  // =========================================================================

  var state = {
    index: 0, // 0 = intro, 1..TOTAL_QUESTIONS = questions, TOTAL_QUESTIONS + 1 = result
    answers: {},
    result: null
  };

  var root = null;
  var stage = null;
  var progressWrap = null;
  var progressBar = null;
  var progressLabel = null;
  var live = null;
  var whatsappNumber = '';
  var contactBase = '/';

  function el(tag, className, text) {
    var node = document.createElement(tag);
    if (className) node.className = className;
    if (typeof text === 'string') node.textContent = text;
    return node;
  }

  function clearStage() {
    if (!stage) return;
    while (stage.firstChild) stage.removeChild(stage.firstChild);
  }

  function announce(message) {
    if (!live) return;
    live.textContent = '';
    // Re-announcing the same string needs a fresh node for screen readers to notice it.
    var note = el('span', '', message);
    live.appendChild(note);
  }

  function saveState() {
    try {
      window.sessionStorage.setItem(
        STORAGE_KEY,
        JSON.stringify({ index: state.index, answers: state.answers })
      );
    } catch (err) {
      /* storage unavailable or full — the checkup must keep working */
    }
  }

  function clearState() {
    try {
      window.sessionStorage.removeItem(STORAGE_KEY);
    } catch (err) {
      /* ignore */
    }
  }

  function restoreState() {
    var raw;
    try {
      raw = window.sessionStorage.getItem(STORAGE_KEY);
    } catch (err) {
      return;
    }
    if (!raw) return;

    var parsed;
    try {
      parsed = JSON.parse(raw);
    } catch (err) {
      clearState();
      return;
    }

    if (!parsed || typeof parsed !== 'object') {
      clearState();
      return;
    }

    var answers = {};
    if (parsed.answers && typeof parsed.answers === 'object') {
      QUESTIONS.forEach(function (question) {
        if (resolveOption(question, parsed.answers[question.id])) {
          answers[question.id] = parsed.answers[question.id];
        }
      });
    }

    var index = typeof parsed.index === 'number' ? parsed.index : 0;
    if (index < 0 || index > TOTAL_QUESTIONS) index = 0;

    state.answers = answers;
    state.index = index;
  }

  function updateProgress() {
    if (!progressWrap) return;
    if (state.index < 1 || state.index > TOTAL_QUESTIONS) {
      progressWrap.hidden = true;
      return;
    }
    progressWrap.hidden = false;
    var current = state.index;
    progressLabel.textContent = 'Question ' + current + ' of ' + TOTAL_QUESTIONS;
    progressBar.style.width = Math.round((current / TOTAL_QUESTIONS) * 100) + '%';
    var bar = document.getElementById('checkup-progress');
    if (bar) {
      bar.setAttribute('aria-valuenow', String(current));
      bar.setAttribute('aria-valuemax', String(TOTAL_QUESTIONS));
    }
  }

  function renderIntro() {
    clearStage();
    updateProgress();

    var panel = el('div', 'checkup-intro');

    var heading = el('h2', 'checkup-intro-title', 'Eight quick questions about how customers reach you');
    heading.id = 'checkup-intro-title';
    panel.appendChild(heading);

    var meta = el('p', 'checkup-intro-meta', '8 quick questions · about 2 minutes · no signup, no email required');
    meta.id = 'checkup-intro-meta';
    panel.appendChild(meta);

    var pitch = el(
      'p',
      'checkup-intro-copy',
      'You will get a plain-language result and the one thing worth fixing first. Nothing is sent anywhere until you choose to get in touch.'
    );
    panel.appendChild(pitch);

    var actions = el('div', 'checkup-actions');
    var start = el('button', 'checkup-btn checkup-btn-primary', 'Start the checkup');
    start.id = 'checkup-start';
    start.type = 'button';
    start.addEventListener('click', function () {
      state.index = 1;
      saveState();
      renderCurrent();
      announce('Starting the checkup. Question 1 of ' + TOTAL_QUESTIONS + '.');
    });
    actions.appendChild(start);
    panel.appendChild(actions);

    stage.appendChild(panel);
    announce('Business Checkup. ' + TOTAL_QUESTIONS + ' quick questions.');
  }

  function renderQuestion() {
    clearStage();
    updateProgress();

    var question = QUESTIONS[state.index - 1];
    var fieldset = el('fieldset', 'checkup-question');
    fieldset.id = 'checkup-question';
    fieldset.tabIndex = -1; // focus target for screen readers on each step
    fieldset.setAttribute('data-checkup-question-id', question.id);

    var legend = el('legend', 'checkup-legend', question.label);
    fieldset.appendChild(legend);

    if (question.context) {
      fieldset.appendChild(el('p', 'checkup-context', question.context));
    }

    fieldset.appendChild(el('p', 'checkup-required-note', question.required ? 'Required' : 'Optional'));

    var list = el('div', 'checkup-options');
    question.options.forEach(function (option) {
      var wrapper = el('label', 'checkup-option');
      var input = document.createElement('input');
      input.type = 'radio';
      input.name = 'checkup-' + question.id;
      input.value = option.value;
      input.setAttribute('data-checkup-answer', question.id);
      input.className = 'checkup-option-input';
      if (state.answers[question.id] === option.value) input.checked = true;

      input.addEventListener('change', function () {
        state.answers[question.id] = option.value;
        saveState();
        var error = document.getElementById('checkup-error');
        if (error) {
          error.hidden = true;
          error.textContent = '';
        }
      });

      wrapper.appendChild(input);
      wrapper.appendChild(el('span', 'checkup-option-label', option.label));
      list.appendChild(wrapper);
    });
    fieldset.appendChild(list);

    var error = el('p', 'checkup-error', '');
    error.id = 'checkup-error';
    error.setAttribute('role', 'alert');
    error.hidden = true;
    fieldset.appendChild(error);

    var actions = el('div', 'checkup-actions');
    var back = el('button', 'checkup-btn checkup-btn-ghost', 'Back');
    back.id = 'checkup-back';
    back.type = 'button';
    back.addEventListener('click', function () {
      state.index = state.index - 1;
      if (state.index < 1) state.index = 0;
      saveState();
      renderCurrent();
      announce(state.index === 0 ? 'Back to the start.' : 'Question ' + state.index + ' of ' + TOTAL_QUESTIONS + '.');
    });
    actions.appendChild(back);

    var next = el('button', 'checkup-btn checkup-btn-primary', state.index === TOTAL_QUESTIONS ? 'See my result' : 'Next');
    next.id = 'checkup-next';
    next.type = 'button';
    next.addEventListener('click', function () {
      advance(question);
    });
    actions.appendChild(next);

    if (!question.required) {
      var skip = el('button', 'checkup-btn checkup-btn-link', 'Skip this question');
      skip.id = 'checkup-skip';
      skip.type = 'button';
      skip.addEventListener('click', function () {
        delete state.answers[question.id];
        saveState();
        state.index = state.index + 1;
        renderCurrent();
        announce('Question skipped.');
      });
      actions.appendChild(skip);
    }

    fieldset.appendChild(actions);
    stage.appendChild(fieldset);

    // Focus management: the new question announces itself and the keyboard follows.
    fieldset.focus();
  }

  function advance(question) {
    if (question.required && !state.answers[question.id]) {
      var error = document.getElementById('checkup-error');
      if (error) {
        error.hidden = false;
        error.textContent = 'Please choose an answer before continuing.';
      }
      announce('Please choose an answer before continuing.');
      var firstInput = stage.querySelector('input[type="radio"]');
      if (firstInput) firstInput.focus();
      return;
    }

    state.index = state.index + 1;
    saveState();

    if (state.index > TOTAL_QUESTIONS) {
      state.index = TOTAL_QUESTIONS + 1;
      renderResult();
      return;
    }

    renderCurrent();
    announce('Question ' + state.index + ' of ' + TOTAL_QUESTIONS + '.');
  }

  function resultRow(labelText, valueText, attribute, attributeValue) {
    var row = el('div', 'checkup-result-row');
    var label = el('span', 'checkup-result-row-label', labelText);
    var value = el('span', 'checkup-result-row-value', valueText);
    if (attribute) value.setAttribute(attribute, attributeValue);
    row.appendChild(label);
    row.appendChild(value);
    return row;
  }

  function renderResult() {
    clearStage();
    updateProgress();

    var result = scoreCheckup(state.answers);
    state.result = result;

    var panel = el('div', 'checkup-result');
    panel.id = 'checkup-result';

    var kicker = el('p', 'checkup-result-kicker', 'Your result');
    panel.appendChild(kicker);

    var title = el('h2', 'checkup-result-title', result.overall.bandLabel);
    title.id = 'checkup-result-title';
    panel.appendChild(title);

    var scores = el('div', 'checkup-scores');
    scores.appendChild(
      resultRow('Overall', result.overall.score + '/' + result.overall.max, 'data-checkup-score', 'overall')
    );
    scores.appendChild(
      resultRow(
        DIMENSION_LABELS.business_health,
        result.businessHealth.score + '/' + result.businessHealth.max,
        'data-checkup-score',
        'business_health'
      )
    );
    scores.appendChild(
      resultRow(
        DIMENSION_LABELS.whatsapp,
        result.whatsapp.score + '/' + result.whatsapp.max,
        'data-checkup-score',
        'whatsapp'
      )
    );
    panel.appendChild(scores);

    var bands = el('div', 'checkup-bands');
    var bhBand = el('span', 'checkup-band', result.businessHealth.bandLabel);
    bhBand.setAttribute('data-checkup-band', 'business_health');
    var waBand = el('span', 'checkup-band', result.whatsapp.bandLabel);
    waBand.setAttribute('data-checkup-band', 'whatsapp');
    bands.appendChild(el('span', 'checkup-band-label', 'Business Health: '));
    bands.appendChild(bhBand);
    bands.appendChild(el('span', 'checkup-band-label', 'WhatsApp Readiness: '));
    bands.appendChild(waBand);
    panel.appendChild(bands);

    panel.appendChild(el('p', 'checkup-explanation', EXPLANATIONS[result.overall.band]));

    var findingsHeading = el('h3', 'checkup-subheading', 'What stood out');
    panel.appendChild(findingsHeading);
    var findings = el('ul', 'checkup-findings');
    findings.id = 'checkup-findings';
    result.findings.forEach(function (finding) {
      var item = el('li', 'checkup-finding checkup-finding-' + finding.tone, finding.text);
      findings.appendChild(item);
    });
    panel.appendChild(findings);

    if (result.priority) {
      var priority = el('div', 'checkup-priority');
      priority.id = 'checkup-priority';
      priority.setAttribute('data-checkup-priority', result.priority.id);
      priority.appendChild(el('h3', 'checkup-subheading', 'The first thing I would fix'));
      priority.appendChild(el('p', 'checkup-priority-label', result.priority.optionLabel));
      panel.appendChild(priority);
    }

    if (result.statedPriority) {
      var stated = el('div', 'checkup-stated-priority');
      stated.id = 'checkup-stated-priority';
      stated.appendChild(el('h3', 'checkup-subheading', 'Your stated priority'));
      stated.appendChild(el('p', 'checkup-stated-priority-label', result.statedPriority.label));
      panel.appendChild(stated);
    }

    var slug = result.recommendedSlug;
    if (slug) {
      var action = el('div', 'checkup-next-action');
      action.id = 'checkup-next-action';
      action.appendChild(el('h3', 'checkup-subheading', 'Recommended next step'));
      if (NEXT_ACTIONS[slug]) action.appendChild(el('p', 'checkup-next-action-copy', NEXT_ACTIONS[slug]));
      if (SERVICE_OFFERS[slug]) {
        var offer = el('p', 'checkup-offer', SERVICE_OFFERS[slug]);
        offer.id = 'checkup-offer';
        action.appendChild(offer);
      }
      panel.appendChild(action);
    }

    var actions = el('div', 'checkup-actions checkup-actions-result');

    var contact = el('a', 'checkup-btn checkup-btn-primary', 'Tell me about this problem');
    contact.id = 'checkup-primary-cta';
    contact.href = buildContactUrl({
      base: contactBase,
      slug: slug,
      utmSource: attribution.source,
      utmCampaign: attribution.campaign
    });
    actions.appendChild(contact);

    var whatsappUrl = buildWhatsAppUrl(whatsappNumber);
    if (whatsappUrl) {
      var whatsapp = el('a', 'checkup-btn checkup-btn-secondary', 'Ask on WhatsApp instead');
      whatsapp.id = 'checkup-whatsapp-cta';
      whatsapp.href = whatsappUrl;
      whatsapp.target = '_blank';
      whatsapp.rel = 'noopener noreferrer';
      actions.appendChild(whatsapp);
    }

    var restart = el('button', 'checkup-btn checkup-btn-link', 'Start again');
    restart.id = 'checkup-restart';
    restart.type = 'button';
    restart.addEventListener('click', function () {
      state.answers = {};
      state.result = null;
      state.index = 0;
      clearState();
      renderCurrent();
      announce('Checkup reset. Question 1 of ' + TOTAL_QUESTIONS + '.');
    });
    actions.appendChild(restart);

    panel.appendChild(actions);

    var scoring = document.createElement('details');
    scoring.className = 'checkup-scoring';
    scoring.id = 'checkup-scoring';
    scoring.appendChild(el('summary', 'checkup-scoring-summary', 'How this was scored'));
    var scoringList = el('ul', 'checkup-scoring-list');
    PRIORITY_ORDER.forEach(function (id) {
      var question = questionById(id);
      var answer = resolveOption(question, state.answers[id]);
      var points = answer ? answer.points : 0;
      var max = questionMaxPoints(question);
      scoringList.appendChild(
        el('li', 'checkup-scoring-item', question.id + ': ' + points + '/' + max)
      );
    });
    scoring.appendChild(scoringList);
    panel.appendChild(scoring);

    var disclaimer = el(
      'p',
      'checkup-disclaimer',
      'This checkup is a quick self-assessment based on the answers you provided. It is not a technical audit.'
    );
    disclaimer.id = 'checkup-disclaimer';
    panel.appendChild(disclaimer);

    stage.appendChild(panel);
    announce('Your result: ' + result.overall.bandLabel + '. Overall score ' + result.overall.score + ' out of ' + result.overall.max + '.');
  }

  function renderCurrent() {
    if (state.index <= 0) {
      renderIntro();
      return;
    }
    if (state.index > TOTAL_QUESTIONS) {
      renderResult();
      return;
    }
    renderQuestion();
  }

  var attribution = { source: '', campaign: '' };

  function init() {
    root = document.getElementById('checkup');
    if (!root) return;

    stage = document.getElementById('checkup-stage');
    progressWrap = document.getElementById('checkup-progress-wrap');
    progressBar = document.getElementById('checkup-progress-bar');
    progressLabel = document.getElementById('checkup-progress-label');
    live = document.getElementById('checkup-live');
    if (!stage || !progressLabel) return;

    whatsappNumber = root.getAttribute('data-whatsapp') || '';
    contactBase = root.getAttribute('data-contact-base') || '/';
    attribution = readAttribution(window.location.search);

    // Restore an in-progress run (same tab only). Storage errors never break the UI.
    restoreState();

    renderCurrent();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
