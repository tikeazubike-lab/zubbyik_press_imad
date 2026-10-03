/**
 * Unit tests for the Business Checkup scoring contract.
 *
 * Spec: HO-063 §6 (scoring), §7 (priority), §8 (result), §20 (validation), §22 (required tests).
 * Zero dependencies — node:test + node:assert are built into Node.
 *
 * Run: node --test tests/unit/
 */
'use strict';

const test = require('node:test');
const assert = require('node:assert/strict');
const path = require('path');

const MODULE_PATH = path.resolve(
  __dirname,
  '../../malachy-portfolio/assets/js/business-checkup.js'
);
const checkup = require(MODULE_PATH);

const {
  QUESTIONS,
  SERVICE_SLUGS,
  scoreCheckup,
  validateAnswers,
  buildContactUrl,
  buildWhatsAppUrl,
} = checkup;

// ---------------------------------------------------------------------------
// Fixtures
// ---------------------------------------------------------------------------

// Every required question at its lowest scoring answer, Q8 answered.
const FIXTURE_FLOOR = {
  stage: 'solo',
  conversations: 'not_tracked', // 0 / 3
  presence: 'unsure', // 0 / 3
  email_identity: 'personal_only', // 0 / 3
  wa_profile: 'not_set_up', // 0 / 2
  wa_number: 'no_clear', // 0 / 2
  wa_response: 'no_process', // 0 / 2
  priority: 'emails_spam',
};

// Every required question at its highest scoring answer, Q8 skipped.
const FIXTURE_CEILING = {
  stage: 'growing',
  conversations: 'always', // 3 / 3
  presence: 'current', // 3 / 3
  email_identity: 'own_domain', // 3 / 3
  wa_profile: 'business', // 2 / 2
  wa_number: 'consistent', // 2 / 2
  wa_response: 'clear_process', // 2 / 2
};

// A fully predetermined mixed fixture — the "complete fixture" HO-063 §22 requires.
// BH = 1 + 0 + 1 = 2 (At risk)   WA = 0 + 0 + 0 = 0 (At risk)   overall = 2 (Needs attention now)
// Points lost: conversations 2, presence 3, email_identity 2, wa_profile 2, wa_number 2, wa_response 2
// Highest shortfall = presence (3) -> priority presence -> CTA slug wp-rebuild
// Stated priority (Q8 emails_spam) -> CTA slug fix-spam, shown separately, never replacing the computed one
const FIXTURE_MIXED = {
  stage: 'small',
  conversations: 'scattered', // 1 / 3
  presence: 'unsure', // 0 / 3
  email_identity: 'mixed', // 1 / 3
  wa_profile: 'not_set_up', // 0 / 2
  wa_number: 'no_clear', // 0 / 2
  wa_response: 'no_process', // 0 / 2
  priority: 'emails_spam',
};

function optionValue(questionId, points) {
  const q = QUESTIONS.find((item) => item.id === questionId);
  assert.ok(q, `question ${questionId} exists`);
  const option = q.options.find((o) => o.points === points);
  assert.ok(option, `question ${questionId} has an option worth ${points}`);
  return option.value;
}

function answersWith(map) {
  return Object.assign({ stage: 'small' }, map);
}

// ---------------------------------------------------------------------------
// Question set (HO-063 §5)
// ---------------------------------------------------------------------------

test('question set: eight questions with the specified stable IDs', () => {
  assert.equal(QUESTIONS.length, 8);
  assert.deepEqual(
    QUESTIONS.map((q) => q.id),
    [
      'stage',
      'conversations',
      'presence',
      'email_identity',
      'wa_profile',
      'wa_number',
      'wa_response',
      'priority',
    ]
  );
});

test('question set: Q1 and Q8 are unscored, Q2-Q7 are scored with the specified maxima', () => {
  const maxOf = (id) => QUESTIONS.find((q) => q.id === id).maxPoints;

  assert.equal(QUESTIONS.find((q) => q.id === 'stage').scored, false);
  assert.equal(QUESTIONS.find((q) => q.id === 'priority').scored, false);

  assert.equal(maxOf('conversations'), 3);
  assert.equal(maxOf('presence'), 3);
  assert.equal(maxOf('email_identity'), 3);
  assert.equal(maxOf('wa_profile'), 2);
  assert.equal(maxOf('wa_number'), 2);
  assert.equal(maxOf('wa_response'), 2);
});

