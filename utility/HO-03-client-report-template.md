# WordPress Chatbot Readiness Assessment — Report Template

*[Site name / client name] — [Date]*

---

## 1. Summary (read this first)

**Verdict: [Lightweight embed works as-is / Partial decouple needed / Full rebuild needed]**

One paragraph, plain language, no jargon: what this means for them in practice, and the headline reason why. A non-technical site owner should be able to read this paragraph alone and know what happens next.

**Estimated cost range:** [$X–$Y]
**Estimated timeline:** [X weeks]

*(See Section 5 for how these were derived.)*

---

## 2. What we assessed

- **Chatbot type requested:** [Simple FAQ widget / Conversational AI assistant / Data-aware assistant]
- **Current hosting:** [Provider, plan/tier] — *[Identified by client / Identified by us as part of onboarding, included with deposit]*
- **Access reviewed:** [WP admin / hosting panel / FTP-SSH / analytics — whichever were available]
- **Traffic basis:** [Actual analytics data / estimated from business context — state which, since this affects confidence]

---

## 3. Scoring breakdown

| Category | Score (0–2) | Notes |
|---|---|---|
| Hosting resource limits | | |
| Persistent connection support | | |
| Theme/plugin architecture | | |
| Traffic pattern fit | | |
| Data/architecture fit | | |
| **Total** | **/10** | |

Flag any hard blockers here even if the total score doesn't fully reflect their severity (e.g. "Scored 1 point in this category but this alone would block a data-aware assistant regardless of total").

---

## 4. What we found, in plain terms

Short subsection per category that scored below 2 — explain *why*, not just the score. E.g.:

> **Theme/plugin architecture (scored 0):** Your security plugin (Wordfence) currently blocks all external script domains by default. A chatbot widget loads its interface from an external domain, so as configured, it would simply fail to load. This is fixable by whitelisting the specific domain — not a rebuild-level problem — but it does need to happen before any chatbot goes live.

Skip categories that scored a full 2 — no need to explain what already works.

---

## 5. Recommended path forward

Match this section to the verdict:

**If Lightweight embed works as-is:**
- What the embed involves, roughly (a script tag, a plugin install, or similar)
- Any small config changes still needed (e.g. whitelist a domain)
- Realistic cost/timeline for that alone

**If Partial decouple needed:**
- What "decouple" means here in concrete terms — e.g. keep WordPress as the content source, add a lightweight front-end or middleware layer to host the chatbot logic
- Why the full stack doesn't need replacing
- Cost/timeline range, with the main cost driver named (e.g. "most of the cost is building the middleware layer, not touching WordPress itself")

**If Full rebuild needed:**
- Which specific constraints make a rebuild the only reliable option (cite the categories that scored 0)
- What "rebuild" means in scope — front end only, or hosting move too
- Cost/timeline range, and a note that this is a separately scoped engagement (link out to the rebuild process rather than folding it into this report)

---

## 6. What happens if they do nothing / go with a cheaper option anyway

One honest paragraph: if they ignore the assessment and install a chatbot widget regardless, what's the realistic failure mode? (e.g. "the widget will likely load inconsistently and time out under normal daily traffic, not just at peak" — be specific to what you actually found, not generic scare language.)

---

## 7. Appendix — raw findings

Anything technical you want on record but that doesn't belong in the main report: specific plugin versions, hosting plan specs, raw traffic numbers, screenshots of errors encountered during testing.

---

*Note for portfolio use: when this report is for a self-built demo site rather than a real client, add a one-line disclosure at the top of the document — e.g. "This is a self-built demonstration site created to showcase the assessment methodology; it is not a client engagement." Keeps the case study honest and is more credible to prospective clients than pretending otherwise.*
