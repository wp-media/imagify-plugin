---
name: knowledge-graph
description: Use this skill to understand repository structure, trace class dependencies, find a symbol's file, or explore module relationships — without re-scanning the codebase from scratch.
---

# Knowledge Graph

A pre-built dependency graph lives at `.aiassistant/graph/dependency-graph.json`.

**Read it before** running grep/glob searches for class relationships, namespace exploration, or dependency tracing. It eliminates redundant file scans and speeds up the first useful response in any session.

---

## Graph shape

```json
{
  "generated_at": "<ISO timestamp>",
  "base_commit":  "<git SHA>",
  "node_count":   350,
  "nodes": {
    "classes/Optimization/Process/AbstractProcess.php": {
      "language":  "php",
      "namespace": "Imagify\\Optimization\\Process",
      "symbols": [
        { "kind": "class", "name": "AbstractProcess", "extends": [], "implements": ["ProcessInterface"] }
      ],
      "imports": [
        "Imagify\\Optimization\\Data\\DataInterface",
        "Imagify\\Media\\MediaInterface"
      ]
    }
  },
  "symbol_index": {
    "Imagify\\Optimization\\Process\\AbstractProcess": "classes/Optimization/Process/AbstractProcess.php"
  }
}
```

- **`nodes`** — keyed by relative file path. Each node has the language (`php` or `js`), declared symbols (PHP only), and all import/use statements.
- **`symbol_index`** — maps every fully-qualified PHP class / interface / trait / enum to its file path. Use this for instant "where is this class?" lookups.

---

## How to use it

### Find a class file
```
symbol_index["Imagify\\Optimization\\Process\\AbstractProcess"]
→ "classes/Optimization/Process/AbstractProcess.php"
```

### List all classes in a namespace
Filter `nodes` where `node.namespace` starts with `Imagify\Media`.

### Trace what a file depends on
Read `nodes["classes/Media/WP.php"].imports`.

### Find all files that import a given class
Search `nodes[*].imports` for the target FQN.

---

## Keeping the graph fresh

The graph records the git commit it was built from (`base_commit`). If that SHA differs from `HEAD`, run:

```bash
node bin/build-knowledge-graph.js
```

The script is incremental — it only re-parses files changed since `base_commit`. Use `--full` to force a complete rebuild.

**When to refresh:**
- After merging a branch with structural changes (new classes, namespace moves).
- Before a grooming or architecture review session.
- Automatically in CI (optional — add as a pre-commit hook or workflow step).

---

## Supported languages

| Language | What is extracted |
|---|---|
| PHP | `namespace`, `class`/`interface`/`trait`/`enum` declarations (with `extends`/`implements`), `use` imports (including grouped `\{A, B}` forms) |
| TypeScript / JavaScript | `import` (static + dynamic) and `require()` sources |
