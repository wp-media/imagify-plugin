---
derived_sha: PLACEHOLDER          # stamped by testrail-explorer-agent on first real explore
source_files: [imagify.php, classes/MCP/AbilitiesSubscriber.php]
last_explored: PLACEHOLDER        # YYYY-MM-DD, stamped by Explorer
---

# Foundation — environment, prerequisites, seeding (Imagify local QA)

Non-DOM, low-volatility grounding shared by every feature spec. The run agent loads this
file plus the per-feature spec before executing any case. Locator sections below marked
**captured by Explorer** are placeholders until a real explore fills them — do not trust them
until `last_explored` is a real date.

## Environment
- Base URL:  http://localhost:10038          (wp-env, local)
- WP admin:  http://localhost:10038/wp-admin/
- Login:     admin / admin via /wp-login.php
             role-based: getByLabel("Username or Email Address"), getByLabel("Password", { exact: true })
             submit:     getByRole("button", { name: "Log In" })
- WP version requirement: >= 6.9 (the Abilities API is a no-op below this).

## Prerequisites
- Imagify API key:  expected location `settings.local.json` (key `IMAGIFY_API_KEY`).
                    NOT verified in this worktree — **captured by Explorer** (confirm the real
                    path/option on first explore; correct this line if it differs).
                    Seed via the "Set API key" helper below, not the UI.
- Settings page:    /wp-admin/options-general.php?page=imagify
- Capability gate:  the `imagify_capacity` filter, NOT a direct current_user_can() call.

## Test users (for permission cases)
| Role          | Login          | imagify_capacity | Seed                         |
|---------------|----------------|------------------|------------------------------|
| administrator | admin / admin  | full             | exists by default            |
| editor        | <seed>         | limited          | **captured by Explorer**     |
| subscriber    | <seed>         | denied           | **captured by Explorer**     |

## Seeding helpers (WP-CLI / REST — run via Bash, NOT the UI)
Deterministic state setup. The run agent runs these before opening the browser. Each
helper that mutates state has a matching teardown (see "Teardown helpers"). Commands marked
**captured by Explorer** are stubs until verified against the live env on first explore.

```bash
# Set / clear API key                 # captured by Explorer (confirm option name + storage)
wp option patch update imagify_settings api_key "<key>"
wp option patch update imagify_settings api_key ""

# Toggle an arbitrary setting
wp option patch update imagify_settings <key> <value>

# Force an attachment into a known optimized / unoptimized state   # captured by Explorer
wp ...

# Create a non-admin test user (permission cases)                  # captured by Explorer
wp user create <login> <email> --role=<role>
```

## Teardown helpers (LIFO undo — captured by Explorer where noted)
```bash
# Restore an attachment to its pre-optimization state             # captured by Explorer
wp ...

# Re-apply a settings snapshot taken before a mutation
wp option update imagify_settings '<json snapshot>' --format=json

# Delete a seeded test user
wp user delete <login> --yes --reassign=1
```

## Drift detection inputs
`source_files` (above) are the globs whose latest commit is compared against `derived_sha`.
`/testrail-setup --check` reports this spec stale if any source file changed after the stamp.
