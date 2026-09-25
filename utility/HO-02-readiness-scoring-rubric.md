---
type: HO
id: HO-078
title: Claude → OpenCode (deepseek-flash builder): Provision epm_test Database, Migrate Off Schema-Swap Isolation
date: 2026-07-16
from: Claude Web (The Brain / Architect)
to: Hermes deepseek-flash (builder, OpenCode CLI)
protocol: OpenAgile Hybrid Framework v1.0
priority: HIGH
---

# HO-078 — Provision `epm_test` (Option A), Retire Schema-Swap Test Isolation

## Decision

Zubbyik has chosen **Option A**: provision `epm_test` as a genuine
separate database within the existing shared PostgreSQL 15 instance,
matching the locked architectural decision as originally written, rather
than retroactively revising that decision to bless the
`estate_portfolio_test`-schema-within-`estate_portfolio` pattern
`conftest.py` currently uses.

Rationale (for the record): the schema-swap approach's entire safety
rests on `DB_TEST_SCHEMA`/`search_path` being set correctly on every run —
a mechanism that already failed once this session (that's what caused the
original `DB_HOST` breakage). A separate database removes that failure
mode structurally: there's no `search_path` to misconfigure, and no path
by which a broken test config silently touches `estate_portfolio` data
instead of erroring out loudly.

**This does not block the F-022 chatbot feature itself** — the backend
logic, the join fix, and the 29 unit tests are already correct and
approved on their own merits. This handover blocks Gate 2/PR only insofar
as the sector-regression integration test needs to run against the
correctly-isolated database before final sign-off, per the standing rule
that regression tests must prove what they claim to prove.

---

## Task 1 — Provision `epm_test`

1. Run `CREATE DATABASE epm_test;` on the existing shared PostgreSQL 15
   instance (**reuse the existing instance — do not create a new
   container/instance**, per the standing infra constraint).
2. Finish the abandoned `scripts/init-databases.sh` provisioning flow —
   investigate why it was never completed (raw output of what currently
   exists vs. what the script expects), then complete it so `epm_test`
   has the same schema/tables as production, via the same Alembic
   migration chain (`alembic upgrade head` against `epm_test`, not a
   hand-copied schema dump).
3. Confirm connectivity: a raw `psql` or equivalent connection test
   showing `epm_test` is reachable and has the expected tables.

## Task 2 — Migrate `tests/integration/conftest.py` off schema-swap

1. Remove the `DB_TEST_SCHEMA` / `search_path` `connect_args` mechanism
   entirely — tests should connect directly to `epm_test` as a distinct
   database, not to `estate_portfolio` with a schema override.
2. Update whatever env vars/config the test runner uses (`DB_HOST`,
   `DB_NAME`, etc.) to point at `epm_test` by default for integration
   runs. Keep the `connect_args`/`ASGITransport` fixes from HO-077 — those
   were unrelated bug fixes, not part of the schema-swap strategy, and
   remain correct regardless of which database is targeted.
3. Confirm no test code anywhere hardcodes `estate_portfolio_test` or
   assumes schema-based isolation going forward.

## Task 3 — Re-run the sector regression test against `epm_test`

1. Re-run `tests/integration/test_chatbot_sector.py` against the newly
   provisioned `epm_test` (not the old schema pattern).
2. Repeat the same before/after proof as HO-077 (join removed → test
   fails with cartesian-product evidence; join restored → test passes) —
   this time against the real isolated database, full raw pytest output,
   not summarized.
3. Run the full suite (unit + integration) once more and report raw
   pass/fail/xpass counts to confirm nothing else regressed from the
   conftest change.

## Migration-drift note

Two physical databases (`estate_portfolio`, `epm_test`) now need to stay
in sync on schema. Going forward, any new Alembic migration must be
applied to both — flag this explicitly in future HOs whenever a migration
ships, so it doesn't quietly drift the way other parallel-system state did
earlier this project.

---

## Reply format

Raw command output required per standing rule: `CREATE DATABASE`
confirmation, `init-databases.sh` completion evidence, the conftest diff,
and full before/after pytest output for the re-run regression test.

Reply as **HO-079** (next number in sequence — usual numbering caveat
applies until the Pre-assigned HO numbers table is updated).
