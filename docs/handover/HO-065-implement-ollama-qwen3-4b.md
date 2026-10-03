# ENVIRONMENT FACTS (verified 2026-10-03 — treat as authoritative, re-verify only if a command contradicts them)

Host: Ubuntu 24.04 VPS, 7.8 GiB RAM total (~4.9 GiB available idle), 2 GiB swap already partly in use.
  Co-resident: MariaDB/MySQL, n8n, FastAPI, Hermes sessions. RAM is the binding constraint.
Hermes: host install at ~/.hermes/hermes-agent (git, v0.21.5+2168.g59004a6), state in ~/.hermes,
  gateway runs as systemd user service `hermes-gateway`. Tool execution is on the HOST.
  A Docker container `hermes` (compose project at ~/openagile/hermes) shares ~/.hermes but is
  redundant/stopped — IGNORE it and do not modify it. Do NOT run `hermes update`.
Ollama: 0.10.1, listens on 127.0.0.1:11434 (host loopback). Do NOT change its bind address.
  Worker model tag: `qwen3:4b-8k` (derived from qwen3:4b, num_ctx 8192, temperature 0.1).
  Never use any -64k tag (needs 11.3 GiB; fails to load), qwen3:8b, or deepseek-r1.
Hermes already talks to local Ollama via the OpenAI-compatible /v1 endpoint
  (see ~/.hermes/context_length_cache.yaml). No new provider code should be needed.
Existing config: ~/.hermes/config.yaml has `auxiliary:` with only a `vision` slot,
  `plugins.enabled: []`, and secret redaction ON by default.
Existing Hermes code to READ FIRST and REUSE (do not reimplement):
  agent/AGENTS.md, agent/auxiliary_client.py, auxiliary_hooks.py, auxiliary_structured_output.py,
  aux_accounting.py, auxiliary_fallback_recovery.py, bounded_response.py, api_request_hooks.py,
  and the plugin mechanism (plugins/). Locate where tool results are appended to model context.

# PHASE 0 GATE (mandatory, before any code)
Report, with raw command output: (1) the exact function/hook where tool results enter model
context, (2) whether auxiliary_client.py supports a new task slot with custom base_url/model,
(3) whether a plugin or hook can wrap tool output without patching core files,
(4) the integration point you propose, ranked: existing hook/aux task slot > plugin > minimal
wrapper. Prefer anything that survives `hermes update` (no edits to files under hermes-agent/).
STOP and wait for approval after Phase 0. Do not implement before approval.

# CONSTRAINTS ADDED TO THE ORIGINAL SPEC

A. Runtime safety
- Model `qwen3:4b-8k` only. Concurrency 1. keep_alive short (default 2m, configurable).
- Memory preflight before every call: read MemAvailable from /proc/meminfo. If below a
  configurable floor (default 3072 MiB), skip the worker, use raw, log status=skipped_low_memory.
  Never trigger a model load under memory pressure.
- Qwen3 thinking: append `/no_think` to the prompt AND strip any <think>...</think> block from
  the response. Empty/malformed/over-short response => raw fallback.
- Request timeout configurable (default 120s). On timeout/connect error => raw fallback.
- If input exceeds the 8k context budget: first apply deterministic reduction (collapse repeated
  lines with counts; keep head/tail and every ERROR/FAIL/Traceback line with context). If still
  too large, skip the worker and use raw. Never truncate silently.
- Run AFTER Hermes' secret redaction, never before.

B. Deterministic first
- pytest/ruff/tsc/eslint/vite/composer output: parse with regex or native JSON reporters. Use
  Qwen only for unstructured logs (docker/traefik/php/systemd), diffs and search compression.
- Any LLM summary of test output: passed/failed/skipped counts must match a regex pass over the
  raw text. Mismatch => discard summary, use raw, log status=count_mismatch.

C. Raw-evidence handle
- Persist raw output to a file (configurable dir, outside git, size/age capped).
- Injected context must carry the marker:
  "LOCAL SUMMARY (machine-generated, may be incomplete) — raw output at <path>"
  so the cloud model can read/grep the raw file when the summary looks wrong.

D. Rollout
- mode: off | shadow | active. Default: off. `shadow` runs the worker and logs metrics but passes
  RAW output through unchanged. Do not enable `active` until shadow metrics are reviewed by me.
