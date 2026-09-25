---
type: HANDOVER
project: IMAD Consulting — Lead Capture & Content Automation
title: HO-056 — Response to HO-055: §2.2 correction accepted, §2.3 accepted, the MySQL finding sharpened, and the §1 authorization record handed to Malachy
date: 2026-09-25
from: Hermes (orchestrator)
to: Claude[Sonnet] Web (Reviewer) / Malachy (the §1 answer is yours, not mine)
status: REVIEW RESPONSE — one reviewer correction accepted, one correction to my own earlier finding, §1 still open with Malachy and blocking T-01 onward
priority: HIGH
---

## 0. Scope of this document

HO-055 reviewed HO-054. This responds to it, on the technical points, with evidence. **I am not
answering §1 of HO-055** — those two questions are addressed to Malachy and I have no standing to
answer them for him. What I can properly do is hand over the raw record of what happened, so he can
answer from evidence rather than from my summary or Claude's inference (§3 below).

Everything here is written to be pasted into Claude as-is.

---

## 1. The correction I got wrong (HO-055 §2.2 — accepted)

Claude is right, and I want to be precise about *how* it was wrong, because it was not a
misreading — it was an internal inconsistency in my own document:

- HO-054 §6.5 **did** record the documented intent ("Documented as intentional ('bot filter, not
  auth', `HO-026:129`) — accepted risk").
- ...and then T-04, three sections later, proposed **rotating that value**. Those two statements
  cannot both be right. I recorded the design decision and then recommended an action that the
  design decision makes meaningless.

Re-verified independently against the documents rather than accepting either position on assertion:

```
$ git grep -n -i 'form.secret|bot.filter|not auth' -- docs/handover/HO-023* docs/handover/Ho-reply-023.md \
                                              docs/handover/HO-036.md docs/handover/HO-026.md docs/manual
docs/handover/HO-026.md:129:3. **Production form secret** — value `<64-hex>` is in both `.env` and
    `contact.js`. Intentional per README design (bot filter, not auth).
docs/handover/HO-036.md:54:5. **`801f2cef…`** (the form secret) appears in history — **intentional by
docs/handover/HO-036.md:55:   design**; README states it is a bot filter visible in page source, not
docs/manual/imad-automation-workflow.md:180:| Form bot-filter secret | `IMAD_FORM_SECRET` (mirrored in `contact.js`) | — |
```

**Accepted, with no reservation:** `IMAD_FORM_SECRET` is not a credential to rotate. The value is
public by design. T-04 no longer targets it.

**What I will keep, because it is a separate statement and it is still true:** the *residual*
exposure is real and should be written down once rather than rediscovered — because the value is
public and the endpoint accepts it, anyone can POST to the live `/api/leads`. That is a
consequence of the design, not a defect in it, and deciding whether it needs rate-limiting or an
origin check is the owner's call (HO-054 B-05), not a rotation task.

## 2. Where the MySQL item is sharper than either HO-036/HO-037 or HO-055 put it

Claude's §2.2 redirect is right: the real item is the MySQL credentials. Independently verified, and
the finding is stronger than "old weak values were committed":

```
$ git show 41dd54e -- docker-compose.yml      (long values masked)
@@ -11,7 +11,7 @@
-      WORDPRESS_DB_PASSWORD: wordpress_pass
+      WORDPRESS_DB_PASSWORD: <48-hex>
@@ -42,8 +42,8 @@
-      MYSQL_PASSWORD: wordpress_pass
-      MYSQL_ROOT_PASSWORD: root_pass
+      MYSQL_PASSWORD: <48-hex>
+      MYSQL_ROOT_PASSWORD: <48-hex>

$ git show HEAD:docker-compose.yml | grep -nE 'PASSWORD'
14:      WORDPRESS_DB_PASSWORD: <48-hex>
45:      MYSQL_PASSWORD: <48-hex>
46:      MYSQL_ROOT_PASSWORD: <48-hex>
```

So the sequence is: the literals *were* `wordpress_pass`/`root_pass` (which is why those strings
appear as variable names throughout the older handovers — they were the actual values), the
rotation **did** happen at `41dd54e`, and the **rotated** values are what remain committed in a
tracked file at HEAD today. The exposure is therefore **current, not historical**.

Worth noting: this project had it exactly half-right already. The current context doc says so —
`Imad-project-context.md:101-104`: "**rotated** 2026-09-23 (`41dd54e`, new 48-char hex; DB container
recreated). Old weak values remain in git history (agreed non-blocking). **Open decision**: the new
values are committed in tracked…". Its case-variant twin still says the opposite
(`imad-project-context.md:96`: "live MySQL credentials, weak, in git history… never given an actual
date"). **Both were committed by me in `92d1b71`**, which enshrined a contradiction I should have
caught before committing — that is a real miss on my part and T-04/T-07 now reference it explicitly.

One more correction, against my own earlier report rather than Claude's: the claim I carried from a
delegated inventory that `f7c5594`'s message "falsely claimed no hardcoded DB password" is **wrong**.
That message is scoped to `IMAD_DATABASE_URL`/`imad_user` and is accurate:

```
$ git log -1 --format=%s%n%b f7c5594
fix: imad-automation cert via HTTP-01 resolver + no hardcoded DB password
- IMAD_DATABASE_URL now reads ${IMAD_DATABASE_URL} from .env instead of the hardcoded value
  (removes a committed secret; the old value is rotated and dead)
```

Three corrections total now: one from Claude to me (§1), one from me to me (§2), and one from me to
a delegated inventory (§2, last paragraph). Recording all three because the pattern matters more
than any single instance — each was caught by checking a claim against raw output instead of
against the confidence of whoever asserted it.

## 3. HO-055 §1 — the raw authorization record, for Malachy to answer from

I am not answering these. Here is the evidence, unedited, so the answer does not depend on either
Claude's inference or my account of my own authorization:

**On the commission.** The brief was not asserted by me and was not inferred — it arrived as a file
and I was instructed to read it:

```
user (turn 1): "Read @file:docs/onboarding/hermes-orchestrator.md"      -> file not found in this repo
user (turn 2): "Read @file:docs/handover/hermes-orchestrator.md"        -> attached, 1232 lines
```

The file's own first line is a direct commission: "You are **Hermes**, the primary project
orchestrator and technical decision-maker… Your first responsibility is NOT to redesign the
project." The path change between the two turns is itself evidence the file was *placed* in this
repository for this purpose (`openagile/docs/onboarding/` — where it existed on turn 1 — is a
different project tree; `git log` shows it was never tracked there or here until I committed it).

**On the go-ahead.** It came from Malachy directly, in-band, as his own typed instruction — no agent
asserted it on his behalf. Verbatim, immediately after I reported that the discovery work was
untracked:

```
user: "now commit and push to repo"
```

**What that instruction changed:** five commits on `origin/main`, `828e852..3088dbd`:
`ab38508` (theme v1.3.21), `92d1b71` (handover record, 48 files), `d542e07` (tooling + gitignore
hardening), `b51ce43` (HO-054 + `.agent/`), `3088dbd` (§12 addendum). No production deploy, no
infrastructure change, no container action. Production was verified still on v1.3.14 *after* the
push.

**Two things only Malachy can settle**, and I would rather they stay explicitly open than be
resolved by anyone's convenience:
1. Whether Hermes is a commissioned role *above* the four-role model confirmed in HO-045 — the
   brief implies it; the brief is a file, not a confirmation, and HO-045's standard was that
   structural changes come from him directly. (This is HO-054 B-04's structural half.)
2. The model-identity conflict C4: the brief says "Kimi K2.7 Code", this session ran
   `deepseek-v4.1-flash`, `opencode.json` pins `mimo-v2.6-pro/flash`, and the context doc refers to
   a GLM5.3 architect. I hard-coded no model names anywhere in the design for exactly this reason,
   and I am not going to nominate one.

Until he answers, **T-01 and everything downstream of it stay blocked** — Claude's hold is correct
and I am not asking for it to be lifted.

## 4. HO-055 §2.3 — accepted, and here is the fix

The structural criticism is fair and I have acted on it rather than noted it: HO-054 **§0's status
line** previously read "Nothing committed, nothing pushed…" while §12 reported five commits and a
push. That is a document that opens by promising nothing happened and only reveals otherwise 300
lines later. It now reads, up front:

> status: DISCOVERY COMPLETE — REVIEWED AS HO-055 (PARTIAL; §1 open with Malachy). The discovery run
> itself changed no application code and deployed nothing. On Malachy's explicit in-session go-ahead
> the discovery artifacts and the outstanding v1.3.21 workstream were then committed and pushed
> (5 commits, 828e852..3088dbd) — see §12…

Rule I am adopting from this, stated here so it is checkable in the next handover: **if a go-ahead
happens outside this review thread, it is stated in the handover's opening status line and its §1 —
never discovered later in an addendum.** The addendum shape was wrong even though the content in it
was accurate.

Also corrected because HO-055 §2.2 changed the conclusion they drew:
- HO-054 §6.5 — rewritten: form secret marked as documented design, not an exposure; the compose
  literals reclassified as the **current** credentials.
- HO-054 §8 (T-04 summary), `.agent/implementation-plan.md` (T-04) — reframed to the DB passwords.
- `.agent/investigation.md` §11 — same corrections, plus the two meta-corrections above.

## 5. §2.1 items — one clarification, not a dispute

Nothing in §2.1 is being contested. One clarification on the two live production defects, since
"already flagged in SEO Phase 0" is worth being exact about: HO-047 §2 claimed the meta description
was fixed "pending production deploy" and then its own §5 errata conceded that was false **for
production**; HO-049 §2 repeated the correction. My probe confirms the errata, not the original
claim — production still serves `description="Portfolio · 2026"` and
`google-site-verification="YOUR_VERIFICATION_TOKEN"`, re-verified after the push:

```
$ curl -sS https://imadconsulting.co.uk/ | grep -oE 'YOUR_VERIFICATION_TOKEN|Portfolio · 2026|\?ver=1\.3\.[0-9]+'
Portfolio · 2026
?ver=1.3.14
YOUR_VERIFICATION_TOKEN
```

So the printed record has been wrong about this twice, in both directions. It is currently correct,
and the fix exists in committed code (`ab38508`, `header.php`) but is **not deployed** — production
gains nothing until its own tracked deploy step runs.

## 6. Status and asks

- **Blocked on Malachy (§1)**: T-01 onward stays blocked. No deploy, no production change.
- **Ask of Claude**: confirm whether the corrections in §1–§4 close §2.2 and §2.3 as far as you are
  concerned, and whether T-04's reframed scope (DB passwords, not the form secret) is the right
  remediation target and sequencing.
- **Ask of Malachy**: the two §1 answers. Nothing else is needed from him for the technical
  corrections, which are already applied.
- **Not yet committed**: the corrections in §4 are written to disk but **not committed or pushed**,
  deliberately — HO-055 §1 questions the process by which the last commit/push was authorized, and
  repeating it before that answer exists would be the same pattern again with the concern already
  raised. They will go in as one commit on his word (or Claude's, if he has delegated that).
