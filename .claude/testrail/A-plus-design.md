# TestRail Automation — "A+" Execution Design

**Status:** Design / pre-build · **Date:** 2026-06-30 · **Author:** Gaël Robin (+ Claude)

Single source of truth for how we make the TestRail → browser execution step **reliable**.
Consolidates findings from five independent prior-art sources (see Appendix) into one plan.

---

## 1. The problem we are solving

The fragile link in the current pipeline is one step inside `testrail-run-agent`:

> **Translate each TestRail step (HTML-stripped prose) into a Canary/Playwright script and run it.**

This translation happens **at run time, open-loop, with no grounding**:

- Selectors are *guessed from prose* ("Navigate to Settings > Imagify") — they have never
  touched the real DOM.
- Assertions are *inferred from expected text* ("Returns all available abilities").
- Prerequisites are *assumed* (the agent doesn't know an API key is needed, or where it lives).
- State leaks between sequentially-run cases (`optimize-media` is destructive; settings persist).

Anyone — not just us — authors the TestRail cases, so **pre-writing a script per case is not an
option**. The fix must work for cases we have never seen.

---

## 2. The core insight (validated by 5 independent sources)

Every serious prior-art system converged on the **same spine**, regardless of stack:

> **Explore the running app → ground locators from *real* interactions → validate by
> compiling → gate with humans → cache the grounding for reuse.**

Two volatilities of knowledge must be handled differently:

| Knowledge | Volatility | DOM-observable? | Source of truth |
|---|---|---|---|
| Selectors, labels, interaction order | High | **Yes** | Captured from **live exploration** — never frozen from a code read |
| URLs, prerequisites, API-key location, "what success means", seed/teardown | Low | **No** | Light **code/docs audit**, committed |

The original "Plan A" (Opus audits the *code* and writes specs) was right for the low-volatility
half and **wrong** for selectors — reading code to infer selectors is itself guessing. A stale,
hand-written selector spec is *worse* than none, because the agent trusts it and won't fall back
to looking at the real page.

---

## 3. A+ architecture

```
┌─────────────────────────────────────────────────────────────────────┐
│  /testrail-setup   (Explorer — runs occasionally, output committed)   │
│                                                                       │
│   • Drives the LIVE app via Canary, logged in                         │
│   • Captures REAL locators from real clicks (role-based preferred)    │
│   • Light code/docs audit for non-DOM knowledge:                      │
│       - admin URLs & how to reach each feature                        │
│       - prerequisites + how to SEED them (WP-CLI / REST)              │
│       - teardown / undo for each mutation (LIFO)                      │
│       - verification criteria ("success" = what, observably)          │
│   • Writes a grounded map → .claude/testrail/specs/<feature>.md       │
│   • Stamps each spec with source_files + git SHA  (drift detection)   │
└─────────────────────────────────────────────────────────────────────┘
                                  │  (committed, shared by whole team)
                                  ▼
┌─────────────────────────────────────────────────────────────────────┐
│  /testrail-run     (Executor — per release QA)                        │
│                                                                       │
│   1. Fetch run + cases from TestRail (steps + EXPECTED RESULTS)       │
│   2. Resolve case → feature spec (via frontmatter mapping)            │
│   3. Load _foundation.md + <feature>.md as grounding                  │
│   4. Per case:                                                        │
│        a. SEED prerequisites via API/CLI (not UI)                     │
│        b. Execute closed-loop against live DOM, grounded by the map   │
│        c. Assert against the TestRail EXPECTED RESULT (the oracle)    │
│        d. LIFO teardown                                               │
│        e. Capture trace/video/HAR/console (evidence)                  │
│   5. Results table → human confirm → post to TestRail                 │
└─────────────────────────────────────────────────────────────────────┘
```

### Why this beats the alternatives

- **Selectors are grounded in reality**, captured from real interactions, not prose or code.
- **The grounded map is committed & shared** — everyone runs against the same knowledge.
- **Drift is detectable** (SHA stamps), not silent.
- **TestRail expected-results are our assertion oracle** — neutralises the failure mode every
  prior-art author flagged as fatal: *"the agent knows what it observed, not what's correct."*
  We don't let the agent decide what "correct" means; TestRail tells it.

---

## 4. The two deltas harvested from prior art

Beyond the shared spine, two concrete mechanisms are worth lifting:

### 4.1 Per-case state isolation  (from the "scalable E2E" source)

Our 66 cases run **sequentially in one session**, and several **mutate state**
(`optimize-media`, settings updates, Internal State Reset). Without isolation, case N pollutes
case N+1 — a silent flakiness source.

**Contract per case:**
- **Seed prerequisites via WP-CLI / REST, not the UI** (set API key, force an attachment into
  optimized/unoptimized state, toggle a setting). Faster + deterministic.
- **LIFO teardown queue** — each case pushes its undo; teardown unwinds in reverse, even after
  an assertion fails.

This is also *captured by the Explorer* — "how to seed / tear down" is part of each feature spec.

### 4.2 Anti-hallucination ruleset  (from the "write/compile/execute/ship" source)

Encode grounding as **enforceable AUTO-REJECT guards** in the agent instructions:

- **Inferred locator while the page is reachable → REJECT.** Must open the page and capture the
  real locator.
- **Ephemeral/internal ref used as a locator → REJECT.** Convert to a stable locator
  (`getByRole` preferred, then `data-testid` / `id`).
- **Tautological assertion (e.g. assert true) → REJECT.** The assertion must check the TestRail
  expected result.
- **Generate only after reading 2–3 REAL existing `Tests/e2e/specs/` files** and copying their
  exact patterns/base classes — never invent a foreign style.

### 4.3 Validate before trusting generated code  (reconfirmed by 2 sources)

If/when we emit committed `.spec.ts` (phase 2), gate them: `tsc --noEmit` + ESLint against a temp
clone **before** the spec is allowed to run. Never trust un-compiled generated code.

---

## 5. File / artifact layout

```
.claude/testrail/                  ← committed, shared
  A-plus-design.md                 ← this doc
  specs/                           ← grounded maps (low-volatility + locators)
    _foundation.md                 ← login, admin URLs, test users, where the API key lives
    mcp-abilities.md               ← frontmatter: testrail_sections, source_files, derived_sha
    mixpanel-tracking.md
    settings.md
    ...

.ai/testrail/                      ← run-time / staging (gitignored working area)
  pending/                         ← generated scenarios awaiting review
  reviewed/                        ← reviewed scenarios ready to publish
  <run_id>/canary/<session>/       ← execution evidence (trace/video/HAR)
```

**Spec organisation:** by **plugin feature** (stable axis), *not* by TestRail section (which we
reshuffle). Each spec maps back via frontmatter `testrail_sections: [...]`, so the run agent
resolves section → spec with a thin lookup that survives TestRail reorg.

---

## 6. Execution model — the one open decision

The grounded Explorer/map is shared by both options below and is built first. The fork is **how
the run agent executes**:

**Option 1 — Closed-loop Canary run (recommended first).**
Run agent drives the live browser, grounded by the map; re-explores an element if it drifts.
Evidence-rich (trace/video/HAR for TestRail), simpler, nothing to maintain but the map.

**Option 2 — Generate validated, committed `.spec.ts` + CI replay (phase 2).**
A Writer emits Playwright specs (grounded by the map + TestRail steps), validated via §4.3, then
CI replays them fast & deterministically (`--last-failed` for reruns). Specs become real repo
artifacts; matches the existing `Tests/e2e/specs/` story. Bigger build.

**Recommendation:** build the Explorer + grounded map, ship Option 1, layer Option 2 later —
Option 2's committed specs are only as good as the grounding, so get the grounding right first.

---

## 7. Build order

1. `_foundation.md` schema + the Explorer agent + `/testrail-setup` (and `--check` for drift).
2. Re-ground one feature end-to-end (`mcp-abilities.md`) as the reference example.
3. Update `testrail-run-agent`: load specs, state-isolation contract (§4.1), anti-hallucination
   guards (§4.2), TestRail-expected-result oracle.
4. Dry-run the MCP Abilities cases; compare reliability vs. the old open-loop path.
5. (Later) Option 2 Writer + validation gate + CI replay.

---

## 8. Agent design principles (build lean, not bloated)

**Non-negotiable.** Every agent/skill in this system must follow these, or it gets rejected in
review. A bloated agent is slow, expensive, and unreliable — token weight is cognitive weight.

1. **One agent, one job.** Explorer explores. Executor executes. Scenario generates. Reviewer
   reviews. If an agent's `description` needs the word "and" to describe its job, split it.

2. **Minimal tool grant.** Grant only the tools the job needs — fewer tools = less for the model
   to weigh = cheaper, faster, safer.
   - Explorer: `Bash, Read, Write, Glob, Grep` (drives Canary via Bash; **no** WebFetch).
   - Executor (`testrail-run-agent`): `Bash, Read, Write, Glob, Grep`.
   - Never grant a tool "just in case."

3. **Right model for the job.**
   - **Explorer → Opus or Sonnet.** Reasoning-heavy (map an app, judge what matters). Runs
     occasionally, so cost is amortised — fine to pay for quality here.
   - **Executor → Sonnet.** Procedural (follow spec, drive browser, assert). Runs 66×/run —
     keep it cheap. Do **not** pay Opus prices for procedural work.
   - Set `model:` explicitly in frontmatter; don't inherit by accident.

4. **Push deterministic work OUT of the LLM.** HTML-stripping, JSON building, WP-CLI seeding,
   SHA stamping, file moves, `curl` calls — these are Bash/python, **not** LLM reasoning. The
   LLM decides *what* to do; scripts do the mechanical parts. Every token spent on string
   manipulation is a token wasted and a chance to hallucinate.

5. **Knowledge lives in specs, not in the prompt.** The agent prompt says *"resolve the case to
   its feature spec and load it."* It must **not** inline the grounded map, the 7 ability slugs,
   or the section IDs. Progressive disclosure: read what you need at runtime. This keeps the
   agent file small and lets knowledge update without editing the agent.

6. **DRY within the prompt.** State each rule once. One `## DO NOT` block at the end, not the
   same warning sprinkled five times.

7. **Concrete > prose.** A code block, schema, or table beats three paragraphs. The "no verbose"
   QA principle applies to the agent *files* themselves.

8. **Bounded loops.** Every agent sets `maxTurns`. Any validation/self-heal loop has an explicit
   attempt cap (≤ 2 rounds) then escalates to a human — never spins.

9. **Fail loud, escalate early.** On auth failure, missing env, or ambiguous state: stop and
   report (BLOCKED), don't guess. Distinguish environment gaps from product defects.

---

## 9. Component contracts

### 9.1 `_foundation.md` schema (committed)

```markdown
---
derived_sha: <git SHA at capture time>
source_files: [imagify.php, ...]          # for drift detection
last_explored: 2026-06-30
---

## Environment
- Base URL:  http://localhost:10038
- WP admin:  http://localhost:10038/wp-admin/
- Login:     admin / admin via /wp-login.php
             role-based: getByLabel("Username or Email Address"), getByLabel("Password")

## Prerequisites
- Imagify API key:  lives in settings.local.json (key IMAGIFY_API_KEY).
                    seed via: wp option patch / define() — see Seeding helpers.
- Settings page:    /wp-admin/options-general.php?page=imagify

## Test users (for permission cases)
| Role          | Login          | imagify_capacity |
|---------------|----------------|------------------|
| administrator | admin / admin  | full             |
| editor        | <seed>         | limited          |
| subscriber    | <seed>         | denied           |

## Seeding helpers (WP-CLI — run via Bash, not UI)
- Set API key:            wp option patch update imagify_settings api_key "<key>"
- Force media optimized:  wp ...      # captured by Explorer
- Reset media:            wp ...      # the LIFO undo
- Toggle a setting:       wp option patch update imagify_settings <k> <v>
```

### 9.2 Feature spec schema (committed) — concrete example `mcp-abilities.md`

```markdown
---
testrail_sections: [8724]                 # MCP Abilities (maps section → this spec)
feature: "MCP Abilities API"
source_files: [classes/Abilities/*.php, classes/AbilitiesSubscriber.php]
derived_sha: <sha>
last_explored: 2026-06-30
---

## Overview
7 abilities via WP Abilities API + MCP adapter. Requires WP >= 6.9.
Capability gated by the `imagify_capacity` filter (NOT direct current_user_can).

## Abilities (ground truth)
| Slug                          | Class              | Destructive |
|-------------------------------|--------------------|-------------|
| imagify/get-account           | GetAccount         | no          |
| imagify/get-settings          | GetSettings        | no          |
| imagify/get-media-status      | GetMediaStatus     | no          |
| imagify/get-stats             | GetStats           | no          |
| imagify/get-nextgen-coverage  | GetNextgenCoverage | no          |
| imagify/optimize-media        | OptimizeMedia      | YES         |
| imagify/update-settings       | UpdateSettings     | mutates     |

## How to invoke (grounded from live)
- MCP tool: mcp__imagify__mcp-adapter-execute-ability  { ability, input }
- REST:     <endpoint captured live by Explorer>

## Locators (captured live — role-based preferred, then data-testid, then id)
- <filled by Explorer>

## Prerequisites & seeding (per ability)
- get-media-status / optimize-media: need an attachment ID → seed via _foundation helper.
- update-settings: snapshot current settings first (for teardown).

## Verification criteria — "success" means (observable)
- optimize-media:  response status == "success" AND attachment gains _imagify_data meta.
- get-* :          response schema complete, types correct, api_key/version absent.

## Teardown (LIFO)
- optimize-media:  restore original via <helper>.
- update-settings: re-apply the snapshot.
```

### 9.3 Explorer agent contract (`testrail-explorer-agent.md`)

```yaml
name: testrail-explorer-agent
tools: [Bash, Read, Write, Glob, Grep]
model: opus            # reasoning-heavy, runs occasionally
maxTurns: 60
```
- **Job:** drive the live app (Canary, logged in), capture real locators + seed/teardown +
  verification criteria per feature, write/refresh `.claude/testrail/specs/*.md`, stamp SHAs.
- **Input:** a feature name or "all"; optional `--check` (drift report only, no writes).
- **Output:** committed spec files + a summary of what changed since last explore.
- **Anti-hallucination guards (§4.2) apply** — capture locators from real clicks, never invent.
- **DO NOT:** write selectors it did not observe; print the API key; invent ability slugs.

### 9.4 `/testrail-setup` skill

- `/testrail-setup` → explore everything, write/refresh all specs.
- `/testrail-setup <feature>` → re-ground one feature.
- `/testrail-setup --check` → drift report only (which specs are stale vs current SHA), no writes.
- Spawns `testrail-explorer-agent`; relays its summary. Never posts to TestRail.

### 9.5 Changes to `testrail-run-agent` (the executor)

Add, without bloating it (move detail into specs, keep the agent procedural):
1. **Resolve case → spec:** map the case's TestRail section to a feature spec via frontmatter
   `testrail_sections`; load `_foundation.md` + that spec as grounding.
2. **State-isolation contract (§4.1):** seed prerequisites via WP-CLI/REST before the browser;
   register a LIFO teardown; run teardown after each case (even on failure).
3. **Anti-hallucination guards (§4.2):** the reject rules become a single DO NOT block.
4. **Assertion oracle:** assert against the TestRail **expected result**, not "page looked ok".
5. Keep everything else (sequential, evidence capture, confirm-before-post) unchanged.

---

## 10. Concrete Imagify reference data (discovered this session — don't rediscover)

```
Local env      : http://localhost:10038   (wp-env)   login admin / admin
Settings page  : /wp-admin/options-general.php?page=imagify
API key        : settings.local.json → IMAGIFY_API_KEY
WP requirement : >= 6.9 (abilities no-op below this)
Capability     : imagify_capacity filter (not direct current_user_can)

TestRail       : https://wpmediaqa.testrail.io  · project 3 · suite 3
  Sections     : MCP Abilities 8724 (parent API Requests 7685)
                 Mixpanel Tracking 8725 (parent Regression 33)
                 general settings 7689 · next-gen 7694 · file optimization 7695
  Case fields  : template_id 2 (Step) · type_id 7 · priority_id 2
                 custom_steps_separated[{content,expected}] · custom_smoketest · custom_preconds
  Result status: 1 PASS · 5 FAIL · 2 BLOCKED
  Dedup key    : refs = PR URL

Canary         : npx @usecanary/cli  (capture trace,video,har,console)
Fixtures       : .claude/agents/canary-imagify-session-agent.md (login, selectors, ability slugs)
E2E specs      : Tests/e2e/specs/  ·  playwright.config.ts workers:1  ·  E2E_CI=true
MCP tools      : mcp__imagify__mcp-adapter-{discover-abilities,execute-ability,get-ability-info}
```

7 ability slugs: `get-account, get-settings, get-media-status, get-stats, get-nextgen-coverage,
optimize-media, update-settings` (all `imagify/` prefixed, kebab-case).

---

## 11. Drift detection mechanics

- Each spec's frontmatter records `source_files` (globs) + `derived_sha` (the commit it was
  captured at).