- Log per call: task_type, input_chars, output_chars, compression_ratio, duration_ms, status,
  fallback_used, MemAvailable before/after. Also log worker latency vs raw pass-through.
  Character-based metrics only; label them as such.
- During the shadow demo, capture `ollama ps` and `free -m` before, during and after a call.

E. Evidence and process
- Report raw command output, not narrated summaries. Full 40-char commit SHAs only.
  Commit before proceeding; a change that exists only on disk is not complete.
- Do not touch: WordPress Business Checkup code, lead API, DB schema, n8n, the Docker `hermes`
  container, Ollama service config, or any EPM v2 service.
- Config lives in the existing Hermes config mechanism. Roll back by setting mode: off
  (and removing the plugin/slot entry); document the exact lines.

# Task: Integrate Ollama Qwen3 4B as a Local Mechanical-Task Worker

## Objective

Modify the existing Hermes Agent environment so that the locally available Ollama model:

```text
qwen3:4b
```

can handle low-complexity, high-volume codebase tasks before their output is passed to the stronger opencode/cloud models.

The goal is **not** to replace the existing cloud reasoning model.

The goal is to reduce unnecessary cloud-model context consumption by using the local Qwen3 4B for mechanical preprocessing.

Do not introduce n8n or another orchestration platform.

---

# 1. First: Inspect Before Modifying

Before writing code, inspect the existing Hermes installation and determine:

1. Hermes version.
2. Current provider/model configuration.
3. How Hermes invokes models.
4. How Hermes receives tool output.
5. Whether Hermes already has:
   - middleware/hooks
   - tool-output processors
   - context compaction
   - summarization
   - subagents
   - local-provider support
   - configurable model routing.
6. Whether Ollama is reachable from the Hermes runtime.
7. Whether the existing configuration already supports OpenAI-compatible endpoints.
8. Where Hermes stores configuration and runtime state.

Do not assume an implementation mechanism.

Use the existing Hermes architecture wherever possible.

Do not create a second orchestration framework.

Do not duplicate functionality that Hermes already provides.

---

# 2. Local Model

The local worker must use:

```text
Ollama
Model: qwen3:4b
```

Prefer the existing Ollama API rather than shelling out to the `ollama` CLI for every request.

Determine the actual Ollama endpoint from the environment and existing configuration rather than hard-coding an incorrect address.

Typical local endpoint:

```text
http://127.0.0.1:11434
```

but verify this first.

The implementation must make the Ollama endpoint configurable.

Example configuration concept:

```yaml
local_worker:
  enabled: true
  provider: ollama
  model: qwen3:4b
  base_url: ...
```

Use the actual Hermes configuration format and conventions rather than blindly adding this exact YAML structure.

---

# 3. Responsibility of Qwen3 4B

Qwen3 4B is a **mechanical preprocessing worker**.

It may perform:

### A. Test-output summarization

Input:

```text
raw pytest / Playwright / Node / npm test output
```

Output:

```text
- tests passed
- tests failed
- tests skipped
- failing test names
- relevant error messages
- relevant stack-trace locations
- repeated/noisy lines removed
- likely duplicate failures grouped
```

### B. Build/log summarization

Support outputs such as:

```text
Docker logs
Traefik logs
FastAPI logs
PHP logs
Vite logs
TypeScript compiler output
ESLint/Ruff output
systemd logs
```

Extract:

```text
ERROR
WARNING
failed operation
affected component
file/location when available
relevant surrounding lines
```

### C. Git diff summarization

Given a git diff, produce:

```text
Files changed
Functions/components affected
Type of change
Tests associated with the change
Potentially noteworthy changes
```

Do not ask Qwen3 4B to approve the change.

### D. Search-result compression

When Hermes performs broad repository searches, Qwen3 4B may compress repetitive search results into:

```text
file
location
relevant match
why it appears relevant
```

### E. Failure clustering

If a test run contains many failures, group failures that appear to share the same immediate technical cause.

Example:

```text
18 failures

Cluster 1:
14 failures caused by missing Business Checkup route.

Cluster 2:
3 failures caused by mobile click interception.

Cluster 3:
1 independent assertion failure.
```