test('question set: Q1-Q7 required, Q8 optional', () => {
  QUESTIONS.forEach((q) => {
    assert.equal(Boolean(q.required), q.id !== 'priority', `required flag for ${q.id}`);
  });
});

test('question set: declared maxPoints matches the highest-scoring option', () => {
  QUESTIONS.forEach((q) => {
    const derived = q.options.reduce((max, option) => Math.max(max, option.points), 0);
    assert.equal(q.maxPoints, derived, `maxPoints for ${q.id}`);
  });
});

test('question set: dimension assignment matches HO-063 §5', () => {
  const dim = (id) => QUESTIONS.find((q) => q.id === id).dimension;
  assert.equal(dim('conversations'), 'business_health');
  assert.equal(dim('presence'), 'business_health');
  assert.equal(dim('email_identity'), 'business_health');
  assert.equal(dim('wa_profile'), 'whatsapp');
  assert.equal(dim('wa_number'), 'whatsapp');
  assert.equal(dim('wa_response'), 'whatsapp');
});

// ---------------------------------------------------------------------------
// Per-answer scoring (HO-063 §5 tables)
// ---------------------------------------------------------------------------

test('scoring: each answer contributes exactly the specified points', () => {
  const expected = {
    conversations: [
      ['always', 3],
      ['mostly', 2],
      ['scattered', 1],
      ['not_tracked', 0],
    ],
    presence: [
      ['current', 3],
      ['outdated', 1],
      ['social_only', 1],
      ['unsure', 0],
    ],
    email_identity: [
      ['own_domain', 3],
      ['mostly_professional', 2],
      ['mixed', 1],
      ['personal_only', 0],
    ],
    wa_profile: [
      ['business', 2],
      ['used_not_business', 1],
      ['not_set_up', 0],
    ],
    wa_number: [
      ['consistent', 2],
      ['inconsistent', 1],
      ['no_clear', 0],
    ],
    wa_response: [
      ['clear_process', 2],
      ['inconsistent', 1],
      ['no_process', 0],
    ],
  };

  Object.keys(expected).forEach((questionId) => {
    expected[questionId].forEach(([value, points]) => {
      const declaration = QUESTIONS.find((q) => q.id === questionId).options.find(
        (o) => o.value === value
      );
      assert.ok(declaration, `${questionId}.${value} is declared`);
      assert.equal(declaration.points, points, `${questionId}.${value}`);
    });
  });
});

test('scoring: minimum valid answers produce 0/9, 0/6, 0/15', () => {
  const result = scoreCheckup(FIXTURE_FLOOR);
  assert.equal(result.businessHealth.score, 0);
  assert.equal(result.businessHealth.max, 9);
  assert.equal(result.whatsapp.score, 0);
  assert.equal(result.whatsapp.max, 6);
  assert.equal(result.overall.score, 0);
  assert.equal(result.overall.max, 15);
});

test('scoring: maximum valid answers produce 9/9, 6/6, 15/15', () => {
  const result = scoreCheckup(FIXTURE_CEILING);
  assert.equal(result.businessHealth.score, 9);
  assert.equal(result.whatsapp.score, 6);
  assert.equal(result.overall.score, 15);
});

// ---------------------------------------------------------------------------
// Band boundaries (HO-063 §6)
// ---------------------------------------------------------------------------

test('bands: Business Health boundaries 0-3 / 4-6 / 7-9', () => {
  const cases = [
    [0, 'at_risk'],
    [3, 'at_risk'],
    [4, 'partly_covered'],
    [6, 'partly_covered'],
    [7, 'solid'],
    [9, 'solid'],
  ];

  cases.forEach(([target, band]) => {
    // Build a set of BH answers totalling exactly `target` using Q2 (worth 3) as the coarse dial.
    const answers = answersWith({
      conversations: optionValue('conversations', Math.min(3, target)),
      presence: optionValue('presence', Math.max(0, Math.min(3, target - 3))),
      email_identity: optionValue('email_identity', Math.max(0, target - 6)),
    });
    const result = scoreCheckup(answers);
    assert.equal(result.businessHealth.score, target, `fixture for ${target}`);
    assert.equal(result.businessHealth.band, band, `Business Health ${target}`);
  });
});

