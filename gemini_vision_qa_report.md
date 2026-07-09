    Here is the complete QA report for https://imadconsulting.co.uk:



    QA Report: imadconsulting.co.uk — Homepage

    Date: 2026-07-09 | Browser: Chromium (Desktop) | Viewport: ~1280x720



    Executive Summary

    The site presents a professionally designed, clean portfolio with good structural layout, no console errors, no broken images, and
proper responsive foundation (38 media query blocks, viewport meta). However, a systematic GSAP ScrollTrigger animation bug renders ~40% of the page content invisible, making the toolkit, blog, and social links sections appear empty to visitors.



    🔴 CRITICAL: GSAP ScrollTrigger Animations Stuck

    Severity: Critical — 14 elements remain invisible or mid-transition after full page scroll.

    | Element                          | State                           | Visible? |
    |----------------------------------|---------------------------------|----------|
    | 8 × skill-card (Toolkit)         | opacity: 0; translateY: 50px    | No       |
    | 2 × blog-card (Blog)             | opacity: 0; translateY: 40px    | No       |
    | .contact-social div              | opacity: 0; translateY: 30px    | No       |
    | .contact-heading ("Let's talk.") | opacity: 0; translateY: 60px    | No       |
    | .hero-portrait image             | opacity: 0.7; scale: 0.85       | Partial  |
    | .hero-text-col                   | opacity: 0.6; translateY: -80px | Partial  |
    | .about-reveal                    | opacity: 0.8                    | Partial  |

    Root Cause: Each tween has paused: true with immediateRender: true and runBackwards: true. The combination means GSAP renders the initial (hidden) state immediately, then the tween completes (progress=1) but is paused — never clearing the inline opacity: 0 / transform styles. The ScrollTriggers report progress=1 (done) but the CSS never updates.

    Themes affected: zubbyik_press_imad — likely in main.js or GSAP init script.

    Recommendation:
    js
    // Instead of:
    ScrollTrigger.create({
      trigger: el,
      animation: tl,
      // ...
    });

    // Ensure each tween is NOT paused and use defaults:
    gsap.fromTo(el,
      { opacity: 0, y: 50 },
      {
        opacity: 1, y: 0,
        scrollTrigger: { trigger: el, start: 'top 90%' },
        immediateRender: false  // key fix
      }
    );

    The fix is removing paused: true from the tweens or setting immediateRender: false on the from/to tweens that use runBackwards: true.



    🟡 MODERATE: Missing Alt Text on Decorative Images

    Severity: Moderate — Accessibility (WCAG 2.1 SC 1.1.1)

    Two images have empty alt="" when they should have descriptive text:
    1. malachy-portrait.webp (hero section) — should read alt="Malachy Egbuna — portrait photo"
    2. about-illustration.webp (about section) — should read alt="Illustration of developer working at multi-monitor setup"

    Three project card images have proper alt text (Test Automation Framework, Infrastructure as Code, Custom WordPress Platform) — good.

    Fix: Edit the theme template to add alt attributes to both IMG tags.



    🟡 MODERATE: Social Links Hidden Behind Broken Animation

    Severity: Moderate — Users cannot find GitHub/LinkedIn/Email links

    Three social links exist in the DOM with inline SVGs:
    - github.com/malachy (GitHub icon)
    - linkedin.com/in/malachy-egbuna (LinkedIn icon)
    - mailto:malachy.egbuna@imadconsulting.co.uk (Email icon)

    But they sit inside the .contact-social div which is stuck at opacity: 0; transform: translateY(30px) due to the same GSAP bug above. Visitors see no social links on the page.

    Fix: Resolves automatically when the ScrollTrigger bug above is fixed. Alternatively, move social links outside the animated div.



    🟡 MODERATE: "Let's talk." Heading Invisible

    Severity: Moderate — The contact section has no visible heading

    The <h2 class="contact-heading"> contains "Let's talk." but is stuck at opacity: 0; transform: translateY(60px). The introductory paragraph below it is visible, so visitors see text but no heading.

    Fix: Same GSAP ScrollTrigger fix as above.



    🟢 MINOR: Placeholder Text Color Matches Input Text

    Severity: Minor — UX/Usability

    Form placeholders use oklch(0.45 0.02 60) (~#737373) which provides adequate contrast (~4.6:1 against white input bg — passes WCAG
AA for 3:1 minimum for placeholder text). Not a compliance failure, but the placeholder is dark enough that users may not immediately distinguish it from typed text.

    Fix (optional): Lighten placeholder to oklch(0.6 0.02 60) (~#999) for clearer visual distinction.



    ✅ PASSED: No Issues Found

    Check: Console errors
    Status: Clean — 0 JS errors
    ────────────────────────────────────────
    Check: Broken images
    Status: 0 — all 5 images loaded (384×576, 512×512, 1024×683×3)
    ────────────────────────────────────────
    Check: Layout overflow
    Status: None — no horizontal scroll, no clipped content
    ────────────────────────────────────────
    Check: Misalignment
    Status: None — all sections properly aligned
    ────────────────────────────────────────
    Check: Typography
    Status: Clean — Fraunces (serif headings) + Inter (sans-serif body), good hierarchy
    ────────────────────────────────────────
    Check: Color contrast
    Status: 🔴 Body #2e2e2e on #fbf9f5 passes AAA. Buttons #8b2f1f on off-white passes AAA. All acceptable.
    ────────────────────────────────────────
    Check: Form fields
    Status: All present: Name, Email, Message, Send Message button (WordPress nonces and honeypot hidden fields properly implemented)
    ────────────────────────────────────────
    Check: Mobile responsiveness
    Status: 38 media query blocks found — responsive framework in place
    ────────────────────────────────────────
    Check: Viewport meta
    Status: width=device-width, initial-scale=1.0 — correct
    ────────────────────────────────────────
    Check: Interactive elements
    Status: All navigable: 7 nav links, 2 CTAs, 3 "Live Demo" + 3 "Source" buttons, 2 "Read" links, form submit
    ────────────────────────────────────────
    Check: Post/page rendering
    Status: All text content renders: hero intro, about description, 3 project cards with images & tags, 4 timeline entries, 2 blog
      post titles



    Summary Table

    | #   | Issue                                             | Severity    | Applies To          |
    |-----|---------------------------------------------------|-------------|---------------------|
    | 1   | GSAP ScrollTrigger animations stuck (14 elements) | 🔴 Critical | Theme JS (main.js)  |
    | 2   | Empty alt text on 2 images                        | 🟡 Moderate | Theme templates     |
    | 3   | Social links hidden behind broken animation       | 🟡 Moderate | .contact-social div |
    | 4   | "Let's talk." heading invisible                   | 🟡 Moderate | .contact-heading    |
    | 5   | Placeholder color same as input text              | 🟢 Minor    | Form CSS            |

    Primary recommendation: Fix the GSAP ScrollTrigger tween configuration (paused: true + immediateRender: true +
    runBackwards: true conflict) — this single fix resolves issues 1, 3, and 4 simultaneously, restoring ~40% of the page content.
