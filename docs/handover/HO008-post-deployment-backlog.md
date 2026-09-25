# HO-008: Post-Deployment Backlog — Non-Blocking Follow-Ups

**Date:** 2026-07-21
**Status:** Open — non-blocking, no urgency
**Project:** Malachy — QA Engineer Portfolio
**Theme Root:** `/home/zubbyik/wordpress_project/malachy-portfolio/`
**Depends on:** HO-006 (final handover), HO-007 (pre-production verification — accepted, cleared for deployment)

---

## 1. Purpose

Two small items surfaced during HO-007 sign-off review that don't block production deployment to InMotion, but are worth tracking so they don't quietly get forgotten. Neither is a regression from any confirmed fix in HO-002–HO-007 — both are pre-existing content/ordering gaps, spotted while verifying the actual fixes.

---

## 2. Item 1 — Testimonials Static Reply Is Stale (Content Gap, Not a Bug)

### Observed

Tier 0 pattern #13 (Reviews/testimonials) in `get_static_answer()` hardcodes exactly 4 client quotes:

```php
return "Here's what clients have said about working with me:\n\n**Mkenny Properties (Manchester):** ...\n\n**John T (Birmingham):** ...\n\n**Nicholas R (London):** ...\n\n**Mimi R (Zagreb):** ...\n\nWant to see more or discuss a project?";
```

HO-006 §5 confirms 6 testimonial CPT entries now exist — **Lee J (London, GB)** and **Greg W (Birmingham, GB)** are missing from this static string.

### Impact

Low. Tier 1 knowledge retrieval (HO-003) can still surface Lee J or Greg W individually if a visitor asks about them by name or platform, since Tier 1 scores against the live `testimonial` CPT data, not this hardcoded string. Only the "show me your reviews" catch-all under-represents the real count.

### Fix options (either is acceptable, no urgency)

**Option A — quick, one-off:** update the static string to include all 6 entries.

**Option B — durable, avoids future staleness:** replace the hardcoded Tier 0 reply with a dynamic pull from the same `testimonial` CPT data Tier 1 already reads via `get_structured_context()`, so this pattern never drifts out of sync with the CPT again as more testimonials are added.

```php
// Option B sketch — in get_static_answer(), pattern #13:
if ( preg_match( '/(review|testimonial|feedback|client.*say|what.*client|recommendation)/i', $lower ) ) {
    $context = $this->get_structured_context();
    $testimonials = $context['testimonials'] ?? array();
    if ( empty( $testimonials ) ) {
        return ''; // fall through to Tier 1/2 if no testimonials exist yet
    }

    $lines = array_map( function( $t ) {
        $org = ! empty( $t['org'] ) ? ' (' . $t['org'] . ')' : '';
        return sprintf( "**%s%s:** \"%s\"", $t['title'], $org, $t['content'] );
    }, $testimonials );

    return "Here's what clients have said about working with me:\n\n"
        . implode( "\n\n", $lines )
        . "\n\nWant to see more or discuss a project?";
}
```

Note: reuses the single-`\n` list-join convention already established in HO-004 §4 to avoid reintroducing that markdown-rendering bug.

### Priority

Low — cosmetic content gap, not a functional defect. Good candidate to fix whenever the testimonials CPT is next touched (e.g. when adding a 7th client), rather than as a standalone task.

---

## 3. Item 2 — Pattern-Ordering Risk: "what can you" (Skills) May Shadow Projects/Experience Questions

### Observed

Tier 0 pattern #3 (Skills) matches on the broad phrase `what can you` (among others), and is checked **before** pattern #6 (Projects) and pattern #7 (Experience) in `get_static_answer()`'s sequential `if` chain.

A message like *"what can you tell me about your projects"* contains the literal phrase `"what can you"` and will match pattern #3 (Skills) first, returning the skills overview — never reaching pattern #6's projects answer — purely due to pattern check order, not because Skills is actually the better answer.

### Why this matters

This is the same class of bug HO-004 fixed for the bare `"email"` keyword colliding across the contact and email-deliverability patterns (§3 of that handover) — a broad keyword/phrase in an earlier-checked pattern silently intercepting a more specific, later-checked pattern's intended match. That fix was reactive (found via a real failing transcript); this one is proactive (found by inspection, not yet confirmed as an actual failure in the wild).

### Status

**Not yet confirmed as a live failure** — this has not been reproduced against the actual endpoint the way the email/pricing bugs were. Flagging it here so it's tracked rather than acted on prematurely.

### Recommended verification (before deciding whether a fix is needed at all)

```bash
curl -sS -X POST https://imadconsult.zubbystudio.site/wp-json/malachy/v1/chat \
  -H "Content-Type: application/json" \
  -d '{"message":"what can you tell me about your projects","history":[]}'
```

If this returns the Skills overview (`source: "static"`, skills content) instead of the Projects answer, the collision is real and worth tightening — e.g. narrowing pattern #3's `what can you` to require it isn't immediately followed by `projects`/`built`/`experience`-type words, or simply reordering so Projects/Experience are checked before the broader Skills pattern.

If it returns the Projects answer or falls through to Tier 1, this item can be closed as a false alarm — regex evaluation order matters here and the phrase overlap alone doesn't guarantee a collision without a live check.

### Ongoing monitoring alternative

Rather than fixing preemptively, this is also exactly the kind of thing the Tier 1 near-miss log (HO-003 §6) is designed to catch over time — if `source: "static"` (Skills) responses start appearing disproportionately often relative to genuine skills questions, that's a signal this collision is real in practice and worth prioritizing.

### Priority

Low — unconfirmed, no reported user-facing failure yet. Worth a single curl check next time the theme is touched; not worth interrupting current work for.

---

## 4. Summary

| Item | Type | Confirmed live issue? | Blocking? | Suggested trigger to act |
|---|---|---|---|---|
| Testimonials static reply missing 2 of 6 clients | Content staleness | Yes (confirmed via HO-006 CPT count vs. hardcoded string) | No | Next time testimonials CPT is edited |
| "what can you" (Skills) may shadow Projects/Experience | Pattern-ordering risk | No — unconfirmed, inspection-only | No | Quick curl check, or watch near-miss log for a pattern |

Neither item changes the HO-007 sign-off — production deployment to InMotion remains cleared per that handover. This is a backlog note, not a hotfix requirement.

---

*Logged so these don't quietly drop, per the same "don't let a known gap disappear without a paper trail" discipline already established across HO-003 through HO-007.*
