---
type: HANDOVER
project: IMAD Consulting — Lead Capture & Content Automation
title: HO-046 — Field-name parity audit: Contact vs Discovery forms (all shared fields match)
date: 2026-09-22
from: MiMo-V2.6-Pro (Architect)
to: Claude[Sonnet] Web (Reviewer) / ChatGPT (Co-reviewer)
status: COMPLETE — audit clean, no mismatches found
priority: NORMAL
---

## 1. Why this audit

Two field-mismatch bugs were found in this project (honeypot `website` vs
`malachy_hp` in HO-026; nonce `malachy_nonce` vs `discovery_nonce` in
HO-041). Both were cases of shared code validated against only one caller.
This audit checks every shared field name between the two forms that use
the same handler (`malachy_process_contact`).

## 2. Fields compared

### Discovery form (section-hero.php)

| Field | `name=` | Type | Required |
|---|---|---|---|
| Nonce | `malachy_nonce` | hidden | yes |
| Action | `action` = `malachy_send_contact` | hidden | yes |
| Honeypot | `malachy_hp` | text (off-screen) | no |
| Name | `malachy_name` | text | yes |
| Email | `malachy_email` | email | yes |
| Message | `malachy_message` | textarea | yes |

### Contact form (section-contact.php)

| Field | `name=` | Type | Required |
|---|---|---|---|
| Nonce | `malachy_nonce` | hidden | yes |
| Action | `action` = `malachy_send_contact` | hidden | yes |
| Service slug | `malachy_service` | hidden | no |
| Honeypot | `website` | text (off-screen) | no |
| Name | `malachy_name` | text | yes |
| Email | `malachy_email` | email | yes |
| Message | `malachy_message` | textarea | yes |
| Source campaign | `source_campaign` | hidden | no |

### Handler (contact-handler.php)

```php
$nonce = $data['malachy_nonce'] ?? ( $data['discovery_nonce'] ?? '' );
// → accepts either nonce field name (covers cached markup)

if ( ! empty( $data['website'] ) || ! empty( $data['malachy_hp'] ) ) {
    // → accepts either honeypot field name (covers both forms)
}

$name    = isset( $data['malachy_name'] ) ? ... : '';
$email   = isset( $data['malachy_email'] ) ? ... : '';
$message = isset( $data['malachy_message'] ) ? ... : '';
$service = isset( $data['malachy_service'] ) ? ... : '';
// → malachy_service is optional (Discovery form doesn't send it)
```

## 3. Parity result

| Handler field | Contact form | Discovery form | Match? | Notes |
|---|---|---|---|---|
| `malachy_nonce` | `malachy_nonce` | `malachy_nonce` | ✅ | Both use `malachy_nonce` after HO-041 fix |
| Honeypot | `website` | `malachy_hp` | ✅ | Handler accepts either |
| `malachy_name` | `malachy_name` | `malachy_name` | ✅ | Identical |
| `malachy_email` | `malachy_email` | `malachy_email` | ✅ | Identical |
| `malachy_message` | `malachy_message` | `malachy_message` | ✅ | Identical |
| `malachy_service` | `malachy_service` | (not present) | ✅ | Optional — defaults to empty string |
| `source_campaign` | `source_campaign` | (not present) | ✅ | Used by JS (backend), not by handler |
| `action` | `malachy_send_contact` | `malachy_send_contact` | ✅ | Identical |

**Result: ALL SHARED FIELDS MATCH.** No mismatches found.

## 4. Backend `LeadIn` model mapping (for completeness)

The JS payload sent to the imad-automation backend maps as follows:

| `LeadIn` field | Source field | Form |
|---|---|---|
| `name` | `malachy_name` | Both |
| `contact` | `malachy_email` | Both |
| `problem_text` | `malachy_message` | Both |
| `website` (honeypot) | `website` or `malachy_hp` | Contact or Discovery |
| `source_campaign` | `source_campaign` | Contact only (null if absent) |
| `idempotency_key` | Generated per page load | Both |

✅ Matches `LeadIn` schema in `app/models.py`.

## 5. Deviations from the plan

None. The audit was performed exactly as scoped.

## 6. Remaining items (unchanged from HO-045)

1. `f1eca1f` production deploy + honeypot re-test — blocked on Malachy
2. `wp_mail()` delivery confirmation — blocked on Malachy
3. `wordpress_pass` / `root_pass` rotation
4. Test data cleanup
5. SEO Phase 0
