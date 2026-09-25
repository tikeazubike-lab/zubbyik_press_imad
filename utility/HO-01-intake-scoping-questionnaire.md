# WordPress Chatbot Readiness — Intake & Scoping Questionnaire

Use this at the start of every assessment (real client or demo site). The goal is to pin down what "chatbot" actually means before touching hosting or plugins — a huge share of failed chatbot projects come from mismatched expectations here, not bad infrastructure.

## 1. What kind of chatbot do they actually mean?

- [ ] **Simple FAQ widget** — static/scripted responses, no live data lookups, low resource footprint
- [ ] **Conversational AI assistant** — LLM-backed, open-ended conversation, moderate footprint
- [ ] **Data-aware assistant** — needs to read live site content, product catalog, inventory, or order status in real time — significantly higher resource and architecture requirements

Follow-up questions:
- What should it be able to answer that a search bar can't?
- Does it need to look anything up live (stock levels, prices, order status), or is a fixed knowledge base enough?
- Any expectation of it taking actions (booking, checkout assistance), or just answering questions?

## 2. Current hosting

- Hosting provider and plan/tier name:
- Hosting type: Shared (cPanel) / Managed WordPress (WP Engine, Kinsta, etc.) / Self-managed VPS
- Any known resource limits already hit (e.g. "site slows down during sales")?
- Is there a CDN or caching layer in front of the site already?

**If the client doesn't know their own hosting details** (expect this often — plan for it rather than treating it as an edge case): note that here and move on rather than stalling intake. Once the deposit clears, this becomes a scoped, time-boxed piece of onboarding (see engagement terms) — WHOIS/IP lookup and response headers usually identify provider and rough tier without needing access yet. Full plugin/resource/traffic findings still require WP admin or hosting panel access regardless, so this only covers "who's hosting it," not the rest of Section 2.

## 3. Traffic and analytics availability

- [ ] Google Analytics or similar available
- [ ] Server-level logs available
- [ ] No analytics — will need to estimate from business context (foot traffic, marketing spend, seasonality)

If available, request: average daily visitors, peak concurrent visitors (if known), typical session length.

## 4. Access needed for the assessment

Check off what you'll request before starting technical review:

- [ ] Hosting control panel (cPanel / hosting dashboard) — read-only or admin
- [ ] WordPress admin login
- [ ] FTP/SFTP or SSH (VPS only)
- [ ] Analytics dashboard access (if available)

## 5. Business context (shapes the verdict, not just the tech)

- What's the actual goal of adding a chatbot? (reduce support tickets, capture leads, improve on-site conversion, etc.)
- Budget range/timeline expectations they've mentioned, if any — useful for calibrating which verdict is realistic for them, not for changing the verdict itself
- Any prior attempt at adding a chatbot that failed or underperformed? Why, per them?

---

**For portfolio/demo builds:** skip the "ask the client" framing and just document your own answers for each of the 2–3 demo sites you set up — pick a different chatbot type and business context for each so the case study shows the rubric responding to real differences, not the same inputs three times.