test('bands: WhatsApp Readiness boundaries 0-2 / 3-4 / 5-6', () => {
  const cases = [
    [0, 'at_risk'],
    [2, 'at_risk'],
    [3, 'partly_covered'],
    [4, 'partly_covered'],
    [5, 'solid'],
    [6, 'solid'],
  ];

  cases.forEach(([target, band]) => {
    const answers = answersWith({
      wa_profile: optionValue('wa_profile', Math.min(2, target)),
      wa_number: optionValue('wa_number', Math.max(0, Math.min(2, target - 2))),
      wa_response: optionValue('wa_response', Math.max(0, Math.min(2, target - 4))),
    });
    const result = scoreCheckup(answers);
    assert.equal(result.whatsapp.score, target, `fixture for ${target}`);
    assert.equal(result.whatsapp.band, band, `WhatsApp Readiness ${target}`);
  });
});

test('bands: Overall boundaries 0-5 / 6-10 / 11-15', () => {
  // Explicit point allocations, because not every question offers every value (presence has
  // no 2-point option). Each row totals the target exactly.
  const allocations = [
    [0, { conversations: 0, presence: 0, email_identity: 0, wa_profile: 0, wa_number: 0, wa_response: 0 }, 'needs_attention'],
    [5, { conversations: 0, presence: 3, email_identity: 0, wa_profile: 0, wa_number: 2, wa_response: 0 }, 'needs_attention'],
    [6, { conversations: 0, presence: 3, email_identity: 1, wa_profile: 0, wa_number: 2, wa_response: 0 }, 'some_gaps'],
    [10, { conversations: 3, presence: 3, email_identity: 2, wa_profile: 0, wa_number: 2, wa_response: 0 }, 'some_gaps'],
    [11, { conversations: 3, presence: 3, email_identity: 3, wa_profile: 0, wa_number: 2, wa_response: 0 }, 'in_good_shape'],
    [15, { conversations: 3, presence: 3, email_identity: 3, wa_profile: 2, wa_number: 2, wa_response: 2 }, 'in_good_shape'],
  ];

  allocations.forEach(([target, points, band]) => {
    const answers = answersWith({});
    let total = 0;
    Object.keys(points).forEach((id) => {
      const award = points[id];
      total += award;
      answers[id] = optionValue(id, award);
    });
    assert.equal(total, target, `fixture for ${target} totals exactly`);

    const result = scoreCheckup(answers);
    assert.equal(result.overall.score, target, `fixture for ${target}`);
    assert.equal(result.overall.band, band, `overall ${target}`);
  });
});

// ---------------------------------------------------------------------------
// Priority + ties (HO-063 §7)
// ---------------------------------------------------------------------------

test('priority: the largest shortfall wins (FIXTURE_MIXED -> presence)', () => {
  const result = scoreCheckup(FIXTURE_MIXED);
  assert.equal(result.priority.id, 'presence');
  assert.equal(result.priority.pointsLost, 3);
});

test('priority: deterministic tie-break follows conversations -> presence -> email_identity -> wa_*', () => {
  // conversations and presence both short by 3 -> conversations wins (earlier in the order).
  const tieFirst = scoreCheckup(
    answersWith({
      conversations: 'not_tracked',
      presence: 'unsure',
      email_identity: 'own_domain',
      wa_profile: 'business',
      wa_number: 'consistent',
      wa_response: 'clear_process',
    })
  );
  assert.equal(tieFirst.priority.id, 'conversations');

  // WhatsApp questions all short by 2 while presence is short by 3 -> presence still wins.
  const businessWins = scoreCheckup(
    answersWith({
      conversations: 'always',
      presence: 'unsure',
      email_identity: 'own_domain',
      wa_profile: 'not_set_up',
      wa_number: 'no_clear',
      wa_response: 'no_process',
    })
  );
  assert.equal(businessWins.priority.id, 'presence');
});

test('priority: equal dimension percentages resolve to Business Health', () => {
  const result = scoreCheckup(FIXTURE_FLOOR);
  assert.equal(result.businessHealth.score / result.businessHealth.max, 0);
  assert.equal(result.whatsapp.score / result.whatsapp.max, 0);
  assert.equal(result.priority.dimension, 'business_health');
});

test('priority: a perfect run has no priority at all', () => {
  const result = scoreCheckup(FIXTURE_CEILING);
  assert.equal(result.priority, null);
});

