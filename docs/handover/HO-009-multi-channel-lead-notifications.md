# HO-009: Multi-Channel Lead Notifications — Owner Alert (Email + Telegram) + Visitor Confirmation Email

**Date:** 2026-07-21
**Status:** Design complete — pending implementation
**Project:** Malachy — QA Engineer Portfolio
**Theme Root:** `/home/zubbyik/wordpress_project/malachy-portfolio/`
**Depends on:** `inc/ai-chat-bot.php` `handle_lead()` method (HO-002 §7, unchanged contract)
**Decision inputs:** Owner alert channels = Email + Telegram. Visitor gets a rich confirmation email with discussion context.

---

## 1. Problem Statement

Currently, `handle_lead()` does exactly one thing on a successful lead capture: `wp_mail()` to the site owner's configured contact address (HO-002 §7). Two gaps:

1. **Owner has no real-time alert** — has to be watching the inbox to notice a new lead. No messenger notification.
2. **Visitor gets nothing** — the in-chat "I'll follow up" text (HO-002 §7, HO-006 auto-detect flow) is the only acknowledgment. No email trail for them to reference or reply to.

This handover adds both, without changing the existing REST contract (`POST /wp-json/malachy/v1/chat/lead`, same request/response shape per HO-002 §15).

---

## 2. Architecture

```
Visitor submits lead (name + email + note/transcript)
    │
    ▼
handle_lead()
    │
    ├──► 1. Owner email (existing, kept as-is)          — wp_mail() to malachy_contact_email
    ├──► 2. Owner Telegram alert (NEW)                   — Telegram Bot API sendMessage
    └──► 3. Visitor confirmation email (NEW)             — wp_mail() to visitor, with conversation recap
    │
    ▼
Response: success if AT LEAST ONE owner-facing channel (email OR Telegram) succeeded.
Visitor email is best-effort and does not block success/failure of the response.
```

**Design principle:** the visitor's REST response (`success: true/false`) reflects whether *you* were notified, not whether the visitor's confirmation email happened to send — a visitor-email hiccup shouldn't tell the visitor their lead failed to submit when you may have already received it via Telegram or email.

---

## 3. Telegram Setup (one-time, manual — required before deploying this code)

Telegram Bot API is free, has no approval process (unlike WhatsApp Business API), and works over a simple HTTPS POST — well suited to shared hosting where outbound webhook calls are the only option (no persistent socket/websocket needed).

### 3.1 Create a bot

1. Open Telegram, message **@BotFather**
2. Send `/newbot`, follow the prompts (choose a name + a unique username ending in `bot`)
3. BotFather returns a **bot token** — looks like `123456789:AAFq...` — save it, this is `TELEGRAM_BOT_TOKEN`

### 3.2 Get your chat ID

1. Message your new bot directly (search its username, hit Start, send any message — e.g. "hi")
2. In a browser, visit: `https://api.telegram.org/bot<YOUR_BOT_TOKEN>/getUpdates`
3. Find `"chat":{"id": ...}` in the JSON response — that number is `TELEGRAM_CHAT_ID`

### 3.3 Add both to wp-config.php

Same convention as `OPENCODE_API_KEY` (HO-002 §14):

```php
define('TELEGRAM_BOT_TOKEN', '123456789:AAFq...');
define('TELEGRAM_CHAT_ID', '987654321');
```

Or via environment variable in `docker-compose.yml` (staging), matching the existing `OPENCODE_API_KEY` pattern (HO-002 §13):

```yaml
environment:
  TELEGRAM_BOT_TOKEN: ${TELEGRAM_BOT_TOKEN}
  TELEGRAM_CHAT_ID: ${TELEGRAM_CHAT_ID}
```

**Note for production (InMotion):** since this is shared hosting, confirm outbound HTTPS to `api.telegram.org` isn't blocked by the host's firewall before relying on this — most shared hosts allow outbound curl/HTTPS by default, but worth a quick test after deployment (see verification checklist, §7).

---

## 4. Implementation

### 4.1 Telegram notification method

