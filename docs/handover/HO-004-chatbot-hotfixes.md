# HO-004: AI Chatbot — Production Hotfixes (Addendum to HO-002 / HO-003)

**Date:** 2026-07-19
**Status:** Design complete — pending implementation
**Project:** Malachy — QA Engineer Portfolio
**Live URL:** https://imadconsult.zubbystudio.site
**Theme Root:** `/home/zubbyik/wordpress_project/malachy-portfolio/`
**Supersedes:** Nothing — bug fixes only, no architectural change
**Depends on:** `inc/ai-chat-bot.php` (HO-002), `assets/js/ai-chat-bot.js` (HO-002), Tier 1 retrieval layer (HO-003, design-complete, not yet confirmed shipped)
**Trigger:** Live transcript review, 2026-07-19 (Zubbyik) — see Section 5 for the exact conversation that surfaced these

---

## 1. Summary

Three independent bugs found in a single real transcript, none related to Tier 1 retrieval logic itself (HO-003's project/experience single-match replies routed correctly in the same transcript — this confirms that part is sound). All three are pre-existing Tier 0/rendering issues that predate HO-003 and were simply not visible until real traffic exercised them.

| # | Bug | Severity | Blocks HO-003? |
|---|-----|----------|----------------|
| 1 | Double-encoded HTML entities (`&#038;`) in replies | Cosmetic but visible on every experience-related answer | No |
| 2 | Tier 0 keyword collision — bare `"email"` in the hire/contact pattern intercepts email-deliverability questions | Functional — actively prevents Tier 1/Tier 2 from ever answering email-related questions | **Yes — directly blocks Tier 1/2 reachability for this topic** |
| 3 | Broken markdown list rendering — empty `<li>` between experience entries | Visible, degrades the "list all experience" static answer | No |

---

## 2. Bug 1 — Double-Encoded HTML Entities

### Observed

```
That's from my time as Senior QA Engineer &#038; Systems Consultant at IMaD Consulting...
```

`&#038;` is the literal string `&amp;` decoded once — i.e. an ampersand that has been entity-encoded twice.

### Root Cause

`get_portfolio_context()` (HO-002 §5) builds the context array from `get_the_title()` and similar WP core functions, which already run through `wptexturize()`/`convert_chars()` — this turns a literal `&` in a CPT title into `&#038;` at that point. HO-003's `compose_reply_from_match()` (§4.4) then calls `esc_html()` on that already-encoded string. `esc_html()` encodes `&` → `&amp;`, so an already-encoded `&#038;` becomes double-encoded, and because the reply is delivered as a plain JSON string field — never re-rendered as raw HTML server-side, only passed through the client-side `parseMarkdown()` (HO-002 §10) — nothing downstream ever decodes it back to a plain `&`.

### Fix

Decode once, at the point titles/descriptions enter the context array — not at output time, and remove the redundant `esc_html()` call in the reply composer (escaping belongs at exactly one point in the pipeline, not two).

**`get_portfolio_context()`** — wherever titles/descriptions are pulled from CPTs:

```php
$title       = html_entity_decode( get_the_title( $post_id ), ENT_QUOTES, 'UTF-8' );
$description = html_entity_decode( get_post_meta( $post_id, 'description', true ), ENT_QUOTES, 'UTF-8' );
// Apply the same decode to any other CPT text field flowing into $context
// (company, duration, tech_stack entries, excerpt, etc.)
```

**`compose_reply_from_match()`** (HO-003 §4.4) — remove `esc_html()` wrapping, use the decoded strings directly:

```php
case 'experience':
    return sprintf(
        "That's from my time as **%s** at %s (%s). %s",
        $item['title'],
        $item['company'] ?? '',
        $item['duration'] ?? '',
        $item['description'] ?? ''
    );
```

Apply the same removal to the `skill`, `project`, and `post` cases in the same method — all four currently have the identical double-encode risk.

### Note on scope

The static Q&A cache answers (HO-002 Tier 0) that reference portfolio data (e.g. skills list, projects list) should be audited for the same pattern if they pull from CPT fields rather than fully hardcoded strings — confirm during implementation whether any Tier 0 templates are affected.

---

## 3. Bug 2 — Tier 0 Keyword Collision Blocking Email Questions

### Observed

```
have you done any email migrations, and if you have who might it be for?
> I'm available for select engagements — QA consulting, WordPress development,
  infrastructure work, and AI integration projects. Share your name and email
  and I'll follow up personally.
```

A specific, answerable question about email migration work got the generic hire/contact template instead of anything from Tier 1 (skills/projects) or a grounded LLM answer.

### Root Cause

Tier 0 (HO-002 §4) runs first, before Tier 1 or the LLM ever see the message. Two existing patterns both key on the word "email", and the broader one wins:

| Pattern | Keywords (HO-002 original) |
|---|---|
| #7 Contact/Hire | `hire, contact, available, booking, engagement, work together, reach you, **email**, get in touch` |
| #10 Email deliverability | `email deliverability, spam, dkim, spf, dmarc, email authentication` |

Because pattern #7 contains the bare single-word keyword `email`, *any* message containing that word anywhere — including "have you done any **email** migrations" — matches pattern #7 first and returns immediately. Pattern #10 (and, since HO-003, Tier 1's skill/project match, and Tier 2's LLM) never get a chance to run, regardless of how specific or answerable the actual question is.

This is a **pre-existing Tier 0 design bug**, not a Tier 1 gap — but it directly blocks HO-003's retrieval layer from ever being reached for this entire topic area (email deliverability, DNS, SPF/DKIM/DMARC — a core listed skill per HO-002 §4 pattern #10 and the memory'd IMaD Consulting hero offer).