test('priority: an unscored Q1 or Q8 can never become the priority', () => {
  ['stage', 'priority'].forEach((id) => {
    assert.equal(
      QUESTIONS.find((q) => q.id === id).scored,
      false,
      `${id} must not be scored`
    );
  });
  const result = scoreCheckup(FIXTURE_FLOOR);
  assert.notEqual(result.priority.id, 'stage');
  assert.notEqual(result.priority.id, 'priority');
});

// ---------------------------------------------------------------------------
// Q8 routing (HO-063 §5, §7)
// ---------------------------------------------------------------------------

test('Q8: does not change any numeric score', () => {
  const withoutPriority = scoreCheckup(
    Object.assign({}, FIXTURE_MIXED, { priority: undefined })
  );
  const withPriority = scoreCheckup(FIXTURE_MIXED);

  assert.equal(withoutPriority.overall.score, withPriority.overall.score);
  assert.equal(withoutPriority.businessHealth.score, withPriority.businessHealth.score);
  assert.equal(withoutPriority.whatsapp.score, withPriority.whatsapp.score);
  assert.equal(withoutPriority.overall.band, withPriority.overall.band);
});

test('Q8: each option routes to the correct existing service slug', () => {
  const expected = {
    emails_spam: 'fix-spam',
    website_outdated: 'wp-rebuild',
    email_not_domain: 'm365-setup',
    check_everything: 'email-audit',
  };

  Object.keys(expected).forEach((option) => {
    const result = scoreCheckup(Object.assign({}, FIXTURE_MIXED, { priority: option }));
    assert.equal(result.statedPriority.value, option);
    assert.equal(result.statedPriority.slug, expected[option]);
    assert.ok(
      SERVICE_SLUGS.indexOf(expected[option]) !== -1,
      `${expected[option]} is an existing slug`
    );
  });
});

test('Q8: stated priority never replaces the computed priority', () => {
  const result = scoreCheckup(FIXTURE_MIXED);
  assert.equal(result.priority.id, 'presence'); // computed
  assert.equal(result.statedPriority.value, 'emails_spam'); // stated
  assert.equal(result.priority.slug, 'wp-rebuild');
  assert.equal(result.statedPriority.slug, 'fix-spam');
  assert.equal(result.recommendedSlug, 'fix-spam'); // stated priority drives the CTA
});

test('Q8: omitted -> CTA falls back to the computed priority service', () => {
  const result = scoreCheckup(
    Object.assign({}, FIXTURE_MIXED, { priority: undefined })
  );
  assert.equal(result.statedPriority, null);
  assert.equal(result.recommendedSlug, 'wp-rebuild');
});

test('Q8: every routed slug is one of the nine existing service slugs', () => {
  assert.equal(SERVICE_SLUGS.length, 9);
  const result = scoreCheckup(Object.assign({}, FIXTURE_MIXED, { priority: 'check_everything' }));
  result.statedPriority.slug.split(',').forEach((slug) => {
    assert.ok(SERVICE_SLUGS.indexOf(slug) !== -1, `${slug} exists`);
  });
});

// ---------------------------------------------------------------------------
// Findings (HO-063 §8: 3-5 key findings)
// ---------------------------------------------------------------------------

test('findings: between three and five, never more', () => {
  [FIXTURE_FLOOR, FIXTURE_MIXED, FIXTURE_CEILING].forEach((fixture) => {
    const result = scoreCheckup(fixture);
    assert.ok(result.findings.length >= 3, 'at least three findings');
    assert.ok(result.findings.length <= 5, 'at most five findings');
  });
});

test('findings: gaps are ordered by size of shortfall, then by question order', () => {
  const result = scoreCheckup(FIXTURE_MIXED);
  const gaps = result.findings.filter((f) => f.tone === 'gap');
  assert.equal(gaps.length, 5); // capped at five of the six gaps
  for (let i = 1; i < gaps.length; i += 1) {
    assert.ok(
      gaps[i - 1].pointsLost >= gaps[i].pointsLost,
      'gaps are ordered largest shortfall first'
    );
  }
  assert.equal(gaps[0].pointsLost, 3, 'the largest shortfall leads');
});

