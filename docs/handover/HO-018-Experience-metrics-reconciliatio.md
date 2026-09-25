# Content Reconciliation Package — Experience, About Metrics, Testimonials

## 1. Canonical Experience — use identically in all three places

This replaces the seeder data (`data-seeder.php`), the fallback in
`section-experience.php`, and the chatbot's static Q&A in `ai-chat-bot.php`.
Content is the same blend we built from your actual resume earlier —
matches the "7 seeded entries" the audit found, so this should map
directly onto the existing 7 CPT posts without needing new ones created.

### Full 7-entry version → `data-seeder.php` (and whatever currently
populates the 7 `experience` CPT posts)

1. **IMaD Consulting — Founder & Systems/QA Consultant**
   2020 – Present
   Run a small consultancy covering business email setup, DNS security,
   and web support, alongside AI-assisted automation work. Currently
   building and maintaining a self-hosted portfolio-tracking application
   end to end — backend, deployment, and testing — using a spec-first
   workflow where AI coding tools handle implementation and every change
   gets reviewed before it ships.
   *Tags: Email & DNS · Docker · Spec-first AI workflows*

2. **Imad Consulting (clients: zubbystudio, Okra Technology) — Test Analyst / UAT**
   March 2017 – October 2020
   Documented functional requirements and wrote regression tests for an
   ERP platform. Worked directly with business owners to turn their needs
   into test cases, and ran the user-acceptance process that caught
   serious defects before they reached production.
   *Tags: UAT · Regression testing · Requirements*

3. **Imad Consulting (client: Fitzdanuk.org) — Test Analyst / Front-End Development**
   July 2015 – March 2017
   Owned testing end-to-end for a tax reporting system — functional,
   regression, and integration testing, plus exploratory testing. Wrote
   SQL to validate data directly against the database and ran load tests
   before major releases.
   *Tags: SQL · Load testing · Exploratory QA*

4. **Planixs (clients: Barclays, RBS, Vodafone, Zenith Bank) — Test Analyst**
   July 2015 – August 2016
   Turned tickets into test cases for a liquidity-reporting system, using
   Selenium and Cucumber alongside manual testing. Wrote API scripts to
   verify backend data matched what the front end displayed.
   *Tags: Selenium · Cucumber · API testing*

5. **Parcel Force / NatWest / Quick Light — Test Analyst**
   June 2012 – August 2016
   A run of UK test-analyst roles: turning requirements into test plans,
   running smoke/regression/UAT cycles, and reporting clear results back
   to the business.
   *Tags: Test planning · UAT · Regression*

6. **British Telecoms — Desktop Support Engineer**
   June 2008 – December 2012
   Supported a Windows server migration from 2003 to 2008 and helped
   bring order to a support environment under heavy day-to-day demand.
   *Tags: Windows Server · Migration · Support*

7. **Admiral Insurance — Network Administrator**
   May 2001 – October 2004
   Rolled out and configured desktop systems for staff, and helped set up
   regular knowledge-sharing between support staff to speed up
   problem-solving.
   *Tags: Desktop admin · Knowledge sharing*

### Trimmed 3-entry version → `section-experience.php` fallback

(Only renders if the CPT is ever empty — kept aligned so it can never
contradict the real data again.)

1. **Founder & Systems/QA Consultant** — IMaD Consulting — 2020–Present
   Consultancy work spanning email/DNS security, web support, and
   building a self-hosted application with a spec-first, AI-assisted
   workflow.
2. **Test Analyst / UAT** — Imad Consulting, Planixs, and others — 2012–2020
   A decade of UK test-analyst roles: requirements into test cases,
   regression and UAT cycles, clear reporting back to the business.
3. **Desktop Support & Network Administration** — British Telecoms, Admiral Insurance — 2001–2012
   Hands-on support and infrastructure work — server migrations, desktop
   rollouts, and the fundamentals everything since has built on.

### Chatbot static Q&A → `ai-chat-bot.php`

Replace the "2024 — Now: Senior QA Engineer" line with:

> "I've run IMaD Consulting since 2020, covering email/DNS security, web
> support, and AI-assisted development. Before that, I spent close to
> two decades in QA and IT support roles across UK finance, telecoms,
> and insurance — Planixs, British Telecoms, Admiral Insurance, and
> others."

