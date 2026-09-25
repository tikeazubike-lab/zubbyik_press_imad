# HO-005: AI Chatbot — Root Cause Findings & Remaining Fixes (Addendum to HO-002 / HO-003 / HO-004)

**Date:** 2026-07-20
**Status:** Investigation complete — partial fixes pending implementation, one item open pending further evidence
**Project:** Malachy — QA Engineer Portfolio
**Live URL:** https://imadconsult.zubbystudio.site
**Theme Root:** `/home/zubbyik/wordpress_project/malachy-portfolio/`
**Supersedes:** Nothing — narrows HO-004's Bug B investigation to a confirmed root cause
**Depends on:** `inc/ai-chat-bot.php` (current source reviewed 2026-07-20), HO-002, HO-003, HO-004

---

## 1. Summary

A live transcript (reviewed in the prior session) showed two apparent bugs: a Tier 0 pricing-pattern miss on a cost-related question, and a subsequent LLM failure with a generic "temporarily unavailable" message. Direct investigation — reading `debug.log`, testing the LLM endpoint directly via curl, and reading the current `inc/ai-chat-bot.php` source — traced both symptoms to a **single root cause: a stale OPcache compile running against an older version of the file**, not a logic defect in the code as currently written. This was confirmed by reproducing the exact failing message directly against the live REST endpoint, which now returns the correct static reply.

One item (the exact client-facing error string) remains open pending review of `assets/js/ai-chat-bot.js`, since that string does not exist anywhere in the current PHP source.

---

## 2. Root Cause: OPcache Serving Stale Bytecode

### Evidence chain

1. **`debug.log`** showed a fatal error on 19-Jul 12:14 UTC: `Call to undefined function wp_is_numeric_context()` thrown from `is_dark_mode()` at `ai-chat-bot.php:110`.
2. **Current source review** confirmed `is_dark_mode()` contains no such call, and no reference to `wp_is_numeric_context()` exists anywhere in the file — that function isn't part of WordPress core.
3. **Direct curl test** against `/wp-json/malachy/v1/chat` with the exact message from the failing transcript (`"how much would it cost me to migrate my website from inmotion to godaddy"`) returned the **correct** Tier 0 static pricing reply (`source: "static"`), proving the current code's Tier 0 routing is correct and was never the actual defect.
4. **`opcache_get_status()`** for this file returned `"Not currently cached"` at time of check — consistent with a cache invalidation (container restart, OPcache reset, or a timestamp-triggered recompile) having occurred between the failing transcript and the investigation.

### Conclusion

The PHP process serving requests at the time of the failing transcript was very likely executing an **older, in-memory compiled version** of `ai-chat-bot.php` — one that both lacked the current, correct pricing-pattern behavior and still contained a call to a non-existent function. The file on disk had already been updated to the version reviewed in this session; OPcache had not yet revalidated against it. Both anomalies in the original transcript (routing miss + fatal error referencing dead code) are explained by this one cause — no code changes to Tier 0 matching or `is_dark_mode()` are required, since both are already correct in the current source.

### Fix — deployment/caching hygiene, not application code

HO-002 §13 states theme files are bind-mounted and "changes are live immediately." That assumption only holds if OPcache is configured to revalidate on every request, or is cleared explicitly after every edit. Neither is guaranteed by a bind mount alone.

**Standing process addition** — run this as the last step whenever `inc/ai-chat-bot.php` (or any theme PHP) is edited directly on the VPS:

```bash
docker exec malachy-wp php -r 'opcache_reset();'
```

**Recommended one-time config check** — confirm whether OPcache is set up for fast iteration on this box:

```bash
docker exec malachy-wp php -i | grep -E 'opcache.validate_timestamps|opcache.revalidate_freq'
```