- `/testrail-setup --check`: for each spec, `git log -1 --format=%H -- <source_files>`; if any
  source file's latest commit is newer than `derived_sha`, mark the spec **stale** and name the
  files that changed. Output is a report only — no rewrites.
- The run agent runs a cheap `--check` at start and **warns** (does not block) if it's executing
  against stale specs, so a tester knows the grounding may be behind the code.
- Re-grounding (`/testrail-setup <feature>`) re-stamps the SHA.

---

## 12. What we deliberately rejected

- **3rd-party skills** (`lackeyjb/playwright-skill`, etc.) — none do TestRail; generic
  prose→Playwright can't produce working selectors for *our* app without seeing it; supply-chain
  risk in a committed pipeline.
- **Freezing selectors in a hand-audited spec** — the "confidently wrong" trap.
- **Enterprise scaffolding** — 2FA/IMAP, parallel sharding, GraphQL clients, 7-agent swarms,
  Allure deploy. We are `workers: 1`, local wp-env, `admin/admin`.

---

## Appendix — sources triangulated (blueprints, none adopted as code)

1. `lackeyjb/playwright-skill` — general Playwright runner; **no** TestRail (Gemini fabricated
   that). Validates iterative live execution.
2. `aiqualitylab/ai-natural-language-tests` — live-DOM grounding (`--url`), pattern memory.
   **AGPL-3.0** — unusable in a commercial plugin.
3. `learnautomatedtesting/playwright-ai-agents` — Planner/Generator/Healer on `.claude/agents/`.
   Proves the architecture; immature (1 commit).
4. "Scalable E2E … Playwright/GraphQL/Actions" (Medium) — **API-seed + LIFO teardown**
   (§4.1), role-based locators, rerun-failed-only.
5. "AI agent that writes/compiles/executes/ships E2E" (Medium) — **anti-hallucination reject
   rules** (§4.2), compile-before-execute (§4.3), cached discoveries, two human gates.

**Convergence:** all five → explore-live · ground from real interactions · validate by
compiling · gate with humans · cache the grounding. A+ = that spine + deltas §4.1 and §4.2,
plus our unique edge: **TestRail expected-results as the assertion oracle.**