### Fix

Tighten pattern #7 to require multi-word, unambiguous phrasing — remove the bare `email` keyword entirely. Single common nouns should not live in Tier 0 at all now that Tier 1/2 exist to add nuance; Tier 0 is reserved for phrases that are unambiguous standalone.

```php
// Pattern 7 — Contact/Hire (tightened)
'contact' => [
    'hire me', 'hire you', 'contact you', 'available for',
    'booking', 'engagement', 'work together', 'reach you',
    'get in touch', 'your email address', 'how to contact',
],

// Pattern 10 — Email deliverability (unchanged, now actually reachable)
'email_deliverability' => [
    'email deliverability', 'spam', 'dkim', 'spf', 'dmarc',
    'email authentication', 'email migration', 'email migrations',
],
```

### Standing rule going forward

Before adding any new Tier 0 keyword, grep it against every existing pattern's keyword array. If a keyword is a single common word rather than a specific multi-word phrase, it almost certainly belongs nowhere in Tier 0 — let Tier 1 (structured retrieval) or Tier 2 (LLM) handle it instead, since both now exist specifically to add the nuance Tier 0 can't.

### Verification

After the fix, "have you done any email migrations" should either:
- Hit Tier 0 pattern #10 if "migration"/"migrations" is added there (as above), **or**
- Fall through to Tier 1 and match against the email/DNS-related skill entry, **or**
- Reach Tier 2/LLM grounded with the email-deliverability skill as a near-miss hint (HO-003 §5)

Any of these three outcomes is correct — the current behavior (generic contact template, zero information) is the only wrong one.

---

## 4. Bug 3 — Broken Markdown List Rendering

### Observed

```
Here's where I've worked:

* IMaD Consulting (London, 2024 — Present) — Senior QA Engineer & Systems Consultant
* 
* Enterprise SaaS (Remote, 2021 — 2024) — QA Automation Engineer
* 
* Managed Services Firm (London, 2019 — 2021) — Systems Administrator
* 
* Regional Bank (Manchester, 2017 — 2019) — IT Support Engineer
* 
```

Empty bullet points appear between every real entry.

### Root Cause

This is Tier 0 pattern #6 ("experience/work history" — HO-002 §4), which lists all experience entries in one reply, distinct from HO-003's Tier 1 single-match template. Its PHP template joins list items with a blank line between each (`"- {$entry}\n\n"` per item), but the client-side `parseMarkdown()` (HO-002 §10) treats any `\n\n` as a paragraph break *before* it groups consecutive `- `-prefixed lines into a single `<ul>`. The blank line between each bullet splits the list at every entry and leaves a stray empty `<li>` behind from the broken grouping.

### Fix