If `opcache.validate_timestamps` is `0`, that's the real underlying cause and should be changed to `1` with a short `opcache.revalidate_freq` (e.g. `2`) on this environment, since it's being iterated on directly rather than through a build/deploy pipeline. If it's already `1`, the `opcache_reset()` step above should be added to your standard edit-and-test loop as a safety margin regardless, since timestamp revalidation still depends on filesystem mtimes updating correctly through the bind mount, which isn't always guaranteed across host/container boundaries.

---

## 3. Confirmed Resolved — No Code Change Needed

| Item | Status | Evidence |
|---|---|---|
| Tier 0 pricing-pattern miss on "how much...cost..." | **Resolved** | Direct curl reproduction returned correct static reply |
| `is_dark_mode()` fatal (`wp_is_numeric_context`) | **Resolved** | Function does not exist anywhere in current source; confirmed dead/stale reference |

Both are logged here rather than silently dropped, so a future session doesn't re-investigate them from scratch or mistake the OPcache explanation for a guess — it was confirmed via direct reproduction, not inferred.

---

## 4. Still Open — Requires Code Change

### 4.1 No error logging in `call_opencode_api()`

Every failure branch (`wp_remote_post` error, non-200 HTTP status, empty `content`) returns a `WP_Error` but never calls `error_log()` first. This is precisely why `debug.log` had **zero entries** for the LLM failure in the original transcript, even accounting for the OPcache explanation — there is currently no code path that would have logged it even under the correct, current version of the file. This needs fixing independent of the OPcache root cause, since any future LLM-side failure (rate limit, timeout, malformed response) will be similarly invisible without it.

**Fix — `inc/ai-chat-bot.php`, inside `call_opencode_api()`:**

```php
$response = wp_remote_post( $endpoint, $args );

if ( is_wp_error( $response ) ) {
    error_log( 'Malachy Chatbot LLM connection error: ' . $response->get_error_message() );
    return new WP_Error(
        'api_error',
        __( 'Could not connect to AI service. Please try again later.', 'malachy-portfolio' )
    );
}

$code = wp_remote_retrieve_response_code( $response );
if ( $code !== 200 ) {
    $body = wp_remote_retrieve_body( $response );
    error_log( sprintf( 'Malachy Chatbot LLM HTTP %d: %s', $code, $body ) );

    $data = json_decode( $body, true );
    $error_msg = isset( $data['error']['message'] ) ? $data['error']['message'] : __( 'AI service returned an error.', 'malachy-portfolio' );

    if ( in_array( $code, array( 401, 403, 402 ), true ) ) {
        return new WP_Error( 'credit_exhausted', $error_msg, array( 'status' => $code ) );
    }
    if ( $code === 429 ) {
        return new WP_Error( 'rate_limited', $error_msg, array( 'status' => 429 ) );
    }
    return new WP_Error( 'api_error', $error_msg, array( 'status' => $code ) );
}

$body = wp_remote_retrieve_body( $response );
$data = json_decode( $body, true );

if ( empty( $data['choices'][0]['message']['content'] ) ) {
    error_log( 'Malachy Chatbot LLM returned empty content. Raw response: ' . $body );
    return new WP_Error(
        'api_error',
        __( 'AI service returned an empty response. Please try again.', 'malachy-portfolio' )
    );
}
```

This also directly protects against the `finish_reason: "length"` / `content: null` failure mode identified in the prior session's curl test against `mimo-v2.5` — if that happens again in production, it will now be visible in `debug.log` with the full raw response body, rather than only visible as a generic user-facing error with no trace.

### 4.2 Duplicate chatbot-build regex block

`get_static_answer()` contains the identical pattern block twice, back to back:

```php
// Chatbot / AI integration — only when asking about BUILDING one.
if ( preg_match( '/(build.*chatbot|create.*chatbot|make.*chatbot|need.*chatbot|want.*chatbot|chatbot.*integrat|ai.*integrat|build.*ai|create.*ai)/i', $lower ) ) {
    return "I can absolutely help with AI chatbot integration...";
}
```

This appears once directly after the skills pattern, and again later, verbatim, after the WordPress pattern's neighbor. Harmless as written (the first occurrence always wins, the second is unreachable dead code) but should be removed for maintainability — a future edit to one copy and not the other would silently reintroduce inconsistent behavior.