The cloud model must still receive enough raw evidence to independently reason about the actual cause.

---

# 4. Do NOT Delegate These Tasks to Qwen3 4B

Never automatically use the local model for:

- architecture decisions
- security decisions
- authentication/authorization design
- database schema design
- migrations
- production code generation
- complex debugging
- interpreting ambiguous requirements
- deciding whether a proposed fix is correct
- deciding whether a test is sufficient
- approving deployment
- evaluating cloud-model output
- making irreversible changes

The cloud reasoning model remains authoritative for those tasks.

---

# 5. Preserve Raw Evidence

This is mandatory.

Never replace raw tool output permanently with an LLM-generated summary.

The workflow should conceptually be:

```text
Tool
 │
 ├───────────────> Raw output retained
 │
 ▼
Qwen3 4B
 │
 ▼
Structured summary
 │
 ▼
Hermes / cloud reasoning model
```

If the local summarizer fails, times out, returns malformed output, or produces a suspiciously small summary:

```text
DO NOT lose the raw output.
```

Fall back to the original raw output.

---

# 6. Structured Output

Prefer structured output from Qwen3 4B rather than unrestricted prose.

For example:

```json
{
  "type": "test_result",
  "summary": "...",
  "passed": 61,
  "failed": 2,
  "skipped": 1,
  "failures": [
    {
      "test": "...",
      "error": "...",
      "location": "..."
    }
  ],
  "warnings": [],
  "clusters": []
}
```

Adapt this to Hermes' existing data structures if appropriate.

Do not invent a new schema if Hermes already has a suitable internal representation.

The output must remain useful to a stronger model as context.

---

# 7. Size-Based Triggering

Do not send every tiny tool result through Qwen3 4B.

Small outputs should pass through normally.

Introduce a configurable threshold based on actual token/character size.

For example:

```text
small output
    ↓
directly to Hermes/cloud model

large repetitive output
    ↓
Qwen3 4B
    ↓
compressed output
    ↓
cloud model
```

Do not choose an arbitrary threshold without first inspecting Hermes' existing context/token handling.

Make the threshold configurable.

---

# 8. Confidence / Safety Behaviour

The local worker is an optimization, not an authority.

If Qwen3 4B cannot confidently summarize the input:

```text
preserve raw output
```

and allow Hermes to use the raw evidence.

Never allow:

```text
Qwen3 4B hallucinated summary
        ↓
cloud model assumes it is fact
```

The summary should clearly distinguish:

```text
observed facts
```

from:

```text
possible interpretation
```

For test and diagnostic output, prioritize extraction over interpretation.

---

# 9. Failure Handling

The local worker must never become a single point of failure for Hermes.

If Ollama is:

- unavailable
- overloaded
- out of memory
- timing out
- returning HTTP errors
- returning invalid output
- returning an unexpectedly empty response

Hermes must continue operating using the existing cloud-model path.

The local worker should therefore behave like:

```text
optional optimization
```

rather than:

```text
required dependency
```

---

# 10. Resource Constraints

The available machine has limited resources.

Do not configure multiple local models to remain resident unnecessarily.

Use:

```text
qwen3:4b
```

as the initial local worker only.

Do not automatically load:

```text
qwen3:8b
deepseek-r1:7b
```

for this feature.

Do not introduce local parallel inference that could starve the rest of the server.

Keep concurrency conservative and configurable.

---

# 11. Observability

Add enough logging to determine whether the optimization is actually saving cloud context.

For every summarization operation, record locally:

```text
worker=qwen3:4b
input_size
output_size
compression_ratio
duration
success/failure
fallback_used
task_type
```

Do NOT log secrets, API keys, authentication headers, credentials, or sensitive user data unnecessarily.

Example:

```text
local_worker task=test_output
input_chars=18420
output_chars=3240
compression_ratio=0.176
duration_ms=1840
status=success
```

This allows us to measure whether the feature is worthwhile.

---

# 12. Token-Saving Measurement

Add a way to compare:

```text
raw tool output size
```

against:

```text
summarized output size
```

The first implementation does not need perfect token accounting if Hermes already has no tokenizer available.

Character counts are acceptable initially.

Clearly label the metric as character-based rather than token-based.

Do not claim that:

```text
18,000 characters = X tokens
```

unless an actual tokenizer is being used.

---

# 13. Configuration

Do not hard-code:

- Ollama URL
- timeout
- threshold
- concurrency
- model name
- enable/disable state

Use Hermes' existing configuration mechanism.

The default behaviour should be conservative.

If enabling the worker could change current production behaviour unexpectedly, make it explicitly configurable and initially disabled until tests demonstrate safe fallback behaviour.

---

# 14. Testing

Follow RED → GREEN.

First create tests demonstrating the required behaviour.

At minimum test:

### Test 1 — small output bypass

Small tool output does not invoke Ollama.

### Test 2 — large output invokes worker

Large repetitive tool output invokes Qwen3 4B.

### Test 3 — successful compression

Ollama response is passed back to Hermes in the expected form.

### Test 4 — Ollama unavailable

Hermes falls back to the original raw output.

### Test 5 — Ollama timeout

Hermes falls back to raw output.

### Test 6 — malformed response

Hermes falls back to raw output.

### Test 7 — secrets are not logged

Authentication/API credentials must not appear in worker diagnostics.

### Test 8 — raw evidence remains available

The original tool output is retained even after summarization.

### Test 9 — configuration disabled

Existing Hermes behaviour remains unchanged when the worker is disabled.

### Test 10 — compression metrics

Input/output sizes and duration are recorded correctly.

Use mocks for Ollama in unit tests.

Do not make the normal unit-test suite dependent on a live Ollama server.

If an integration test is useful, make it explicitly opt-in.

---

# 15. Practical Demonstration

After implementation, do NOT simply report:

```text
Qwen3 4B integration complete.
```

Demonstrate it using the actual Hermes/codebase environment.

Run a representative command that produces substantial output.

For example, use an existing test suite or diagnostic command that produces enough output to justify summarization.

Show:

```text
RAW OUTPUT SIZE
↓
QWEN3 4B PROCESSING
↓
SUMMARY SIZE
↓
COMPRESSION
↓
FINAL HERMES INPUT
```

Also demonstrate the failure path by temporarily making Ollama unavailable or mocking the failure.

Show that Hermes continues using the original raw output.

---

# 16. Do Not Modify Unrelated Systems

This task is limited to integrating the local Qwen3 4B mechanical worker.

Do not modify:

- WordPress Business Checkup implementation
- Business Checkup scoring
- contact flow
- lead API
- database schema
- Docker infrastructure unrelated to Hermes
- n8n
- production application code
- existing opencode/cloud model configuration unless required for routing integration.

Do not create another agent framework.

Do not introduce another model provider unnecessarily.

---

# 17. Final Deliverable

At completion provide a concise handover containing:

## Implementation

- files created
- files modified
- Hermes integration point
- Ollama integration point
- configuration added

## Supported Tasks

List exactly which tool-output types are currently summarized.

## Routing

Explain precisely:

```text
what stays local
what goes to opencode/cloud models
what happens on failure
```

## Tests

Provide the actual commands executed and their raw output.

Do not merely state "tests passed".

## Demonstration

Show one real before/after example:

```text
RAW
...
```

```text
QWEN3 4B SUMMARY
...
```

Include actual measured:

```text
input size
output size
compression ratio
processing time
```

## Rollback

Document exactly how to disable the local worker and return Hermes to its previous behaviour.

## Limitations

Document anything that remains intentionally unsupported.

---

# Success Criterion

The feature is successful only if it achieves this architectural outcome:

```text
                  HERMES
                    │
             executes tool
                    │
                    ▼
              tool output
                    │
          ┌─────────┴─────────┐
          │                   │
       small               large
          │                   │
          │                   ▼
          │             Ollama Qwen3 4B
          │                   │
          │             compressed facts
          │                   │
          └─────────┬─────────┘
                    ▼
             reasoning context
                    │
                    ▼
             opencode/cloud model
                    │
                    ▼
              actual reasoning
```

The local model is therefore used to **reduce repetitive context consumption**, while the stronger cloud model remains responsible for engineering judgment.

Before implementing anything, inspect the Hermes architecture and existing configuration and state the exact integration point you intend to use. Then follow RED → GREEN → implementation and provide the actual test output.
