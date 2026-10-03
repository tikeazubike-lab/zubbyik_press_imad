---
type: HANDOVER
project: Hermes Agent — local model integration
title: "HO-066 — Ollama qwen3:4b worker: PHASE 0 GATE recon + proposed integration point"
date: 2026-10-03
from: Hermes (implementation agent, host session)
to: Malachy / architecture review (Claude Web)
status: PHASE 0 — AWAITING APPROVAL, NOTHING IMPLEMENTED
priority: high
source: HO-065 (implement Ollama qwen3:4b as a local mechanical-task worker)
---

# HO-066 — Phase 0 gate report

HO-065 defines a mandatory gate: report four things with raw command output, propose a
ranked integration point, then **STOP and wait for approval**. This document is that
report. **No code was written, no file in the Hermes install was modified, no
configuration was changed.** Verification of that claim is in §9.

Environment facts in HO-065 were treated as authoritative and re-verified only where a
command contradicted them. One item did (§3.4).

---

## 1. The four gate answers

### 1.1 Where tool results enter model context — exact function/hook

There are **two sanctioned seams**, both plugin hooks, both documented as running
*before the result enters context*.

**Registry tools** — `model_tools.py:848-865`:

```python
def _apply_transform_tool_result_hook(function_name: str, function_args: Dict[str, Any], result: Any, duration_ms: int,
                                      ids: _CallIds) -> Any:
    """transform_tool_result: plugins may replace the final result string.

    Runs after post_tool_call and before the result enters context. Fail-open;
    first string return wins. Gated on has_hook so the no-listener path is cheap.
    """
    ...
        hook_results = invoke_hook("transform_tool_result", tool_name=function_name, args=function_args,
                                   result=result, **ids.hook_kwargs(), duration_ms=duration_ms,
                                   status=status, error_type=error_type, error_message=error_message)
        return next((r for r in hook_results if isinstance(r, str)), result)
```

Called from `handle_function_call` at `model_tools.py:959`; the string it returns is what
becomes the tool message content.

**Terminal output** — `tools/terminal_tool_result.py:137`:

```python
def _apply_output_transform_hook(command, output, returncode, task_id, env_type) -> str:
    """Plugin output-transform seam (fail-open; first string result wins).
    Replacements are still subject to the output limit applied afterwards."""
    ...
        results = invoke_hook("transform_terminal_output", command=command, output=output,
                              returncode=returncode, task_id=task_id or "", env_type=env_type,
                              tool_call_id=_approval_tool_call_id.get())
```

Also fired for background-process output at `tools/process_registry.py:2557`
(poll/wait/log/kill).

**Agent-level ("inline") tools** reuse the same helper rather than reimplementing it —
`agent/inline_tool_executors.py:61 apply_transform_tool_result()`, applied at
`agent/tool_executor.py:1781-1782` (`if not transform_applied:`) and
`agent/agent_runtime_helpers.py:2446`, guarded by the `transform_applied` flag
(`tool_executor.py:1634`, `:1699`) so the hook fires exactly once per call.

Downstream of all three, the result is appended to `messages` (`agent/turn_tool_round.py`,
`agent/tool_executor.py:_publish_sequential_result`).

### 1.2 Does `auxiliary_client.py` support a new task slot with custom base_url/model?

**Yes — first-class, and a plugin can declare the slot itself.**

`agent/auxiliary_client.py:6049`:

```python
def _resolve_task_provider_model(
    task: str = None, provider: str = None, model: str = None, base_url: Optional[str] = None,
    api_key: Optional[str] = None,
) -> Tuple[str, Optional[str], Optional[str], Optional[str], Optional[str]]:
    """Determine (provider, model, base_url, api_key, api_mode) for a call.

    Priority: explicit args > config auxiliary.{task}.* > "auto". ...
```

Per-task keys accepted (`:6050-6075`): `provider`, `model`, `base_url`, `api_key`,
`key_env` / `api_key_env`, `api_mode`, `timeout`, plus per-task `fallback_chain`
(`:4348`). Entry point for text tasks is `get_text_auxiliary_client(task=...)` (`:5461`).

The existing config already contains a working example of the exact shape required
(`~/.hermes/config.yaml`, secret values redacted by me, not by Hermes):

```
  auxiliary.vision.provider: openrouter
  auxiliary.vision.model: gemini-2.5-flash
  auxiliary.vision.base_url: https://generativelanguage.googleapis.com/v1beta/openai
  auxiliary.vision.timeout: 120
  auxiliary.vision.context_length: 1000000
```

