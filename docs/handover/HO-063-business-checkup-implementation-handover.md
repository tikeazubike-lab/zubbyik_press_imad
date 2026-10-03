# HO-063 — Business Checkup Implementation Handover

**Status:** IMPLEMENTATION READY  
**Audience:** On-ground coding agent  
**Source:** HO-061 specification + HO-062 specification response  
**Scope:** Implement the approved Business Checkup MVP only. Do not expand scope.

## 1. Mission

Implement the Business Checkup MVP on the existing IMAD Consulting Studio WordPress site.

Add a standalone:

`/business-checkup/`

The MVP is a client-side deterministic self-assessment with:
- 8 questions
- 6 scored questions
- Business Health dimension
- WhatsApp Readiness dimension
- deterministic scoring
- result shown before contact details
- existing contact form used for lead handoff
- UTM source/campaign preserved
- no new backend endpoint
- no database/schema change
- no AI
- no website/email server-side checks
- no analytics
- no new notification path

Do not modify unrelated existing funnel behavior.

## 2. Non-Negotiable Constraints

### DO NOT CHANGE

These existing behaviors are outside scope and must remain unchanged:
- `/r/{source}`
- campaign redirect behavior
- existing homepage UTM hero CTA behavior
- `contact.js`
- `inc/contact-handler.php`
- `section-hero.php`
- `section-contact.php`
- existing GSAP/ScrollTrigger animation system
- FastAPI lead endpoint
- FastAPI database code
- notification code
- scheduler
- existing database schema
- migrations
- `.htaccess`
- Docker/compose configuration

Do not introduce:
- another lead API
- another notification mechanism
- another database table
- another public write endpoint
- another AI provider
- another analytics system
- n8n
- a frontend framework
- a build system
- npm dependencies

Stay consistent with the existing classic WordPress/PHP + plain JavaScript architecture.

## 3. Approved Product Definition

**Public name:** Business Checkup

**URL:** `/business-checkup/`

Use the existing WordPress custom-template mechanism. Reconnaissance established that ordinary pages inherit blog styling, so use a dedicated custom page template.

## 4. Visitor Journey

```text
Business Checkup landing
        |
        v
Intro
"8 quick questions · about 2 minutes · no signup"
        |
        v
Question 1 → Question 2 → ... → Question 8
        |
        v
Deterministic result
        |
        +----> Primary CTA: existing contact flow
        +----> Secondary CTA: existing WhatsApp contact
        +----> Start again
```

Requirements:
- One question visible at a time.
- User can move backward.
- Required questions must be answered before advancing.
- Q8 is optional.
- Answers survive Back navigation.
- Result is calculated locally.
- No answers are sent to the server.
- No signup/contact details before result.

SessionStorage is optional for MVP. If implemented, keep it small, namespaced, corruption-tolerant, and never allow storage errors to break the checkup.

## 5. Question Specification

Use these stable IDs.

### Q1 — Business stage

ID: `stage`

Purpose: context only. **Not scored.**

Ask for business stage/size using the approved options from HO-062. Do not collect free-form personal information.

### Q2 — Customer conversations

ID: `conversations`  
Dimension: `business_health`

Scores:

| Answer condition | Score |
|---|---:|
| Strong/centralized recording of customer conversations | 3 |
| Some conversations are recorded | 2 |
| Conversations are handled but inconsistently recorded | 1 |
| No central record | 0 |

### Q3 — Online presence

ID: `presence`  
Dimension: `business_health`

Scores:

| Answer condition | Score |
|---|---:|
| Searchers can quickly find a clear, current business presence | 3 |
| Presence exists but is incomplete/outdated | 1 |
| Presence is weak/inconsistent | 1 |
| Little/no useful presence | 0 |

Preserve these approved scoring values; do not normalize them.

### Q4 — Business email identity

ID: `email_identity`  
Dimension: `business_health`

Scores:

| Answer condition | Score |
|---|---:|
| Professional business email on own domain and used consistently | 3 |
| Mostly professional, with some inconsistency | 2 |
| Mix of personal/free-mail and business email | 1 |
| Primarily personal/free-mail | 0 |

### Q5 — WhatsApp profile

ID: `wa_profile`  
Dimension: `whatsapp`

Scores:

| Answer condition | Score |
|---|---:|
| WhatsApp Business | 2 |
| WhatsApp is used but not as a Business profile | 1 |
| WhatsApp is not set up for the business | 0 |

### Q6 — WhatsApp number consistency

ID: `wa_number`  
Dimension: `whatsapp`

Scores:

| Answer condition | Score |
|---|---:|
| Same business WhatsApp number is consistently advertised | 2 |
| Number is advertised inconsistently | 1 |
| No clear business WhatsApp number | 0 |

### Q7 — WhatsApp response handling

ID: `wa_response`  
Dimension: `whatsapp`

Scores:

| Answer condition | Score |
|---|---:|
| Clear response process/ownership | 2 |
| Responses are handled but inconsistently | 1 |
| No clear response process | 0 |

### Q8 — Stated priority

ID: `priority`

Purpose: optional routing context. **Not scored.**

| Priority | Existing service slug |
|---|---|
| Emails not reaching customers | `fix-spam` |
| Website is out of date / not bringing enquiries | `wp-rebuild` |
| Business email is not on own domain | `m365-setup` |
| Someone should check everything | `email-audit` |

Do not invent additional service slugs. Q8 remains optional.

## 6. Scoring Contract

Implement scoring as a pure JavaScript function, independent of DOM rendering.

Conceptual output:

```text
scoreCheckup(answers)
    -> {
         businessHealth: { score, max: 9, band },
         whatsapp:       { score, max: 6, band },
         overall:        { score, max: 15, band },
         priority
       }
```

### Business Health

Q2–Q4. Range `0–9`.

- `0–3`: **At risk**
- `4–6`: **Partly covered**
- `7–9`: **Solid**

### WhatsApp Readiness

Q5–Q7. Range `0–6`.

- `0–2`: **At risk**
- `3–4`: **Partly covered**
- `5–6`: **Solid**

### Overall

Range `0–15`.

- `0–5`: **Needs attention now**
- `6–10`: **Some gaps**
- `11–15`: **In good shape**

The result must explicitly identify itself as a **self-assessment**, not a formal audit.

## 7. Priority Determination

Default computed priority is the lowest-scoring scored question.

Use deterministic tie-breaking in this order:

```text
conversations
presence
email_identity
wa_profile
wa_number
wa_response
```

If Q8 has a stated priority, display it separately as **Your stated priority**. Do not silently replace the computed priority with Q8.

Q8 may select the relevant existing service CTA.

## 8. Result Contract

The result must contain:
1. Result title
2. Overall score
3. Business Health score
4. WhatsApp Readiness score
5. Plain-language explanation
6. 3–5 key findings
7. Priority issue
8. Recommended next action
9. Matching existing IMAD offer title
10. Primary contact CTA
11. Secondary WhatsApp CTA
12. Start-again action

Do not claim certification, benchmarks, guaranteed outcomes, a completed technical audit, actual email verification, actual website testing, actual WhatsApp inspection, or capabilities absent from the source of truth.

The result is based only on user answers.

### Starting result copy

**At risk**
> Right now, a customer trying to reach you can fall through the gap.

**Partly covered**
> The basics are there, but there are gaps a customer may notice.

**Solid**
> You're in good shape — the gaps left are small and worth closing.

Adjust only for clarity, grammar, and site voice. Do not introduce unsupported claims.

Include a clear note such as:

> This checkup is a quick self-assessment based on the answers you provided. It is not a technical audit.

## 9. Lead Handoff

Use the **existing contact flow**. Do not create a new lead endpoint.

Result CTA format:

```text
/?service=<service-slug>&utm_source=<source>&utm_campaign=<campaign>#contact
```