**PHP side** — join list items with a single `\n`, not `\n\n`. Only use `\n\n` to separate the list block as a whole from surrounding prose (before the list starts, after it ends):

```php
$lines = array_map( function( $exp ) {
    return sprintf(
        "- %s (%s, %s) — %s",
        $exp['title'],
        $exp['location'] ?? '',
        $exp['duration'],
        $exp['description'] ?? ''
    );
}, $experience_entries );

$reply = "Here's where I've worked:\n\n"
       . implode( "\n", $lines )
       . "\n\nEach role built on the last — from hands-on IT support to QA automation and infrastructure. If you'd like more details on any of these, just ask!";
```

**JS side (defense-in-depth)** — harden `parseMarkdown()` to collapse consecutive blank lines between `- `-prefixed lines before paragraph-splitting, so a future template mistake of this exact shape degrades gracefully instead of producing empty bullets:

```javascript
// Before paragraph-splitting on \n\n, collapse any blank line(s) that sit
// strictly between two consecutive markdown list lines — prevents template
// bugs (double \n between list items) from fragmenting a single <ul>.
text = text.replace(/(-\s.+)\n\s*\n(?=-\s)/g, '$1\n');
```

This is a genuine bug-prevention hardening, not just a workaround for this one template — worth keeping even after the PHP-side template is fixed, since any future Tier 0/1 template author could reintroduce the same mistake.

---

## 5. Source Transcript (for reference)

```
Hello! I'm Malachy — QA Engineer, SysAdmin & developer. How can I help you today?
what do you do?
> I'm Malachy Egbuna, a QA Engineer, SysAdmin & IT Support Specialist based in the UK...
what projects have you accomplished as a QA?
> That's from my time as Senior QA Engineer &#038; Systems Consultant at IMaD Consulting...  [BUG 1]
what kind of project is that?
> One example is Test Automation Framework: End-to-end test automation framework...  [correct — Tier 1 working]
Who company have you worked with?
> Here's where I've worked: * IMaD Consulting... * [BUG 3]
have you done any email migrations, and if you have who might it be for?
> I'm available for select engagements...  [BUG 2 — should have hit email_deliverability pattern or Tier 1]
```

The `"what kind of project is that?"` → Test Automation Framework answer confirms HO-003's Tier 1 matching and single-item templating are functioning correctly in production. None of these three bugs are regressions from HO-003 — all three predate it or sit in Tier 0 templates HO-003 didn't touch.

---

## 6. Files Modified

| File | Change |
|------|--------|
| `inc/ai-chat-bot.php` | `get_portfolio_context()` — add `html_entity_decode()` on all CPT text fields entering `$context`. `compose_reply_from_match()` — remove `esc_html()` calls on all four candidate types (Bug 1). Tier 0 keyword array — remove bare `email` from contact pattern, tighten to multi-word phrases; add `email migration(s)` to email_deliverability pattern (Bug 2). Tier 0 experience-list template — join with single `\n` (Bug 3). |
| `assets/js/ai-chat-bot.js` | `parseMarkdown()` — add blank-line collapse regex ahead of paragraph-splitting (Bug 3, defense-in-depth). |

No REST contract change, no new dependencies, no changes to HO-003's scoring/threshold logic.

---

## 7. Verification Checklist

- [ ] "who have you worked with" → clean bulleted list, no empty `<li>` entries, renders correctly in both light and dark mode
- [ ] Any reply referencing a CPT title/description containing `&` renders a plain ampersand, not `&#038;` or `&amp;`
- [ ] "have you done any email migrations" → reaches either Tier 0 pattern #10, Tier 1 skill match, or a grounded Tier 2/LLM answer — never the generic contact template
- [ ] "hire me" / "how can I contact you" / "are you available" still correctly hit the contact pattern (regression check — confirm the tightened keyword list didn't lose legitimate matches)
- [ ] Re-run the full HO-003 verification checklist (§9) to confirm these fixes didn't disturb Tier 1 routing
- [ ] Spot-check 2–3 other Tier 0 patterns (skills, projects) for the same `html_entity_decode`/list-join issues, since they share the same context source and template style

---

*This addendum is a pure bugfix pass — no architectural changes beyond what HO-003 already introduced. All three fixes are independent and can be applied/tested individually if preferred.*
