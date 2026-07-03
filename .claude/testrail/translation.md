# Translating a TestRail case into a Playwright spec — thinking protocol

Read this in full before translating your first case of a run. It is not a list of commands —
the run agent's own file covers mechanics. This is the reasoning layer: how to think about the
prose→code problem so that the spec you emit proves something, instead of merely resembling
the prose it came from.

## The one-sentence model

A TestRail case is a human's **lossy serialization of an intent and an oracle**. Your job is
not to transcribe its steps into code — it is to reconstruct the intent, find the strongest
observable oracle, and emit the smallest deterministic program that proves or refutes it.
Every failure mode of naive translation (guessed selectors, tautological assertions, steps
that "pass" because they check nothing) comes from transcribing instead of reconstructing.

## 1. Read the case backwards

Read all the expected results before you read any action. The **last** expected result is
usually the case; everything before it is scaffolding to get there. Before writing anything,
answer in one sentence: *"What single observable state change would prove this case?"* Write
that sentence down. Every locator, action, and assertion you choose afterward is in service
of reaching and checking that oracle — anything that doesn't serve it is decoration.

## 2. Classify every step before writing any code

Human testers blur four different things into "steps". You must not:

| Kind | Tell | What it becomes |
|---|---|---|
| **Setup in disguise** | "Have an optimized image", "Be logged in as an editor" | Seeding via WP-CLI/REST *before* the browser opens — never UI clicks |
| **Navigation** | "Go to Settings > Imagify" | `page.goto()` / POM `goto()` |
| **Actuation** | "Enable the toggle and Save" | POM method or grounded locator action |
| **Observation** | "Verify the notice appears" | An assertion attached to the *preceding* actuation — not an empty step |

