# Decisions

> Architecture and workflow choices that should survive beyond one chat thread.

| Date | Decision | Rationale |
|------|----------|-----------|
| | | |

## ReactWoo defaults

- **ChatGPT/Codex:** diagnose, spec, acceptance criteria, review patches.
- **Cursor:** apply patches, local edits, run smallest validation, write `cursor-output.md`.
- **Repo markdown:** shared memory — not chat history.
- **No duplicate fallbacks:** fix root cause; do not stack defensive workarounds.