And `_get_auxiliary_task_config` (`:6136-6158`) merges **plugin-registered defaults**
over the config block:

```python
        from hermes_cli.plugins import get_plugin_auxiliary_tasks
        for _entry in get_plugin_auxiliary_tasks():
            if _entry.get("key") == task:
                _defaults = _entry.get("defaults") or {}
                    return {**_defaults, **task_config}
```

Registration API — `hermes_cli/plugins.py:881`:

```python
    def register_auxiliary_task(
        self, key: str, *, display_name: str, description: str,
        defaults: Optional[Dict[str, Any]] = None,
    ) -> PluginRegistration:
        """Register an auxiliary LLM task with its own ``auxiliary.<key>`` config block (picker entry,
        ``AUXILIARY_<KEY>_*`` env bridge, defaults merged into loaded configs). ``defaults`` may
        override provider/model/base_url/api_key/timeout/extra_body (unknown keys kept verbatim).
        Raises ``ValueError`` for an empty/invalid key, a built-in key, or another plugin's key."""
```

So no new provider code is needed, confirming HO-065's claim about the transport — with
one caveat recorded in §3.4.

**There is no `ollama` provider profile** (`providers/` contains only `base.py`,
`__init__.py`, `README.md`), so this is a `custom`/auto route plus a `base_url`. That is
the established pattern — Hermes already reaches Ollama this way:

```
agent/image_routing.py:312:        base_url = "http://localhost:11434/v1"
hermes_cli/models_local.py:116:    return _ollama_host_from_env(env_host) if env_host else "http://localhost:11434"
hermes_cli/runtime_provider.py:1043:    # endpoint (e.g. Ollama at localhost:11434), route through the OpenAI-compatible resolver instead of
```

Live check of the endpoint and the model tag:

```
$ curl -s http://127.0.0.1:11434/v1/models | python3 -c "..."
  ids: ['qwen3:4b-8k']
```

### 1.3 Can a plugin or hook wrap tool output without patching core files?

**Yes.** `transform_tool_result` is in the registerable hook name set, and plugin return
values are consumed as replacements:

```
hermes_cli/plugins.py:109:            "pre_tool_call", "post_tool_call", "transform_terminal_output", "transform_tool_result",
hermes_cli/plugins_dispatch.py:43:   "post_tool_call", "transform_terminal_output", "transform_tool_result", "transform_llm_output",
hermes_cli/plugins_activation.py:26: "transform_llm_output", "transform_tool_result", "transform_terminal_output", "pre_gateway_dispatch",
```

Registration — `hermes_cli/plugins.py:930`:

```python
    def register_hook(self, hook_name: str, callback: Callable) -> PluginRegistration:
        """Register a lifecycle hook callback (unknown names warn but are still stored)."""
```

Discovery and the "never touch core" rule are stated by the framework itself
(`plugins/AGENTS.md`):

> ## Plugins never touch core (Teknium, May 2026)
> Plugins live in their own directory and work within the ABCs / hooks / `ctx` surface we provide.
> A plugin MUST NOT modify `run_agent.py`, `cli.py`, `gateway/run.py`, `hermes_cli/main.py`, etc.

General plugins load from `plugins/<name>/`, **`~/.hermes/plugins/`**, `./.hermes/plugins/`
or pip entry points; `plugins.enabled` is currently `[]`. A plugin under `~/.hermes/plugins/`
lives outside the `hermes-agent/` source tree, so it **survives `hermes update`** — the
preference HO-065 states explicitly.

Contrast: `pre_auxiliary_call` / `post_auxiliary_call`
(`agent/auxiliary_hooks.py`) are explicitly **observer-only** ("Observer-only (returns
ignored) and fail-open") — they cannot transform anything, so they are the wrong seam for
this feature.

### 1.4 Proposed integration point (ranked)

| Rank | Option | Verdict |
|---|---|---|
| **1** | **User plugin at `~/.hermes/plugins/local-worker/`** registering `transform_tool_result` (+ `transform_terminal_output` for the terminal payload) and declaring its aux task slot via `ctx.register_auxiliary_task(...)` | **CHOSEN.** No core edits, off by default, survives `hermes update`, fail-open by construction, and both hooks are already documented as running before context entry |
| 2 | Aux task slot only (config-only, no plugin) | Insufficient alone — it provides a route but no automatic trigger on tool output, which §3 of HO-065 requires. It is a *component* of option 1, not an alternative to it |
| 3 | Minimal wrapper / core patch in `model_tools.py` or `tool_executor.py` | **Rejected.** Violates HO-065 ("Prefer anything that survives `hermes update` (no edits to files under `hermes-agent/`)") and the framework's own plugins/AGENTS.md rule |

