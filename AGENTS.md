# Imagify — AI Agent Instructions

This file is read by AI coding agents (Claude Code, GitHub Copilot, etc.) to understand the project structure, conventions, and available skills.

---

## §1 — Repository identity

- **Repo:** `wp-media/imagify-plugin`
- **Plugin slug:** `imagify`
- **Single edition** (no FREE/PRO split)
- **PHP namespace root:** `Imagify\`
- **PSR-4 root:** `classes/`

---

## §2 — Technology stack

- PHP 7.3+ (strict types, PSR-4 autoloading via Composer)
- WordPress plugin APIs (hooks, options, WP-CLI, AJAX)
- League Container (DI container + service providers + event subscribers)
- ActionScheduler (async background jobs)
- Strauss (Composer dependency namespace prefixing → `Imagify\Dependencies\`)
- JavaScript / Grunt (_dev/ pipeline → assets/)
- Playwright + TypeScript for E2E testing

---

## §3 — Code structure

```
classes/          New PHP code goes here (PSR-4, Imagify\ namespace)
inc/              Legacy PHP includes (procedural, no namespace)
inc/classes/      Legacy class files migrating toward classes/
assets/           Compiled frontend assets (do not edit directly)
_dev/             Frontend source (JS, SCSS, Grunt config)
views/            PHP view templates
Tests/            PHPUnit tests
Tests/e2e/        Playwright E2E tests (TypeScript)
bin/              CLI scripts (dev-up, dev-down, dev-seed, build-knowledge-graph)
docs/             Documentation (E2E_TESTING.md, etc.)
.aiassistant/     Skill files for AI assistants
.claude/agents/   Claude Code sub-agents (qa-engineer, e2e-qa-tester)
```

---

## §4 — Architecture rules

1. **New features** go in `classes/` with `declare(strict_types=1)` and `Imagify\` namespace.
2. **Do not** add new uses of `InstanceGetterTrait` — new singletons should be registered in the DI container.
3. **New admin functionality** must be an `EventSubscriberInterface` registered via a service provider.
4. **Legacy `inc/`** — minimize changes; only bug fixes and small adjustments.
5. **Strauss vendor** — never edit files under `vendor/` directly; they are overwritten on `composer install`.

---

## §5 — Skills

The `.aiassistant/skills/` directory contains skill files that configure AI assistant behavior for specific tasks:

| Skill | Activates when |
|-------|----------------|
| `imagify-architecture` | Writing or reviewing PHP code in `classes/` or `inc/` |
| `wordpress-compliance` | Reviewing any PHP for WP coding standards |
| `knowledge-graph` | Exploring dependencies via `dependency-graph.json` |
| `issue-workflow` | Triaging or implementing a GitHub issue |

---

## §6 — E2E testing agents

Two Claude Code sub-agents in `.claude/agents/` support QA workflows:

| Agent | Use when |
|-------|----------|
| `qa-engineer` | Validating a PR against its ticket spec (strategy selection, test report) |
| `e2e-qa-tester` | Driving the browser via Playwright, converting flows to spec files |

Full E2E testing documentation: [`docs/E2E_TESTING.md`](docs/E2E_TESTING.md)

The test directory is `Tests/e2e/` (capital T, consistent with the existing `Tests/` PHPUnit directory).

The E2E suite runs in CI via `.github/workflows/e2e.yml`. The `IMAGIFY_TESTS_API_KEY` GitHub secret must be configured for optimization tests to run.

---

## §7 — Local development

```bash
# Start the local WordPress environment (Docker via wp-env)
bash bin/dev-up.sh

# Stop the environment (preserves data)
bash bin/dev-down.sh

# Full wipe
bash bin/dev-down.sh --clean

# Seed test data (idempotent)
bash bin/dev-seed.sh

# Run E2E tests
cd Tests/e2e && npm test
```

- Site: `http://localhost:8888`
- Admin: `http://localhost:8888/wp-admin` — `admin` / `password`

---

## §8 — GitHub issue workflow

Use the `issue-workflow` skill to triage and implement issues:

```
/issue-workflow <issue-number>
```

Issues are fetched from `wp-media/imagify-plugin` and cached to `.TemporaryItems/Issues/imagify-plugin/`.

---

## §9 — Commit conventions

- No `Co-Authored-By` lines in commits.
- Follow the commit style in `git log --oneline`.
- No WIP commits on PRs targeting `develop`.