Omit parameters that do not exist. URL-encode values.

The existing contact flow remains responsible for nonce, honeypot, rate limiting, wp_mail fallback, FastAPI submission, Telegram notification, dead-letter handling, and idempotency.

Do not duplicate any of these mechanisms.

## 10. UTM Attribution

Example input:

```text
/business-checkup/?utm_source=status&utm_campaign=ep001
```

Expected contact CTA:

```text
/?service=fix-spam&utm_source=status&utm_campaign=ep001#contact
```

Rules:
- Preserve existing `utm_source` and `utm_campaign`.
- Do not invent or overwrite them.
- Omit absent values.
- Do not modify `/r/{source}`.

## 11. WhatsApp CTA

Reuse the existing centralized WhatsApp configuration/data source, such as `malachy_whatsapp`, if that is what the repository exposes.

Do not hardcode a number if a centralized value already exists.

Do not invent a WhatsApp service slug.

The secondary CTA may use the existing `wa.me` destination.

## 12. Navigation

Add one **Business Checkup** entry to the existing site navigation, pointing to:

`/business-checkup/`

Before modifying navigation:
1. inspect the current structure;
2. preserve its markup conventions;
3. add only one item;
4. reuse existing styling;
5. do not create a navigation component.

Do not modify the hero CTA.

Organic visitors retain:

> View My Projects

Campaign visitors retain the existing UTM-driven:

> Tell me your problem

## 13. WordPress Template

Create:

```text
malachy-portfolio/template-business-checkup.php
```

Requirements:
- follow existing template conventions;
- use WordPress-safe escaping;
- provide the normal theme page structure;
- avoid generic blog styling;
- contain the checkup markup;
- keep application logic in JS where appropriate;
- introduce no PHP dependencies.

## 14. JavaScript

Create:

```text
malachy-portfolio/assets/js/business-checkup.js
```

Use plain JavaScript. No framework, dependency, or build step.

Separate:
1. question data
2. scoring
3. state
4. validation
5. rendering
6. navigation
7. attribution/CTA construction

Avoid global namespace pollution. A small IIFE/module pattern is preferred if consistent with the repository.

## 15. CSS

Create:

```text
malachy-portfolio/assets/css/business-checkup.css
```

Keep checkup styles isolated unless repository conventions require otherwise.

Reuse existing real CSS variables/components.

Do not assume `.btn-secondary` exists; HO-062 explicitly corrected that it does not. Likewise, `.reveal` is not a styled component.

The page should look native to the existing IMAD site without importing homepage animation complexity.

## 16. Asset Loading

Preferred:
- enqueue `business-checkup.js`;
- enqueue `business-checkup.css`;
- load them only on the Business Checkup page.

Modify `functions.php` only if required.

Do not load checkup assets site-wide.

Do not load GSAP specifically for this page.

## 17. Responsive UX

Desktop:
- centered readable content width;
- clear progress;
- readable answer cards;
- strong result hierarchy;
- minimal effects.

Mobile:
- one question per screen;
- stacked tappable answers;
- minimum 44×44 targets;
- progress indicator;
- single-column result;
- accessible navigation;
- no horizontal overflow;
- sticky actions only if safely consistent with existing design, including safe-area inset.

## 18. Accessibility

Implement:
- semantic `fieldset`/`legend` where appropriate;
- real labels;
- keyboard operation;
- visible focus states;
- focus management after question changes;
- `aria-live` where useful;
- inline validation;
- sufficient contrast;
- reduced-motion compatibility;
- 44×44 minimum touch targets;
- usable at 200% zoom.

Do not use color alone to communicate score/band.

## 19. State

Minimum explicit state:

```text
currentQuestion
answers
utmSource
utmCampaign
completed
result
```

Do not store personal/contact data.

Do not send answers to the server.

If sessionStorage is used:
- namespace the key;
- store only checkup state;
- tolerate corrupted data;
- Start Again clears it;
- storage errors cannot break the UI.

## 20. Validation