The one caveat on option 1: option 1 is the right seam for *automatic output reduction*,
but the same seam fires for **every** tool call, so the size threshold and the
"is this an unstructured-log tool?" test both have to be cheap and must fail closed. §7 of
HO-065 anticipated this by requiring configurable size-based triggering; the existing
precedent for the threshold is in §3.3.

---

## 2. Answers to HO-065 §1 (inspect before modifying)

| # | Question | Finding |
|---|---|---|
| 1 | Hermes version | `v0.21.5+2168.g59004a6` (git install at `~/.hermes/hermes-agent`) |
| 2 | Provider/model configuration | `model.default: deepseek-v4.1-flash`, `model.provider: opencode-go`, `model.base_url: https://opencode.ai/zen/go/v1`, `model.api_mode: chat_completions`; `auxiliary:` has only `vision`; `fallback_providers` has 1 entry; `plugins.enabled: []`; `_config_version: 46` |
| 3 | How Hermes invokes models | `agent/auxiliary_client.py` for auxiliary tasks (per-task route resolution, retries, fallback chain, accounting) and the relay/agent loop for the main model; hooks at `pre/post_api_request` and `pre/post_auxiliary_call` |
| 4 | How Hermes receives tool output | `model_tools.handle_function_call` (registry) and the inline executor table; transport-agnostic |
| 5 | Existing middleware / hooks / compaction / summarization / subagents / local-provider support / routing | Middleware: `register_middleware` (request + execution kinds). Hooks: `pre_tool_call`, `post_tool_call`, `transform_tool_result`, `transform_terminal_output`, `transform_llm_output`, lifecycle + `pre/post_auxiliary_call`. **Compaction + summarization: yes — see §3.1.** Subagents: `delegate_task`. Local provider support: yes, custom OpenAI-compatible `base_url` (§1.2). Routing: per-task aux slots, `fallback_providers`, `fallback_chain`, `gateway.platforms.*.channel_overrides.*.model` |
| 6 | Is Ollama reachable from the Hermes runtime | Yes — `/v1/models` returns `qwen3:4b-8k`; Ollama 0.10.1 on host loopback |
| 7 | Does existing config already support OpenAI-compatible endpoints | Yes — `custom`/auto route with `base_url`, precedent `agent/image_routing.py:312` |
| 8 | Where config and runtime state live | `~/.hermes/config.yaml` (+ `~/.hermes/context_length_cache.yaml`), state under `~/.hermes`; gateway is the systemd user service `hermes-gateway` |

---

## 3. Findings that change the design (read these before approving)

### 3.1 Hermes already summarizes tool results — deterministically

`agent/context_compressor.py:1665` `_summarize_tool_result()` and
`_TOOL_RESULT_SUMMARIZERS` (`:1828`), e.g.:

```python
def _sum_terminal(name, args, content, content_len, line_count):
    cmd = _str_arg(args, "command")
    cmd = cmd if len(cmd) <= 80 else cmd[:77] + "..."
    exit_code = m.group(1) if (m := re.search(r'"exit_code"\s*:\s*(-?\d+)', content)) else "?"
    return f"[terminal] ran `{cmd}` -> exit {exit_code}, {line_count} lines output"
```

This is regex/line-based (no LLM) and produces **one-line** summaries, but it runs
**during context compaction**, not per call, and it is lossy by design. HO-065 §16 forbids
duplicating existing functionality, so the worker must be positioned as a distinct thing:
*pre-context, per-call, evidence-preserving reduction with a raw-handle*, not a second
compaction summarizer. If approved, I would name the worker's output label to avoid
confusion with compaction summaries, and I would not touch `context_compressor.py`.

### 3.2 Redaction does not sit where HO-065's constraint A assumes

Constraint A says "Run AFTER Hermes' secret redaction, never before." On this path that
ordering **cannot be satisfied by position**, because redaction is not applied to the
stored tool-result string that reaches `transform_tool_result`. `agent/redact.py`
(`redact_sensitive_text`) is invoked at **provider-request boundaries** instead:

```
agent/chat_completion_helpers.py:246:    from agent.redact import redact_sensitive_text
agent/chat_completion_helpers.py:1574:    from agent.redact import redact_sensitive_text
agent/context_compressor.py:37:from agent.redact import redact_sensitive_text
```

