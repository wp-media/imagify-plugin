# Imagify – AI Coding & Architecture Guidelines

This file defines NON-NEGOTIABLE rules for any AI-assisted work
(Claude Code, ChatGPT, JetBrains AI Assistant, Cursor, etc.)
in this repository.

Skills define behavioral guidance.
AGENTS.md defines mandatory guardrails.
If a conflict exists, AGENTS.md prevails.

The objective is to keep Imagify:

- WordPress.org compliant
- Architecturally consistent
- Secure
- Maintainable
- Review-friendly

This document applies to ALL automated or AI-generated changes.

---

# 1. Project Overview

Imagify is a single-edition WordPress plugin for image optimization.

- **Repo:** `wp-media/imagify-plugin`
- **Plugin slug:** `imagify`
- **PHP namespace root:** `Imagify\`
- **PSR-4 root:** `classes/`

There is no FREE/PRO split. The codebase has two layers:

- `classes/` — modern PSR-4 code, namespace `Imagify\`, `declare(strict_types=1)` required. **New features go here.**
- `inc/classes/` — legacy classmap code, `Imagify_` prefix. **Do not add new classes here; migrate out instead.**

When modifying architecture:
- Prefer the modern `classes/` layer for all new work.
- Follow service provider + subscriber pattern for wiring.

---

# 2. Technology Stack

- PHP 7.3+ (strict types, PSR-4 autoloading via Composer)
- WordPress plugin APIs (hooks, options, WP-CLI, AJAX)
- League Container (DI container + service providers + event subscribers)
- ActionScheduler (async background jobs)
- Strauss (Composer dependency namespace prefixing → `Imagify\Dependencies\`)
- JavaScript / Grunt (`_dev/` pipeline → `assets/`)
- Playwright + TypeScript (E2E testing under `Tests/e2e/`)

---

# 3. Code Structure

```
classes/          New PHP code (PSR-4, Imagify\ namespace)
inc/              Legacy PHP includes (procedural, no namespace)
inc/classes/      Legacy class files migrating toward classes/
assets/           Compiled frontend assets (do not edit directly)
_dev/             Frontend source (JS, SCSS, Grunt config)
views/            PHP view templates
Tests/            PHPUnit tests
Tests/e2e/        Playwright E2E tests (TypeScript)
bin/              CLI scripts (dev-up, dev-down, dev-seed, test-e2e, build-knowledge-graph)
docs/             Documentation (E2E_TESTING.md, etc.)
.aiassistant/     Skill files for AI assistants
.claude/agents/   Claude Code sub-agents (qa-engineer, e2e-qa-tester)
```

---

# 4. Coding Standards & Static Analysis

Source of truth:

- Composer scripts (`composer.json`)
- PHPCS ruleset (`phpcs.xml`)
- PHPStan config (`phpstan.neon.dist`)
- WordPress Plugin Check: https://github.com/WordPress/plugin-check/
- CI pipeline rules

Imagify must remain compatible with WordPress.org validation rules.

Any change affecting public APIs, output, security, metadata, or
plugin bootstrap behavior must be evaluated against WordPress Plugin Check expectations.

AI MUST:

- Read `composer.json` first and use the defined scripts (e.g. `phpcs`, `phpcbf`, `run-stan`, `test-unit`, `test-integration`) instead of inventing commands.
- Auto-discover PHPCS configuration and follow it as the single source of truth.

## 4.1 Tooling Auto-Discovery (MANDATORY)

Before making changes that affect standards or formatting, the agent MUST locate and respect the repository configuration files.

### Required reads (in this order)
1. `composer.json` — use scripts defined in `"scripts"` whenever possible; prefer the exact commands used by CI; do not invent lint/test commands.
2. PHPCS ruleset (first match wins): `phpcs.xml`, `phpcs.xml.dist`
3. Static analysis configs (if present): `phpstan.neon.dist`

### Execution rules
- Do NOT hardcode PHPCS standards.
- Do NOT assume WordPress-Core or WordPress-Extra unless defined in the ruleset.

If no PHPCS configuration exists, stop and ask.

---

# 5. Architectural Integrity

AI must NOT:

- Introduce global state.
- Add new singletons or `InstanceGetterTrait` usage in `classes/`.
- Bypass dependency injection patterns used in the project.
- Couple UI logic to infrastructure logic.
- Add new classes to `inc/classes/`.

Follow existing patterns:

- Service providers (`classes/*/ServiceProvider.php`)
- Subscribers (`classes/*/Subscriber.php` implementing `SubscriberInterface`)
- Container-based wiring (`config/providers.php`)
- Strict types in all new `classes/` files

---

# 6. Testing & Validation

For every change:

1. Ensure no new PHPCS violations.
2. Ensure static analysis still passes.
3. Avoid altering unrelated test behavior.
4. Do not delete tests unless clearly obsolete.

If modifying templates:
- Validate escaping correctness.
- Ensure no functional regressions.

---

# 7. E2E Testing

Two Claude Code sub-agents in `.claude/agents/` support QA workflows:

| Agent | Use when |
|-------|----------|
| `qa-engineer` | Validating a PR against its ticket spec (strategy selection, test report) |
| `e2e-qa-tester` | Driving the browser via Playwright, converting flows to spec files |

Full E2E testing documentation: [`docs/E2E_TESTING.md`](docs/E2E_TESTING.md)

The test directory is `Tests/e2e/` (capital T, consistent with the existing `Tests/` PHPUnit directory).

The E2E suite runs in CI via `.github/workflows/e2e.yml`. The `IMAGIFY_TESTS_API_KEY` GitHub secret must be configured for optimization tests to run.

---

# 8. Local Development

```bash
# Start the local WordPress environment (Docker via wp-env)
bash bin/dev-up.sh