Q1–Q7 are required. Q8 is optional.

Missing required answer:
- blocks progression;
- shows clear inline message;
- focuses/moves attention appropriately;
- remains keyboard accessible.

## 21. Testing — RED → GREEN

Follow the project's test-first discipline.

### RED

Before implementation is complete:
1. Write unit tests for pure scoring.
2. Write relevant Playwright tests.
3. Run against the current implementation.
4. Capture failing output.
5. Confirm failures are due to missing Business Checkup functionality, not broken test infrastructure.

Report this state.

### GREEN

Implement the feature.

Run:
- scoring unit tests;
- Playwright tests;
- relevant existing regression tests.

All required tests must pass.

Include raw command output in the implementation handover.

### Regression

Confirm:
- homepage loads;
- hero CTA behavior unchanged;
- campaign redirect unchanged;
- contact form unchanged;
- checkup assets not loaded on unrelated pages;
- no GSAP dependency on checkup;
- no console errors.

## 22. Required Automated Tests

### Scoring
Test:
- minimum valid score;
- maximum valid score;
- every Business Health boundary;
- every WhatsApp boundary;
- every overall boundary;
- each answer score;
- deterministic ties;
- Q8 does not affect numeric score;
- Q8 routing.

### Navigation
Test:
- landing;
- start;
- Q1 through Q8;
- Back;
- answer persistence after Back;
- required validation;
- skipping Q8;
- result only after required questions;
- Start Again reset.

### Result
Assert:
- overall score;
- dimension scores;
- band;
- findings;
- priority;
- CTAs;
- WhatsApp CTA.

At least one complete fixture must have a fully predetermined expected result.

### Attribution
Test UTM and no-UTM cases.

### Responsive
Use a mobile viewport. Check no horizontal overflow and usable/readable controls.

### Accessibility
At minimum test keyboard progression, focus after question transition, labels/fieldset semantics, required validation, and result accessibility.

## 23. Visual Regression

Existing visual regression harness must be used.

Create:

```text
tests/visual/business-checkup.spec.ts
```

Follow existing conventions.

Capture at least:
1. landing/intro;
2. question state;
3. result state;
4. mobile result.

Do not alter unrelated baselines. If the shared navigation baseline changes because of the new menu item, document the intentional change.

## 24. Security

Review:
- query/URL construction;
- HTML escaping;
- DOM insertion;
- service slug selection;
- sessionStorage parsing if used;
- WhatsApp URL construction.

Use fixed internal option values. Never accept arbitrary user-controlled service URLs.

Do not use `innerHTML` with untrusted query-string content.

No new server-side attack surface should be introduced.

## 25. Performance

Do not add:
- libraries;
- fonts;
- large assets;
- animation dependencies;
- external analytics;
- third-party trackers.

If JS is disabled, show a graceful message rather than broken controls.

## 26. Exact File Change Surface

### CREATE

```text
malachy-portfolio/template-business-checkup.php
malachy-portfolio/assets/js/business-checkup.js
malachy-portfolio/assets/css/business-checkup.css
tests/visual/business-checkup.spec.ts
```

Also create/update the appropriate unit-test file according to the repository's existing test structure. Do not invent a new test framework.

### MODIFY, ONLY IF REQUIRED

```text
malachy-portfolio/functions.php
malachy-portfolio/template-parts/navigation.php
```

### DO NOT MODIFY

```text
contact.js
inc/contact-handler.php
section-hero.php
section-contact.php
FastAPI application
database schema
migrations
notification system
scheduler
Docker configuration
.htaccess
GSAP/animation modules
/r/{source}
```

## 27. WordPress Page Setup

Expected page:

```text
Title: Business Checkup
Slug: business-checkup
Template: Business Checkup
```

If the agent lacks safe production access, report the exact manual step instead of claiming it was done.

## 28. Definition of Done

