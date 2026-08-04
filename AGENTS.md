# Imagify — project instructions for AI agents

Project Configuration values below override a pipeline's derived defaults. Behaviour — how agents
run, dispatch, or report — belongs to the pipeline, not to this file.

---

## Project Configuration

| Variable | Value |
|---|---|
| `REPO` | `wp-media/imagify-plugin` |
| `SLUG` / `TEXT_DOMAIN` | `imagify` |
| `BASE_BRANCH` | `develop` |
| Protected branches | `develop`, `master` — never commit directly |
| Branch naming | `<prefix>/<issue>-<slug>`; prefix `fix` (bugs), `enhancement` (features, incl. `feat` commits), `test` |
| Test (full / unit / integration) | `composer run-tests` / `composer test-unit` / `composer test-integration` |
| Test (one group) | `vendor/bin/phpunit --configuration Tests/Integration/phpunit.xml.dist --group <Name>` |
| Lint / auto-fix | `composer phpcs` / `composer phpcbf` |
| Static analysis | `composer run-stan` |
| Build frontend | `npm run build` |
| Local env up / down / seed | `bash bin/dev-up.sh` / `bash bin/dev-down.sh` / `bash bin/dev-seed.sh` |
| E2E runner | `bash bin/test-e2e.sh` (`--headed`, `--ui`, or a spec path) |
| Local URL | `http://localhost:8888` — admin `admin` / `password` |
| Settings page | `/wp-admin/options-general.php?page=imagify` |
| Key option | `imagify_settings` (API key + config) |
| PR template | `.github/PULL_REQUEST_TEMPLATE.md` |

`.env.local` at the repo root (gitignored) holds `IMAGIFY_TESTS_API_KEY=<key>`. The same value must
exist as a GitHub secret for CI optimization tests.

---

## Traps

Things that fail in ways you will not notice.

- **Run `composer install` before any test or lint command.** Strauss namespace-prefixing is a
  Composer post-install hook. Without it, PHPCS and PHPUnit cannot resolve `Imagify\Dependencies\*`
  and fail with misleading autoload errors.
- **`WordPress.Security.NonceVerification.Missing` and `.Recommended` are deliberately suppressed**
  in `phpcs.xml`. Do not add `phpcs:ignore` for them and do not restructure code to satisfy them.
- **`assets/` is build output.** Edit `_dev/` and rebuild, or the next build discards your work.
- **There is no JavaScript unit test runner** (no Jest, no Vitest). Never invent `npm test`. Frontend
  verification is `npm run build` plus Playwright; otherwise report JS tests as `N/A`.
- **Optimization behaviour sits behind license, API-key and quota guards.** Before reporting that a
  feature works or is broken, run `bash bin/dev-seed.sh` and confirm the key landed:
  `npx @wordpress/env run cli wp option get imagify_settings --format=json | grep -c '"api_key":"[^"]\+'`
  Only once the key is present (result `1`) may you treat a guard as a genuine blocker. Never report a
  behavioural pass or failure *through* a blocked guard — report "cannot verify" with the guard's
  `file:line`. Guard locations are in the `e2e` skill.
- **A PR's number is not its issue number.** Read it back from the `gh pr create` output.
- **PHP-rendered admin UI is a frontend change.** `wp_admin_notice()`,
  `add_action( 'admin_notices', ... )` and `add_settings_error()` need frontend scoping and testing
  despite living in PHP.

---

## Architecture