**Fix:** delete the second occurrence entirely.

---

## 5. Still Open — Pending Further Evidence

### 5.1 Client-facing error string does not exist in PHP

The exact string the visitor saw — *"I can't respond right now — my AI service is temporarily unavailable."* — does not appear anywhere in `inc/ai-chat-bot.php`. Every server-side error message in the current source is distinct and more specific:

| WP_Error code | Message |
|---|---|
| `no_api_key` | "AI service is not configured. Please contact the site administrator." |
| `api_error` (connection) | "Could not connect to AI service. Please try again later." |
| `api_error` (empty content) | "AI service returned an empty response. Please try again." |
| `credit_exhausted` | Dynamic, from opencode.ai's own error body |
| `rate_limited` | Dynamic, from opencode.ai's own error body |

Since none of these match the observed string, it must be generated **client-side** in `assets/js/ai-chat-bot.js` — most likely a catch-all display mapping that collapses multiple/all WP_Error codes into one generic sentence rather than surfacing the more specific server-side messages already designed for this in HO-002 §9.

**Action required:** review `ai-chat-bot.js`'s response-handling logic once available. If it maps every error response to a single generic string regardless of `code`, that should be tightened to surface the distinct messages the PHP already produces — this is useful independent of whether it was ever really the cause of the original transcript's failure, since the PHP side already does the work of distinguishing these cases and the JS is currently discarding that distinction.

This item is deliberately left open rather than guessed at a second time — HO-004 already contained one round of speculative diagnosis on this exact question; resolving it needs the actual JS source, not a further hypothesis.

---

## 6. Files Modified (this handover)

| File | Change |
|------|--------|
| `inc/ai-chat-bot.php` | Add `error_log()` calls to all three failure branches in `call_opencode_api()` (§4.1). Remove duplicate chatbot-build regex block from `get_static_answer()` (§4.2). No changes to Tier 0 pricing pattern or `is_dark_mode()` — both confirmed already correct. |
| Deployment process (not a file) | Add `opcache_reset()` step after direct theme-file edits on the VPS, or fix `opcache.validate_timestamps` config if found to be `0` (§2). |
| `assets/js/ai-chat-bot.js` | **Pending** — no fix proposed yet; awaiting source review (§5.1). |

---

## 7. Verification Checklist

- [x] Direct curl reproduction of the original failing message returns the correct static pricing reply — **already confirmed this session**
- [ ] `opcache.validate_timestamps` / `opcache.revalidate_freq` checked and corrected if needed
- [ ] `opcache_reset()` (or equivalent) added to the standard post-edit workflow for this theme
- [ ] `call_opencode_api()` failure branches produce `debug.log` entries — verify by temporarily forcing a bad API key or malformed request and confirming a log line appears
- [ ] Duplicate chatbot-build regex block removed, single copy retained, existing chatbot-build questions still match correctly (regression check)
- [ ] `ai-chat-bot.js` reviewed; client-facing error strings confirmed to map distinctly per WP_Error code, or fixed if found to be a generic catch-all (HO-005a, pending)

---

## 8. Process Note

This investigation is a good example of why HO-004's original instinct — asking for the actual source rather than proposing a fix from the transcript alone — mattered: a plausible-sounding hypothesis (Tier 0 tokenization treating "how much" as two separate words) turned out to be wrong once the real code was read, and the actual cause (stale OPcache) was something no amount of reading the PHP logic alone would have surfaced. Worth keeping as standing practice for any future "it worked before but not now" report on this project: reproduce directly against the live endpoint before writing a code fix, since intermittent/stale-state causes look identical to logic bugs from a transcript alone.

---

*This handover narrows HO-004's open Bug B to a confirmed root cause (OPcache staleness) for the routing/fatal-error portion, and separates out the two genuinely-still-open code items (logging gap, dead code) from the one item still requiring evidence (JS error-string mapping).*