# Stop (preserves data) / full wipe
bash bin/dev-down.sh
bash bin/dev-down.sh --clean

# Seed test data (idempotent)
bash bin/dev-seed.sh

# Run E2E tests locally (sources .env.local for API key automatically)
bash bin/test-e2e.sh
bash bin/test-e2e.sh --headed     # watch the browser
bash bin/test-e2e.sh --ui         # Playwright interactive UI
bash bin/test-e2e.sh specs/smoke  # single spec
```

Create `.env.local` at the repo root (gitignored) with:
```
IMAGIFY_TESTS_API_KEY=your-key-here
```

- Site: `http://localhost:8888`
- Admin: `http://localhost:8888/wp-admin` — `admin` / `password`

---

# 9. AI Working Protocol

AI must work in small, incremental changes.

After each logical change set:
- explain what changed
- explain why
- list potential edge cases

AI must NOT:

- Perform massive automated refactors without approval.
- Reorganize files without explicit instruction.
- Rewrite entire classes when a minimal fix is sufficient.

## 9.1 Git Commit & Push Policy

By default, AI may only **suggest** commit messages and must not run `git commit` or `git push`.

**Exception — Issue Workflow:** When operating under the issue-workflow skill (triggered by `/task <number>`, `issue <number>`, or `#<number>`), the agent MAY:

1. Run atomic `git commit` calls — one commit per logical, self-contained change set.
2. Run `git push` exactly once after all commits are ready, to publish the branch.
3. Create a GitHub Pull Request using the prepared PR draft.
4. Monitor PR CI status checks until all pass or a failure is detected.

Atomic commit rules:
- Each commit must pass PHPCS and static analysis before being committed.
- Commit message format: `type(scope): short description` (Conventional Commits).
- No `Co-Authored-By` lines in commits.
- Do not squash unrelated changes into a single commit.
- Do not amend commits that have already been pushed.

---

# 10. PR Hygiene

Changes must:

- Be minimal and scoped.
- Have clear intent.
- Avoid noise in diff.
- Avoid unrelated formatting changes.

---

# 11. Security First

Always assume:

- User input is untrusted.
- Remote API responses are untrusted.
- Stored values may be tampered with.

Never:

- Store sensitive values in plain text without review.
- Introduce unsafe serialization.
- Echo unescaped dynamic data.

---

# 12. When in Doubt

Stop.
Explain the ambiguity.
Ask for clarification.

Architectural integrity is more important than speed.

---

# 13. Sub-Agents

Reusable specialist agents live in `.aiassistant/agents/`. Claude Code discovers them via the `.claude/agents` symlink; other tools can read them directly from `.aiassistant/agents/`.

| Agent | File | Invoke when |
|-------|------|-------------|
| `qa-engineer` | `.aiassistant/agents/qa-engineer.md` | Validating a PR against its ticket spec — reads acceptance criteria, runs functional/browser/analysis strategies, produces a structured test report |
| `e2e-qa-tester` | `.aiassistant/agents/e2e-qa-tester.md` | Driving the browser via Playwright, walking through "How to test" steps, converting validated flows into Playwright spec files under `Tests/e2e/` |

The `qa-engineer` agent delegates browser flows to `e2e-qa-tester` automatically when the change involves admin UI.

---

# 14. Skills Activation

The repository defines AI Skills under `.aiassistant/skills/`.

Agents MUST activate the relevant skill depending on the task:

| Task | Skill |
|------|-------|
| Template or UI changes | WordPress Compliance |
| Structural or architectural changes | Imagify Architecture |
| Service modifications | Both skills |
| Codebase exploration / dependency tracing | Knowledge Graph |
| Working on a GitHub issue | Issue Workflow |

## 13.1 Knowledge Graph

A pre-built dependency graph is available at `.aiassistant/graph/dependency-graph.json`.

Before exploring the codebase structure (finding a class, tracing dependencies, exploring namespaces), **read this file first**. It contains:
- `nodes`: per-file namespace, declared symbols, and imports.
- `symbol_index`: maps every fully-qualified PHP class/interface/trait/enum to its file.

Run `node bin/build-knowledge-graph.js` to refresh after structural changes (`--full` to force rebuild).

---

## 13.2 Session Learnings

Lessons learned from past pipeline runs. Injected into every agent dispatch by the orchestrator.

### E2E — Screenshots must show the target element

**Rule:** Always call `locator.scrollIntoViewIfNeeded()` before `page.screenshot()`. Use the shared helper `screenshotElement()` from `Tests/e2e/fixtures/screenshot.ts` instead of calling `page.screenshot()` directly.

**Why:** A screenshot taken at page-load position captures the top of the page, not the feature being tested. Screenshots showing unrelated content (e.g. General Settings header instead of the target button) provide zero QA evidence and mislead reviewers.

**How to apply:** In every Playwright spec, import `screenshotElement` from `../fixtures/screenshot` and pass the target locator as the third argument. Verify each screenshot visually shows the element it documents before committing.

### E2E — Never use `test.skip()` as a fallback for missing seed data

**Rule:** If a required UI element is not visible due to missing seed data, use a hard `expect(...).toBe(true)` assertion, not `test.skip()`.

**Why:** `test.skip()` causes the test suite to report green with zero assertions — CI passes but nothing is tested. A hard fail surfaces the missing seed as a real problem.

**How to apply:** Check element visibility after navigation. If not visible, fail with a descriptive message that includes the seed command needed to fix the environment.

---

# 15. Repository Specs

The repository may define task-specific implementation specs under `.aiassistant/specs/`.

Specs provide detailed guidance for recurring technical problems
(e.g. PHPCS warnings, architecture migrations, WordPress compliance patterns).

When a relevant spec exists, agents must follow it in addition to AGENTS.md and the applicable skills.

---

# AI Task Priority

When executing tasks, agents must prioritize:

1. Security
2. WordPress.org compliance
3. Architectural integrity
4. Backward compatibility
5. Minimal diffs
6. Performance

AGENTS.md remains the final authority.
