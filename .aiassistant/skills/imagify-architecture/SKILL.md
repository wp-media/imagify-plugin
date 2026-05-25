---
name: imagify-architecture
description: Use this skill when changing service structure, shared logic, bootstrapping, dependency wiring, or adding new classes to Imagify.
---

# Imagify Architecture Integrity

Enforce Imagify's architectural patterns and guide the ongoing migration from legacy to modern code.

## Core principles

- New features go in `classes/` with the `Imagify\` namespace.
- `inc/classes/` is legacy — do not add new classes there; migrate out instead.
- `declare(strict_types=1)` is required on all new files in `classes/`.
- No new `InstanceGetterTrait` / fake singleton usage in `classes/` — use DI.
- No global state or static helpers replacing services.
- No tight coupling between UI logic and infrastructure.

## Dependency injection rules

- All new services must be wired through `Imagify\Dependencies\League\Container\Container`.
- Register services in the relevant `ServiceProvider` under `classes/*/ServiceProvider.php`.
- List event subscribers in `ServiceProvider::get_subscribers()`.
- Add new service providers to `config/providers.php`.

## Structural expectations

Follow:
- Service provider pattern (`ServiceProvider` per domain module)
- Subscriber pattern (`SubscriberInterface`) for WordPress hook registration
- Container-based wiring (no `new` in business logic)
- Strict types in `classes/`

Avoid:
- New singletons or `InstanceGetterTrait` usage in new code
- `get_instance()` patterns in `classes/` code
- Direct superglobal access inside services
- Static helpers replacing services

## Legacy migration path

When touching `inc/classes/` code:
- Prefer migrating the class to `classes/` if the change is substantial.
- If a quick fix is all that is needed, fix in place without expanding legacy patterns.
- Deprecated classes and traits belong in `inc/deprecated/` — do not delete them.

## Git Operations
Follow the policy defined in AGENTS.md §6.1. Outside the issue workflow, do not run `git commit` or `git push`.