---

## 2. About Section Metrics — qualitative replacement

**Original (unverifiable, dropped per your instruction):**
`8+ Years in QA` / `40+ Projects shipped` / `99.9% Uptime targeted`

**Final numbers, as provided:**

| Label | Copy |
|---|---|
| **10+** | Years in QA |
| **20+** | Projects shipped |
| **99.9%** | Uptime targeted |

*Same layout as the original site copy — numbers only changed from
40+ → 20+ (projects) and 8+ → 10+ (years) per your confirmed figures.
99.9% uptime kept as-is since you confirmed it as a real target, not an
invented stat.*

---

## 3. Testimonials — from PeopleWorkPerHour (Upwork ones unavailable — account closed, not on record here)

Four usable reviews had actual written feedback attached (one — Ab. B.'s
meta-search-engine job — had a 5-star rating but no written quote, so it's
excluded until/unless you have the text). **Greg W.'s entry is flagged
below and held out pending your confirmation** — see note above.

### Ready to use — PeopleWorkPerHour

**1. Nicholas R. — London, GB**
> "Personal and on-hand."
*Project: Google AdWords event snippet implementation, Shopify store · Rating: 5/5 · Aug 2018*

**2. Mimi R. — Zagreb, HR**
> "Efficient and wonderful help! :)"
*Project: Shopify store setup (payment gateways, tax configuration) · Rating: 5/5 · Nov 2017*

**3. Lee J. — London, GB**
> "We did run into difficulties but turned out to be a server side issue. Certainly can't question Malachy's work ethic, and professionalism."
*Project: Web.Config configuration, 301 redirects · Rating: 4/5 · Nov 2017*

**4. Greg W. — Birmingham, GB**
> "Great!"
*Project: WordPress edits · Rating: 5/5 (corrected — platform data-entry quirk, confirmed) · Oct 2017*

### Ready to use — Upwork (relayed from memory since the account is closed; not independently verifiable against the original listing, but treated as accurate per your confirmation)

**5. Mkenny Properties**
> "Malachy helped us migrate our property management website from IONOS (1&1) to InMotion Hosting. He also transferred our business email during the move and resolved an initial deliverability issue that came up — everything's been running smoothly since."
*Project: Website migration & email hosting setup*
**→ Directly matches Offer #7 (Website + Email Migration) and Offer #1 (Email Deliverability) — strongest testimonial for the current service lineup. Feature this one prominently.**

**6. Tig Michael**
> "Malachy tested our website thoroughly and caught bugs we hadn't noticed ourselves — issues that could have turned into costly problems down the line. Glad we had a second set of eyes on it before launch."
*Project: Website testing & bug fixes*
**→ Best match for the QA/testing capability referenced in the About metrics.**

**7. Dimitry V.**
> "Malachy helped fix a stubborn 301 redirect error on my site. It took some back-and-forth with my hosting provider's support team, but he stayed frank and transparent throughout the process and got it resolved."
*Project: 301 redirect error troubleshooting*
**→ General reliability/troubleshooting signal — no direct offer match, use as a general testimonial.**

**8. Stanley H.**
> "Malachy integrated Stripe into my Shopify store. Professional from start to finish — clear communication and delivered right on schedule."
*Project: Stripe payment gateway integration (Shopify)*
**→ No direct offer match currently sold — general web dev capability signal.**

**9. Gracilis (Dishusbandmata)**
> "Malachy built my website from the ground up: logo design, layout, and all the content. He was genuinely open to learning throughout, and still delivered on schedule. Really happy with the result."
*Project: Full website build — design, logo, content & layout*
**→ Confirmed trimmed: "it was his first full client project" removed, rest kept as-is per your approval.**

### Not usable

**Ab. B.** — 5-star rating on the meta-search-engine job, but no written quote was captured — excluded until you have the actual text.

---

## 4. WhatsApp Number — confirmed fix

WhatsApp number should match the phone number: **`2348164162816`** (i.e.
the `wa.me` link becomes `https://wa.me/2348164162816`, same digits as
the phone field minus the `+`). Hand this to your builder as a one-line
settings fix — the current `2348162816` value is wrong.
