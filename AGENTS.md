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

There is no FREE/PRO split. The codebase has two layers:

- `classes/` — modern PSR-4 code, namespace `Imagify\`, `declare(strict_types=1)` required. **New features go here.**
- `inc/classes/` — legacy classmap code, `Imagify_` prefix. **Do not add new classes here; migrate out instead.**

When modifying architecture:
- Prefer the modern `classes/` layer for all new work.
- Follow service provider + subscriber pattern for wiring.

---

# 2. Coding Standards & Static Analysis

Source of truth:

- Composer scripts (composer.json)
- PHPCS ruleset (phpcs.xml)
- PHPStan config (phpstan.neon.dist)
- WordPress Plugin Check: https://github.com/WordPress/plugin-check/
- CI pipeline rules

Imagify must remain compatible with WordPress.org validation rules.

Any change affecting public APIs, output, security, metadata, or
plugin bootstrap behavior must be evaluated against WordPress Plugin Check expectations.

AI MUST:

- Read `composer.json` first and use the defined scripts (e.g. `phpcs`, `phpcbf`, `run-stan`, `test-unit`, `test-integration`) instead of inventing commands.
- Auto-discover PHPCS configuration and follow it as the single source of truth.

## 2.1 Tooling Auto-Discovery (MANDATORY)

Before making changes that affect standards or formatting, the agent MUST locate and respect the repository configuration files.

### Required reads (in this order)
1) `composer.json`
    - Use scripts defined in `"scripts"` whenever possible.
    - Prefer the exact commands used by CI.
    - Do not invent lint/test commands.

2) PHPCS ruleset (first match wins):
    - `phpcs.xml`
    - `phpcs.xml.dist`

3) Static analysis configs (if present / referenced):
    - `phpstan.neon.dist`

### Execution rules
- Do NOT hardcode PHPCS standards.
- Do NOT assume WordPress-Core or WordPress-Extra unless defined in the ruleset.

If no PHPCS configuration exists, stop and ask.

---

# 3. Architectural Integrity

AI must NOT:

* Introduce global state.
* Add new singletons or `InstanceGetterTrait` usage in `classes/`.
* Bypass dependency injection patterns used in the project.
* Couple UI logic to infrastructure logic.
* Add new classes to `inc/classes/`.

Follow existing patterns:

* Service providers (`classes/*/ServiceProvider.php`)
* Subscribers (`classes/*/Subscriber.php` implementing `SubscriberInterface`)
* Container-based wiring (`config/providers.php`)
* Strict types in all new `classes/` files

---

# 4. Testing & Validation

For every change:

1. Ensure no new PHPCS violations.
2. Ensure static analysis still passes.
3. Avoid altering unrelated test behavior.
4. Do not delete tests unless clearly obsolete.

If modifying templates:

* Validate escaping correctness.
* Ensure no functional regressions.

---

# 5. AI Working Protocol

AI must work in small, incremental changes.

After each logical change set:
- explain what changed
- explain why
- list potential edge cases

AI must NOT:

* Perform massive automated refactors without approval.
* Reorganize files without explicit instruction.
* Rewrite entire classes when a minimal fix is sufficient.

## 5.1 Git Commit & Push Policy

By default, AI may only **suggest** commit messages and must not run `git commit` or `git push`.

**Exception — Issue Workflow:** When operating under the issue-workflow skill (triggered by `/task <number>`, `issue <number>`, or `#<number>`), the agent MAY:

1. Run atomic `git commit` calls — one commit per logical, self-contained change set.
2. Run `git push` exactly once after all commits are ready, to publish the branch.
3. Create a GitHub Pull Request using the prepared PR draft.
4. Monitor PR CI status checks until all pass or a failure is detected.

Atomic commit rules:
- Each commit must pass PHPCS and static analysis before being committed.
- Commit message format: `type(scope): short description` (Conventional Commits).
- Do not squash unrelated changes into a single commit.
- Do not amend commits that have already been pushed.

---

# 6. PR Hygiene

Changes must:

* Be minimal.
* Be scoped.
* Have clear intent.
* Avoid noise in diff.
* Avoid unrelated formatting changes.

---

# 7. Security First

Always assume:

* User input is untrusted.
* Remote API responses are untrusted.
* Stored values may be tampered with.

Never:

* Store sensitive values in plain text without review.
* Introduce unsafe serialization.
* Echo unescaped dynamic data.

---

# 8. When in Doubt

Stop.
Explain the ambiguity.
Ask for clarification.

Architectural integrity is more important than speed.

# 9. Skills Activation

The repository defines AI Skills under `.aiassistant/skills/`.

Agents MUST activate the relevant skill depending on the task:

- Template or UI changes → WordPress Compliance Skill
- Structural or architectural changes → Imagify Architecture Skill
- Service modifications → Both skills
- Codebase exploration / dependency tracing → Knowledge Graph Skill
- Working on a GitHub issue → Issue Workflow Skill

# 9.1 Knowledge Graph

A pre-built dependency graph is available at `.aiassistant/graph/dependency-graph.json`.

Before exploring the codebase structure (finding a class, tracing dependencies, exploring namespaces), **read this file first**. It contains:
- `nodes`: per-file namespace, declared symbols, and imports.
- `symbol_index`: maps every fully-qualified PHP class/interface/trait/enum to its file.

Run `node bin/build-knowledge-graph.js` to refresh after structural changes (`--full` to force rebuild).

# 10. Repository Identity

Canonical GitHub repository: `wp-media/imagify-plugin`

Unless explicitly instructed otherwise, all GitHub issue, PR, and branch workflows must assume this repository.

# 11. Repository Specs

The repository may define task-specific implementation specs under:

`.aiassistant/specs/`

Specs provide detailed guidance for recurring technical problems
(e.g. PHPCS warnings, architecture migrations, WordPress compliance patterns).

When a relevant spec exists, agents must follow it in addition to:

- AGENTS.md
- the applicable skills

# AI Task Priority

When executing tasks, agents must prioritize:

1. Security
2. WordPress.org compliance
3. Architectural integrity
4. Backward compatibility
5. Minimal diffs
6. Performance

AGENTS.md remains the final authority.