Single-edition plugin — no FREE/PRO split. Namespace root `Imagify\`, PSR-4 root `classes/`.

- `classes/` — modern PSR-4, `declare(strict_types=1)` required. **New features go here.**
- `inc/classes/` — legacy classmap, `Imagify_` prefix, migrating toward `classes/`. **Add nothing here.**

In new `classes/` code:

- No new singletons or `InstanceGetterTrait` usage. The trait is still used in `classes/Plugin.php`,
  `classes/Bulk/Bulk.php` and elsewhere — that is legacy precedent, not a pattern to copy.
- No bare `add_action()` / `add_filter()`. Register hooks through a `Subscriber` implementing
  `SubscriberInterface`, listed in `ServiceProvider::get_subscribers()`.
- Service providers live at `classes/*/ServiceProvider.php` and are registered in
  `config/providers.php`.
- No global state, and no UI logic coupled to infrastructure logic. The legacy `inc/` layer is
  procedural and full of both — do not pattern-match from it.

Nonce action naming: `imagify_<feature>_<action>`.

DI container is Strauss-prefixed: `Imagify\Dependencies\League\Container\Container` — a plain
`use League\Container\Container;` is wrong here. Async work uses ActionScheduler, not wp-cron.

Frontend source is `_dev/` (Grunt, `gruntfile.js`); `_dev/` also carries its own `bud.config.js`.

---

## Coding standards

Source of truth: `composer.json` scripts, `phpcs.xml`, `phpstan.neon.dist`, CI. Never hardcode PHPCS
standards or invent lint commands.

Imagify must stay WordPress.org-compatible
([Plugin Check](https://github.com/WordPress/plugin-check/)). Evaluate any change to public APIs,
output, security, metadata, or bootstrap behaviour against it.

- PHP 7.4+, strict types in all new `classes/` files.
- Text domain `imagify`: `esc_html__( 'Label', 'imagify' )`.
- Authorization: use the project's registered capability (`'bulk-optimize'`, `'manage'`, …) in
  `current_user_can()`, never the coarse `manage_options`.
- No jQuery in new or modified JavaScript — native DOM APIs only, no inline handlers, no unsafe
  `innerHTML`. Pass nonces via `wp_localize_script`.
- Remote responses from the Imagify API are untrusted input and are this plugin's main attack
  surface. Validate and escape everything that comes back before storing or echoing it.

---

## Testing

Prefer **integration tests** (`Tests/Integration/`, annotated `@group FeatureName`) — they exercise
real WordPress context, DI wiring and hook execution. Unit tests only for pure logic with no WP
globals, container or hooks.

Scale scope to risk: LOW = the relevant `--group`; MEDIUM = `composer test-unit` plus that group;
HIGH = `composer run-tests`.

Not scope violations: generated files (`*.min.js`, `*.min.css`), lockfiles, tests mirroring changed
source, auto-formatter output.

---

## Commits and PRs

- Conventional Commits: `type(scope): short description`. Each commit passes PHPCS and static
  analysis before being made.
- Pipeline-authored commits carry a `Co-Authored-By: <model> <noreply@anthropic.com>` trailer so
  automated work stays auditable. Human-authored commits do not.
- Do not squash unrelated changes. Do not amend commits already pushed.
- Outside a pipeline working a specific ticket, only *suggest* commits — do not run `git commit` or
  `git push`.
- PR title: `Closes #<N>: <short title>` — never a Conventional-Commit prefix; that is for commits.
- The PR body needs a standalone `Closes #<N>` line, not buried in prose — that is what auto-closes.
- Apply the `Made by AI` label to every AI-created PR and issue.
- Split work vertically: each PR one complete behaviour including its tests. Never a backend PR plus
  a separate frontend PR for one feature, even when separate agents did the work.
- Do not run large automated refactors or reorganize files without being asked. The legacy-to-modern
  migration is ongoing; "cleaning up while I'm here" is not in scope.

---

## On-demand references

- **Browser testing** — admin URLs, page objects, environment variables, local running:
  [`docs/E2E_TESTING.md`](docs/E2E_TESTING.md)
- **QA procedure** — tiers, spec conventions, guard locations: the `e2e` skill (`.claude/skills/e2e/`)
- **Code structure** — use the installed pipeline's knowledge-graph skill if it provides one. A local
  builder also exists: `node bin/build-knowledge-graph.js` writes `.aiassistant/graph/` (gitignored).
  Do not mix the two graphs in one session; if neither is current, use grep rather than stopping.

---

## Priority order

When these conflict, earlier wins:

1. Security
2. WordPress.org compliance
3. Architectural integrity
4. Backward compatibility
5. Minimal diffs
6. Performance