- [ ] Dedicated WordPress template exists.
- [ ] Eight questions implemented.
- [ ] Q1/Q8 unscored.
- [ ] Q2–Q7 scoring correct.
- [ ] Business Health scoring correct.
- [ ] WhatsApp scoring correct.
- [ ] Overall scoring correct.
- [ ] Band boundaries tested.
- [ ] Priority tie-breaking deterministic.
- [ ] Result appears before contact details.
- [ ] Result identifies itself as a self-assessment.
- [ ] All required result elements present.
- [ ] Existing contact flow reused.
- [ ] No new lead endpoint.
- [ ] UTM source/campaign survive to contact CTA.
- [ ] Existing WhatsApp destination reused.
- [ ] One navigation entry added.
- [ ] Hero behavior unchanged.
- [ ] JS/CSS page-scoped.
- [ ] No GSAP dependency added.
- [ ] Mobile layout works.
- [ ] Keyboard navigation works.
- [ ] Accessibility requirements met.
- [ ] Required validation works.
- [ ] No server/database changes.
- [ ] No third-party dependencies.
- [ ] Unit tests pass.
- [ ] Playwright tests pass.
- [ ] Visual regression passes.
- [ ] Relevant regression tests pass.
- [ ] No console errors.
- [ ] Raw test output included.
- [ ] `git diff --check` passes.
- [ ] Final diff reviewed for unintended changes.
- [ ] No deployment claimed unless actually performed.

## 29. Implementation Agent Reporting Requirements

Produce:

```text
docs/handover/HO-064-business-checkup-implementation.md
```

Report:

### A. Implementation summary
What was implemented.

### B. Exact files changed
For every file, state created/modified, reason, and important changes.

### C. Test-first evidence

Show raw output:

```text
RED
<command>
<failing output>

GREEN
<command>
<passing output>
```

Do not replace raw output with prose.

### D. Regression evidence
Include raw output for Playwright, visual tests, relevant existing tests, and `git diff --check`.

### E. Final git evidence

Include:

```bash
git status --short
git diff --stat
git diff --check
```

and relevant focused diffs.

### F. Manual steps
List anything requiring human action, such as WordPress page creation, template assignment, cache purge, or production deployment.

### G. Known limitations
Explicitly state:
- no website technical checker;
- no email/DNS checker;
- no AI report;
- no analytics;
- no server-side diagnostic storage.

### H. Acceptance checklist
Mark every Definition-of-Done item:
- PASS
- FAIL
- BLOCKED

Any FAIL/BLOCKED item must include its reason.

## 30. Git / Deployment Rules

Do not deploy unless explicitly authorized.

Do not push unrelated changes.

Before final report:

```bash
git status --short
git diff --stat
git diff --check
```

Review the actual diff.

Only commit if the project workflow explicitly permits it and tests pass.

Never claim a commit, push, or deployment that did not occur.

## 31. Product Boundaries

Do NOT implement now:

### Website Health
No server-side URL fetching or SSRF-capable checker.

### Email Deliverability
No DNS/MX/SPF/DKIM/DMARC checker.

### AI Action Plan
No AI call. Do not add another provider.

### Analytics
No tracking endpoint or third-party analytics.

### Diagnostic persistence
No JSONB column or new table.

## 32. Future Extension Contract

Future dimensions may include:

```text
Business Health
Website Health
Email / Deliverability
WhatsApp Readiness
AI Action Plan
```

Leave simple data-driven extension points, but do not build speculative abstractions.

## 33. Final Instruction to the Agent

Implement the approved MVP exactly as specified.

Start with the RED test state.

Inspect the repository before changing files.

Follow existing project conventions.

Keep the implementation small and isolated.

Do not touch unrelated systems.

Do not create a second lead funnel.

Do not change the existing campaign funnel.

Do not modify hero behavior.

Do not add AI, analytics, website checks, email checks, database changes, or new dependencies.

When complete, produce the implementation handover with raw test output and exact final diff evidence.

The success criterion is not merely that Business Checkup works. It must work **without destabilizing the existing IMAD Consulting lead funnel**.