A step that is pure observation gets no action; a step that is setup in disguise gets no
`test.step()` at all. If after classification a step is neither reachable by seeding nor by
the browser (needs multisite, a paid service, an env you don't have), the case is BLOCKED —
you know this *before* generating code, which is the cheapest possible time to know it.

## 3. Every noun resolves through three sources, in order

Every UI noun in the prose ("the lossless toggle", "the bulk page") must resolve to exactly
one of, in strict preference order:

1. a **POM method** (`Tests/e2e/pages/` — the compiled, proven truth),
2. a **grounded locator** from the matched feature spec (`.claude/testrail/specs/`),
3. **one targeted live observation** (navigate + snapshot of that specific element).

Prose never resolves directly to a selector. If a noun resolves to none of the three, that is
a **grounding gap**, not a guessing opportunity — the correct output is BLOCKED with the exact
noun that failed to resolve, so `/testrail-setup` can fix it once for every future run. A
confidently guessed selector is strictly worse than a BLOCKED row: the BLOCKED row costs a
human one grounding pass; the guess costs a human a false signal they must investigate.

## 4. Choose the strongest cheap oracle

Expected results are claims about the world. Rank the ways of checking a claim:

1. **State** — read the actual option/meta/response via REST or WP-CLI (`get-settings` shows
   `lossless == 1`).
2. **Dedicated UI signal** — the specific element whose job is to announce this outcome
   (`#setting-error-settings_updated`, the API-key container's validity class).
3. **Generic UI echo** — a success notice class, a changed label.
4. **Absence of error** — page loaded, no fatal.

Prefer the highest rank that costs one request. When the case is *about* the UI (the point is
that the checkbox renders checked), assert the UI **and** the state — but never let a generic
echo be the only proof of a state change when the state itself is one cheap request away.
Level 4 alone is never an oracle; it is the floor every step gets for free.

## 5. Plan → validate → codegen. Never prose → code.

Before emitting TypeScript, write the intermediate plan as a table — one row per TestRail
step:

```
step | kind (seed/nav/act/observe) | surface | locator + SOURCE (pom/spec/observed) | oracle | undo
```

Then validate the table, mechanically:
- No row's locator source is "inferred" or blank. (Rule 3.)
- Every actuation row has an oracle, or explicitly feeds the next row's oracle. (Rule 4.)
- Every row that mutates state has an `undo` entry, queued LIFO **now**, not after execution.
- Any conflict between sources is resolved upward: POM beats spec beats hints beats prose.

Only a table that passes becomes code. This is not bureaucracy — the table is where
hallucinations die cheaply. A wrong locator caught in the plan costs one look; caught at
execution it costs a browser run, a trace, and a repair loop; caught by a human reviewing a
false FAIL it costs the credibility of the whole run.

## 6. The ambiguity protocol

When the prose is ambiguous (two Save buttons; "the settings page" when there are three):

1. The grounded spec decides, if it addresses the surface.
2. Else spend **one** targeted live observation on the ambiguous element only.
3. Else BLOCKED, stating the precise question a human must answer — not a guess.

Never resolve ambiguity by picking the interpretation that is easiest to code. The tester who
wrote the case had one specific thing in mind; your job is to find it or say you couldn't.

## 7. Spend observations where your uncertainty is

You have a budget of live looks (they are slow and cost turns). Before generating, mark each
plan row with your honest confidence: a POM method with a track record is high; a grounded
locator from a fresh spec is high; a grounded locator from a spec whose `derived_sha` is
stale is medium; anything the spec doesn't cover is low. Spend your pre-flight observation on
the **lowest-confidence row only**. Confirming what you already know is waste; running blind
on the row you doubt is how false results happen. Calibration — knowing *which* of your
beliefs is the weak one — is the skill.

## 8. Translate by analogy before translating from scratch

Before writing a new spec, look for the nearest **green cached spec** (same TestRail section,
same admin surface) under `Tests/e2e/testrail-cases/` and diff the new case's steps against
it. Adapting a proven artifact — same imports, same login step, same seeding idiom, changed
actuation and oracle — is dramatically more reliable than de novo generation, because
everything you didn't change is already known to compile, run, and assert correctly. The
cache is not just a speed-up; it is your few-shot library, and it gets better every run.

## 9. Interpreting failure: your model of the app vs. the app

When a run fails, the order of suspicion is fixed:

1. **My code** — compile error, unescaped quote, wrong import path. (Tooling, never FAIL.)
2. **My model of the app** — locator drifted, timing assumption wrong, conditional render I
   didn't know about. (Re-observe the real element once; fix the spec.)
3. **The environment** — login failed, site down, missing prerequisite. (BLOCKED.)
4. **The product** — only after a re-observation confirms the live page truly contradicts the
   expected result.

The prior matters: on a first run, most failures are #1 or #2. You may only report FAIL when
you can point at the defect — the exact element/response that contradicts the TestRail
expected result — and you have confirmed it against the live page, not just against your
generated code's opinion of it. "My spec went red and I can't tell why" is never FAIL; it is
BLOCKED or a repair pass, depending on which attempt you're on.

## 10. Determinism disciplines (non-negotiable)

- Web-first assertions only (`toBeVisible`, `toHaveText`, `toHaveValue` with timeouts).
  Never `waitForTimeout` — a sleep is an admission you don't know what you're waiting for;
  find the signal and wait on it.
- `expect.soft` for every TestRail-step assertion, with a message naming the expected result
  it checks — the message is what a human reads in the results file, write it for them.
- Escape everything interpolated from TestRail prose (quotes, backticks, `${`) — a spec that
  fails to compile because of an apostrophe is your bug, and must never surface as FAIL.
- Seed state via API/CLI, verify via the strongest oracle, undo via the queued LIFO teardown.
  The UI is for the behavior under test, not for setup — setup through the UI makes case N's
  outcome depend on case N−1's cleanup being perfect.
- One `test()` per case, one `test.step()` per TestRail step, login always first. A fresh
  `test()` per step would discard the session that later steps depend on.

## Worked micro-example

TestRail case: *"1. Enable lossless compression in the settings and save. Expected: setting
is saved. 2. Optimize an image from the media library. Expected: the image is optimized
losslessly."*

**Backwards read:** the case is step 2's expected — an optimization actually ran in lossless
mode. Step 1 is scaffolding. One-sentence oracle: *"after optimizing, the attachment's
optimization data records lossless mode."*

**Classification:** "an image" in step 2 is setup in disguise → seed an attachment via REST
before the browser opens, queue its deletion. Step 1 is actuation with its own oracle. Step
2 is actuation + the case's real oracle.

**Resolution:** lossless toggle → grounded spec (`#imagify_lossless`, label overlays input —
spec says read `checked`, don't click the label); Save → POM `settings.save()`; optimize
button in the library row → `MediaLibraryPage` POM.

**Oracles:** step 1 — strongest cheap check is state: `get-settings` returns `lossless == 1`
(the success notice is the UI echo, assert it too since it's free). Step 2 — state again:
the attachment's `_imagify_data` meta (or `get-media-status`) shows an optimization result
consistent with lossless; "the image looks the same" is not observable and must not be faked.

**Plan validates** (every row sourced, every mutation has an undo: restore `lossless` to its
snapshot value, delete the seeded attachment) → now, and only now, write the TypeScript.