```php
/**
 * Send a Telegram notification to the site owner when a new lead is captured.
 * Best-effort — failures are logged, never thrown, never block the visitor's response.
 *
 * @param string $name  Lead name.
 * @param string $email Lead email.
 * @param string $note  Conversation transcript (may be empty).
 * @return bool True on successful delivery, false otherwise.
 */
private function send_telegram_notification( $name, $email, $note ) {
    $bot_token = defined( 'TELEGRAM_BOT_TOKEN' ) ? TELEGRAM_BOT_TOKEN : getenv( 'TELEGRAM_BOT_TOKEN' );
    $chat_id   = defined( 'TELEGRAM_CHAT_ID' ) ? TELEGRAM_CHAT_ID : getenv( 'TELEGRAM_CHAT_ID' );

    if ( empty( $bot_token ) || empty( $chat_id ) ) {
        error_log( 'Malachy Chatbot: Telegram not configured (missing token/chat ID) — skipping notification.' );
        return false;
    }

    // Telegram messages cap at 4096 chars; keep the excerpt well under that.
    $excerpt = ! empty( $note ) ? mb_substr( trim( $note ), 0, 500 ) : '(no conversation transcript)';

    $text = sprintf(
        "🔔 <b>New Chatbot Lead</b>\n\n<b>Name:</b> %s\n<b>Email:</b> %s\n<b>Time:</b> %s\n<b>IP:</b> %s\n\n<b>Conversation excerpt:</b>\n%s",
        esc_html( $name ),
        esc_html( $email ),
        esc_html( wp_date( 'Y-m-d H:i:s' ) ),
        esc_html( $this->get_client_ip() ),
        esc_html( $excerpt )
    );

    $url = "https://api.telegram.org/bot{$bot_token}/sendMessage";

    $response = wp_remote_post( $url, array(
        'timeout' => 10,
        'body'    => array(
            'chat_id'    => $chat_id,
            'text'       => $text,
            'parse_mode' => 'HTML',
        ),
    ) );

    if ( is_wp_error( $response ) ) {
        error_log( 'Malachy Chatbot Telegram error: ' . $response->get_error_message() );
        return false;
    }

    $code = wp_remote_retrieve_response_code( $response );
    if ( $code !== 200 ) {
        error_log( sprintf( 'Malachy Chatbot Telegram HTTP %d: %s', $code, wp_remote_retrieve_body( $response ) ) );
        return false;
    }

    return true;
}
```

**Why HTML `parse_mode` and not Markdown:** Telegram's legacy Markdown mode requires escaping `_`, `*`, `` ` ``, `[` throughout free-text content (the conversation excerpt, which you don't control the wording of), which is fragile. HTML mode only requires escaping `<`, `>`, `&` — already handled by `esc_html()` above — making it the safer choice for arbitrary visitor-typed content.

### 4.2 Visitor confirmation email (rich, with conversation context)

```php
/**
 * Send a confirmation email to the visitor after a successful lead capture,
 * including a brief recap of the conversation that led to it. Best-effort —
 * failure here does not affect the REST response's success/failure status.
 *
 * @param string $name  Lead name.
 * @param string $email Lead email.
 * @param string $note  Conversation transcript (may be empty).
 * @return bool True on successful delivery, false otherwise.
 */
private function send_visitor_confirmation( $name, $email, $note ) {
    $subject = sprintf(
        /* translators: %s: lead's first name */
        __( 'Thanks for reaching out, %s!', 'malachy-portfolio' ),
        $name
    );

    $reply_to = get_option( 'malachy_contact_email', get_option( 'admin_email', 'malachy.egbuna@imadconsulting.co.uk' ) );

    $body  = sprintf( "Hi %s,\n\n", $name );
    $body .= "Thanks for reaching out through my portfolio chatbot! I've received your details and will follow up with you personally as soon as I can.\n\n";

    $recap = $this->format_conversation_recap( $note );
    if ( ! empty( $recap ) ) {
        $body .= "Here's a quick recap of what we discussed, so we're both on the same page:\n\n";
        $body .= $recap . "\n\n";
    }

    $body .= "In the meantime, feel free to reply directly to this email with any extra details about your project — it'll come straight to me.\n\n";
    $body .= "Talk soon,\nMalachy Egbuna\n" . home_url( '/' );

    $headers = array(
        'Content-Type: text/plain; charset=UTF-8',
        'Reply-To: ' . $reply_to,
    );

    $sent = wp_mail( $email, $subject, $body, $headers );

    if ( ! $sent ) {
        error_log( 'Malachy Chatbot: visitor confirmation email failed to send to ' . $email );
    }

    return $sent;
}

/**
 * Lightly format the raw conversation transcript for inclusion in the
 * visitor's confirmation email. No LLM call — this is a plain-text trim,
 * not a generated summary, to avoid adding latency/cost to lead submission.
 *
 * @param string $note Raw transcript from the JS lead-capture flow.
 * @return string Formatted recap, or empty string if no transcript.
 */
private function format_conversation_recap( $note ) {
    if ( empty( $note ) ) {
        return '';
    }
    // Cap length generously for an email body (unlike the Telegram excerpt,
    // which is capped tighter at 500 chars for the messenger UI).
    return trim( mb_substr( trim( $note ), 0, 1500 ) );
}
```