Consequence: unless the plugin calls `redact_sensitive_text()` on the input itself, raw
tool output containing a credential (e.g. a Docker log line or a config dump) would be
sent to the local Ollama worker unredacted. Sending to a loopback-only local model is a
much smaller exposure than a cloud call, but the constraint as written cannot be honoured
by ordering, so **this needs your decision** (see §8, item 1). My recommendation:
the plugin calls `redact_sensitive_text()` on the input before the Ollama call and treats
`_redact_enabled()` as a gate — which satisfies the *intent* of constraint A. Note that
redaction is lossy: tokens under 18 chars are fully masked, longer ones keep first 6 + last
4 (`agent/redact.py:1-4`), which is another reason the raw handle in §4 matters.

### 3.3 The threshold already has a home and a convention

`tools/tool_output_limits.py` — `tool_output` section in config.yaml:

```
DEFAULT_MAX_BYTES = 50_000       # terminal_tool.MAX_OUTPUT_CHARS
DEFAULT_MAX_LINES = 2000         # file_operations.MAX_LINES
DEFAULT_MAX_LINE_LENGTH = 2000   # file_operations.MAX_LINE_LENGTH
```

Read via `get_tool_output_limits()`, cached **per profile home**
(`hermes_home_key()`) — the pattern a plugin must follow, because one gateway process
serves several profiles. The existing terminal cap (50,000 bytes) is the natural anchor
for the worker's own trigger threshold rather than an invented number.

### 3.4 One environment fact is partially contradicted

HO-065: "Hermes already talks to local Ollama via the OpenAI-compatible /v1 endpoint (see
`~/.hermes/context_length_cache.yaml`)."

The mechanism is confirmed, but the **specific tag has no cache entry**:

```
$ grep -n -i 'ollama\|qwen3\|11434' ~/.hermes/context_length_cache.yaml
  deepseek-r1:7b@http://localhost:11434: 131072
  deepseek-r1:7b@http://localhost:11434/v1: 131072
  qwen3:8b@http://localhost:11434/v1: 40960
```

`qwen3:4b-8k` is absent, so the first call will probe/derive its context length; the cache
also carries entries for `qwen3:8b` and `deepseek-r1:7b`, both of which HO-065 §10 forbids
loading. Neither is loaded by this feature — flagging it only because HO-065 invites
re-verification when a command contradicts an environment fact. Note too that the cached
`qwen3:8b` figure of 40960 is well above the 8192 the worker tag is built for; the worker
must therefore pin its own budget (configurable) rather than trust a derived default.

### 3.5 `bounded_response.py` is not on this path

It is about bounding **provider HTTP error bodies** (64 KiB cap, 10 s deadline, used by
native Gemini / Gemini Cloud Code / Antigravity), not tool results. Listed in HO-065's
"read first" set, but it is not a seam for this feature. `auxiliary_hooks.py` is likewise
observer-only (§1.3). Reporting both so the design doesn't inherit a wrong assumption.

### 3.6 Truncation happens *after* the transform hook — good for us

`tools/terminal_tool_result.py`:

```python
    """Plugin output-transform seam (fail-open; first string result wins).
    Replacements are still subject to the output limit applied afterwards."""
```

So the worker sees the **full** command output (not a pre-truncated slice), and whatever it
returns is then subject to the existing `max_bytes` cap. That is the correct order for
"never lose raw evidence": the raw text is still what would be written to the handle, and a
runaway summary cannot exceed the normal output budget.

---

## 4. Proposed shape (sketch only — nothing implemented)

Not a specification; the concrete design follows approval.

- **Kind:** user plugin, `~/.hermes/plugins/local-worker/` (`plugin.yaml` + `__init__.py`),
  `mode: off` by default. Zero files under `hermes-agent/` are touched.
- **Seams:** `transform_tool_result` (all tools) and `transform_terminal_output`
  (terminal/background-process payload: `command`, `returncode`, `task_id`, `env_type`).
- **Aux slot:** `ctx.register_auxiliary_task("local_worker", ...)` with
  `defaults={provider: custom, model: qwen3:4b-8k, base_url: http://127.0.0.1:11434/v1,
  timeout: 120}`; `auxiliary.local_worker.*` in `~/.hermes/config.yaml` overrides it. Reuses
  the existing aux client (retries, timeouts, accounting) instead of a new HTTP client.