test('findings: every finding is derived from the visitor answers', () => {
  const result = scoreCheckup(FIXTURE_MIXED);
  result.findings.forEach((finding) => {
    assert.ok(
      QUESTIONS.some((q) => q.id === finding.id),
      `${finding.id} maps to a real question`
    );
    assert.ok(typeof finding.text === 'string' && finding.text.length > 0);
  });
});

// ---------------------------------------------------------------------------
// Validation (HO-063 §20)
// ---------------------------------------------------------------------------

test('validation: Q1-Q7 missing is reported, Q8 may be absent', () => {
  const incomplete = validateAnswers({ stage: 'solo' });
  assert.equal(incomplete.valid, false);
  assert.deepEqual(incomplete.missing, [
    'conversations',
    'presence',
    'email_identity',
    'wa_profile',
    'wa_number',
    'wa_response',
  ]);

  const complete = validateAnswers(FIXTURE_CEILING);
  assert.equal(complete.valid, true);
  assert.deepEqual(complete.missing, []);
});

test('validation: an unrecognised answer token is treated as missing', () => {
  const answers = Object.assign({}, FIXTURE_CEILING, { presence: 'nonsense' });
  const outcome = validateAnswers(answers);
  assert.equal(outcome.valid, false);
  assert.deepEqual(outcome.missing, ['presence']);
});

test('validation: scoring tolerates invalid input and reports it', () => {
  const result = scoreCheckup(Object.assign({}, FIXTURE_CEILING, { presence: 'nonsense' }));
  assert.deepEqual(result.invalid, ['presence']);
  assert.equal(result.businessHealth.score, 6); // presence contributes 0
});

test('scoring: does not mutate the caller answers object', () => {
  const answers = Object.assign({}, FIXTURE_MIXED);
  const snapshot = JSON.stringify(answers);
  scoreCheckup(answers);
  assert.equal(JSON.stringify(answers), snapshot);
});

// ---------------------------------------------------------------------------
// CTA construction (HO-063 §9, §10, §24)
// ---------------------------------------------------------------------------

test('CTA: without UTM parameters', () => {
  assert.equal(buildContactUrl({ base: '/', slug: 'fix-spam' }), '/?service=fix-spam#contact');
});

test('CTA: with UTM parameters, exactly as HO-063 §10 specifies', () => {
  assert.equal(
    buildContactUrl({
      base: '/',
      slug: 'fix-spam',
      utmSource: 'status',
      utmCampaign: 'ep001',
    }),
    '/?service=fix-spam&utm_source=status&utm_campaign=ep001#contact'
  );
});

test('CTA: absent parameters are omitted, never invented', () => {
  const onlySource = buildContactUrl({ base: '/', slug: 'm365-setup', utmSource: 'status' });
  assert.ok(!/utm_campaign=/.test(onlySource));
  assert.equal(onlySource, '/?service=m365-setup&utm_source=status#contact');

  const none = buildContactUrl({ base: '/', slug: 'm365-setup' });
  assert.ok(!/utm_/.test(none));
});

test('CTA: values are URL-encoded', () => {
  const url = buildContactUrl({
    base: '/',
    slug: 'fix-spam',
    utmSource: 'a b&c',
    utmCampaign: 'x=y',
  });
  assert.ok(url.indexOf('utm_source=a%20b%26c') !== -1, `encoded source in ${url}`);
  assert.ok(url.indexOf('utm_campaign=x%3Dy') !== -1, `encoded campaign in ${url}`);
});

test('CTA: a slug outside the fixed list is refused, not passed through', () => {
  ['javascript:alert(1)', 'https://evil.example', 'not-a-slug', ''].forEach((slug) => {
    const url = buildContactUrl({ base: '/', slug: slug, utmSource: 'status' });
    assert.ok(url.indexOf('service=') === -1, `no service parameter for ${slug}`);
    assert.ok(url.indexOf('evil.example') === -1);
    assert.equal(url, '/?utm_source=status#contact');
  });
});

test('WhatsApp CTA: digits extracted, junk stripped, nothing invented', () => {
  assert.equal(buildWhatsAppUrl('2348164162816'), 'https://wa.me/2348164162816');
  assert.equal(buildWhatsAppUrl('+234 816-416-2816'), 'https://wa.me/2348164162816');
  assert.equal(buildWhatsAppUrl('javascript:alert(1)'), '');
  assert.equal(buildWhatsAppUrl(''), '');
  assert.equal(buildWhatsAppUrl(undefined), '');
});