**Note on "recap" approach:** this reuses the `note` field the JS already sends as the full conversation transcript (HO-002 §7's existing "Note / Conversation" convention) — no new data capture needed, no additional LLM round-trip at lead-submission time (keeps this fast and free, consistent with the Tier 0/1 cost-avoidance philosophy already established in HO-002 §17/18 and HO-003). If you'd prefer an LLM-generated summary instead of a raw trimmed transcript, that's a small follow-on (see §8) — starting with the plain-text version first avoids adding a point of failure to the lead flow itself.

### 4.3 Updated `handle_lead()`

```php
public function handle_lead( WP_REST_Request $request ) {
    $name  = $request->get_param( 'name' );
    $email = $request->get_param( 'email' );
    $note  = $request->get_param( 'note' );

    if ( empty( $name ) || empty( $email ) ) {
        return new WP_Error(
            'missing_fields',
            __( 'Please provide your name and email.', 'malachy-portfolio' ),
            array( 'status' => 400 )
        );
    }

    if ( ! is_email( $email ) ) {
        return new WP_Error(
            'invalid_email',
            __( 'Please enter a valid email address.', 'malachy-portfolio' ),
            array( 'status' => 400 )
        );
    }

    // 1. Owner email — existing behavior, unchanged.
    $to      = get_option( 'malachy_contact_email', get_option( 'admin_email', 'malachy.egbuna@imadconsulting.co.uk' ) );
    $subject = sprintf(
        /* translators: %s: lead name */
        __( '[Chatbot Lead] New inquiry from %s', 'malachy-portfolio' ),
        $name
    );
    $body  = sprintf( "Name: %s\nEmail: %s\n", $name, $email );
    $body .= sprintf( "IP: %s\nDate: %s\n", $this->get_client_ip(), wp_date( 'Y-m-d H:i:s' ) );
    if ( ! empty( $note ) ) {
        $body .= sprintf( "\nNote / Conversation:\n%s", $note );
    }
    $headers = array(
        'Content-Type: text/plain; charset=UTF-8',
        'Reply-To: ' . $email,
    );
    $owner_email_sent = wp_mail( $to, $subject, $body, $headers );
    if ( ! $owner_email_sent ) {
        error_log( 'Malachy Chatbot: owner email failed for lead from ' . $email );
    }

    // 2. Owner Telegram alert — NEW.
    $telegram_sent = $this->send_telegram_notification( $name, $email, $note );

    // 3. Visitor confirmation email — NEW. Best-effort, does not affect response status.
    $this->send_visitor_confirmation( $name, $email, $note );

    // 4. Durable lead log — NEW. See §5. Written regardless of channel outcomes,
    //    so no lead is ever silently lost even if every delivery channel fails.
    $this->log_lead( $name, $email, $note, $owner_email_sent, $telegram_sent );

    // Success if AT LEAST ONE owner-facing channel worked.
    if ( $owner_email_sent || $telegram_sent ) {
        return new WP_REST_Response(
            array(
                'success' => true,
                'message' => __( 'Thank you! Malachy will be in touch soon.', 'malachy-portfolio' ),
            ),
            200
        );
    }

    return new WP_Error(
        'mail_failed',
        __( 'Could not send your details. Please try again later.', 'malachy-portfolio' ),
        array( 'status' => 500 )
    );
}
```

---

## 5. Durable Lead Log (recommended addition, not optional)

If both owner email *and* Telegram fail simultaneously (e.g. a DNS blip, or Telegram API downtime coinciding with an SMTP issue), the current design returns a hard failure to the visitor — meaning if they don't retry, that lead is gone for good. A cheap, durable fallback closes this gap using the same low-overhead pattern as `log_nearmiss()` (HO-003 §6) — no new database table, just a capped `wp_option`.

```php
/**
 * Log every lead submission attempt, independent of delivery outcome.
 * Acts as a durable fallback record if all delivery channels fail —
 * same low-overhead pattern as log_nearmiss() (HO-003).
 *
 * @param string $name           Lead name.
 * @param string $email          Lead email.
 * @param string $note           Conversation transcript.
 * @param bool   $email_sent     Whether the owner email succeeded.
 * @param bool   $telegram_sent  Whether the Telegram alert succeeded.
 */
private function log_lead( $name, $email, $note, $email_sent, $telegram_sent ) {
    $log = get_option( 'malachy_chat_leads_log', array() );

    $log[] = array(
        'name'          => sanitize_text_field( $name ),
        'email'         => sanitize_email( $email ),
        'note'          => mb_substr( sanitize_textarea_field( $note ), 0, 2000 ),
        'email_sent'    => $email_sent,
        'telegram_sent' => $telegram_sent,
        'time'          => current_time( 'mysql' ),
    );

    // Cap at 200 entries — leads are lower-volume than near-miss log entries,
    // but still bounded to avoid unbounded option growth.
    if ( count( $log ) > 200 ) {
        $log = array_slice( $log, -200 );
    }

    update_option( 'malachy_chat_leads_log', $log, false ); // false = don't autoload
}
```

Inspect via WP-CLI, same convention as the near-miss log:

```bash
docker exec malachy-wp wp option get malachy_chat_leads_log --format=json
```

If you ever see an entry with both `email_sent: false` and `telegram_sent: false`, that's a lead that would otherwise have been completely lost under the old single-channel design — check this log periodically, or set up a simple WP-CLI cron to alert if any such entry appears.

---

## 6. Files Modified

| File | Change |
|------|--------|
| `inc/ai-chat-bot.php` | Add `send_telegram_notification()`, `send_visitor_confirmation()`, `format_conversation_recap()`, `log_lead()`. Modify `handle_lead()` to call all three new methods and use OR-based success logic (§4.3). |
| `wp-config.php` (staging + production) | Add `TELEGRAM_BOT_TOKEN` and `TELEGRAM_CHAT_ID` constants (§3.3). |
| `docker-compose.yml` (staging only) | Optionally add both as environment variables, matching the existing `OPENCODE_API_KEY` pattern. |

No REST contract change — `POST /wp-json/malachy/v1/chat/lead` request/response shape is identical to HO-002 §15. No JS changes required.

---

## 7. Verification Checklist

- [ ] Telegram bot created via BotFather, token obtained, chat ID obtained via `getUpdates`
- [ ] `TELEGRAM_BOT_TOKEN` / `TELEGRAM_CHAT_ID` added to staging `wp-config.php` (or docker-compose env)
- [ ] Submit a test lead through the chatbot widget → confirm a Telegram message arrives with name, email, timestamp, IP, and conversation excerpt
- [ ] Confirm the existing owner email still arrives unchanged (regression check on HO-002 §7 behavior)
- [ ] Confirm the visitor receives a confirmation email at the address they submitted, containing the recap text
- [ ] Temporarily break the Telegram token (wrong value) and confirm: owner email still succeeds, REST response still returns `success: true`, `debug.log` shows a Telegram error logged
- [ ] Temporarily break both owner email (invalid `malachy_contact_email`) AND Telegram token, confirm REST response returns the `mail_failed` error, AND confirm the lead still appears in `malachy_chat_leads_log` via WP-CLI — this is the scenario the durable log exists to protect against
- [ ] On InMotion production specifically: confirm outbound HTTPS to `api.telegram.org` isn't blocked by the shared host (test immediately after deploying, before relying on it)
- [ ] Visitor confirmation email's `Reply-To` header correctly routes replies to the configured `malachy_contact_email`, not to a no-reply address

---

## 8. Optional Follow-Ons (not required to ship this handover)

| Idea | Notes |
|------|-------|
| LLM-generated conversation summary instead of raw transcript trim | Would make the visitor email read more naturally ("We talked about your interest in test automation for...") but adds an LLM call + cost/latency to the lead-submission path itself, and a new failure mode to a flow that should stay simple and reliable. Worth revisiting only if the plain-text recap reads awkwardly in practice. |
| WhatsApp channel | Deliberately deferred per your channel selection — Meta's WhatsApp Business Cloud API requires business verification, phone number registration, and pre-approved message templates for any notification sent outside a 24-hour customer-service window, which is a meaningfully heavier lift than Telegram's zero-approval bot API. Revisit only if Telegram proves insufficient. |
| Alert-on-failure for the durable lead log | A simple WP-CLI cron that checks `malachy_chat_leads_log` for any `email_sent: false, telegram_sent: false` entries and pings you some other way (e.g. a distinct fallback Telegram bot or a monitoring service) — closes the loop on "what if BOTH channels are down at once," though this is a low-probability edge case. |

---

*This handover adds two new notification channels on top of HO-002's existing lead-capture flow without changing its REST contract, and introduces a durable fallback log so no lead is silently lost even under a worst-case dual-channel failure.*