- **Config:** one new section (worker enable/mode/threshold/model/concurrency/keep_alive/
  memory floor/timeout/raw dir) plus the `auxiliary.local_worker` block, following the
  `tool_output` + `auxiliary.vision` conventions. Not hard-coded anywhere (§13).
- **Modes:** `off` | `shadow` | `active`, default `off`; `shadow` logs metrics and passes
  raw through untouched. `active` only on your review of shadow metrics (§D).
- **Raw handle:** raw text written to a capped, age-pruned directory outside any git tree,
  with the required marker spelling out the exact path so the cloud model can grep it (§C).
- **Deterministic first:** counts for pytest/ruff/tsc/eslint/vite/composer are parsed by
  regex/JSON reporters; the model is used only for unstructured logs, diffs and search
  results (§B), and any LLM count that disagrees with the regex pass is discarded and
  logged `status=count_mismatch` (§B).
- **Thinking control:** `/no_think` appended, ` thinking...</think>` stripped, empty/over-short
  response → raw fallback (§A).
- **Metrics:** `task_type, input_chars, output_chars, compression_ratio, duration_ms,
  status, fallback_used, MemAvailable before/after`, explicitly labelled character-based
  (§D, §11, §12) — reusing `agent/aux_accounting.py` machinery where it fits rather than
  inventing a parallel accounting path.
- **Tests:** the ten HO-065 §14 cases with a mocked Ollama — the normal unit suite must not
  require a live server; any live integration test is opt-in.
- **Rollback:** set `mode: off` (+ remove the plugin dir / disable it) — exact lines
  documented at handover (§E, §17).

---

## 5. What I will not touch

Per HO-065 §16 and §E: WordPress Business Checkup code (including the HO-064
implementation), the lead API, DB schema, n8n, the Docker `hermes` container, Ollama
service config (no bind-address change), any EPM v2 service, and any file under
`hermes-agent/`. I will not run `hermes update`. This report touched nothing — see §9.

---

## 6. Four decisions I need before implementing

1. **Redaction (blocking):** the §3.2 finding means constraint A cannot be met by ordering.
   Approve the plugin calling `redact_sensitive_text()` itself and gating on
   `_redact_enabled()`, or specify a different handling.
2. **Model tag:** implement against `qwen3:4b-8k` (uncached, §3.4) — or `qwen3:4b`
   with an explicit pinned context budget?
3. **Config location:** `~/.hermes/config.yaml` (the existing mechanism, as §13 requires)
   — confirm, since it is the gateway's live config.
4. **Commit policy:** HO-065 §E says "Commit before proceeding," while the standing project
   rule gates commits/pushes on your go-ahead. I have left this document uncommitted; say
   the word and I will commit it (and the pending OS-facing work) as its own step.

---

## 7. Status

Phase 0 complete. Awaiting approval. Nothing implemented, nothing committed, nothing
deployed.

---

## 8. Evidence index

Commands run in this gate (read-only): `git log/describe` in `~/.hermes/hermes-agent`;
`hermes --version`; `curl 127.0.0.1:11434/{api/version,api/tags,v1/models}`;
`grep -E '^(MemTotal|MemAvailable|SwapTotal|SwapFree)' /proc/meminfo`;
`grep -rn` over `agent/*.py`, `model_tools.py`, `tools/*.py`, `hermes_cli/*.py`,
`providers/*.py`; `sed -n` reads of the cited line ranges; `yaml.safe_load` of
`~/.hermes/config.yaml` with secret-looking values redacted before printing.

Notable raw values, `grep -E '^(MemTotal|MemAvailable|SwapTotal|SwapFree)' /proc/meminfo`:

```
MemTotal:        8131792 kB
MemAvailable:    5323044 kB
SwapTotal:       2097148 kB
SwapFree:         832412 kB
```

MemTotal 8131792 kB ≈ 7.75 GiB (matches HO-065's "7.8 GiB"); MemAvailable 5323044 kB ≈
5.08 GiB, i.e. **above** the 3072 MiB preflight floor; SwapFree 832412 kB of 2097148 kB —
≈1.2 GiB of swap already in use, consistent with HO-065's warning that RAM is the binding
constraint.

An earlier draft of this paragraph carried a mis-remembered MemTotal (7868648 kB); it was
corrected against the raw command output above. Recorded here because §E requires raw
output over narrated summaries.

Not used, deliberately: no configuration was edited, no model was loaded, no pull was run,
and `ollama ps` was not warmed (no call was made to the worker path because none exists yet).
