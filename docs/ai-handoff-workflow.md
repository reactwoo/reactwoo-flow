# AI handoff workflow (ChatGPT ↔ Cursor, no API)

This document describes the **file bridge** pattern for ReactWoo development: persistent repo markdown replaces long chat threads and manual copy-paste of entire conversations.

## Roles

| Tool | Role |
|------|------|
| **ChatGPT / Codex** | Architect, debugger, reviewer, next-step decisions |
| **Cursor** | Patch applier, inline edits, local commands, `cursor-output.md` |
| **You** | Run tests, paste `test-output.md`, shuttle files |
| **Repo files** | Shared memory (`ai-handoff/`, `AGENTS.md`, plugin docs) |

## Why files instead of API

- No OpenAI ↔ Cursor integration required.
- Only the **delta** moves between tools (task packet + output packet).
- `known-issues.md` stops repeated failed fixes across fresh Cursor sessions.
- Cursor **Project rules** and **AGENTS.md** hold project logic that should not live in chat history.

## Directory layout

```text
ai-handoff/
  README.md
  current-task.md      ← planner → cursor
  cursor-output.md     ← cursor → planner
  test-output.md       ← human → planner
  known-issues.md      ← both (persistent)
  decisions.md         ← planner (persistent)
```

Optional project memory (any repo):

```text
AGENTS.md
docs/project-brief.md
docs/architecture.md
```

## One full cycle

### 1. Planner creates the task

Fill `ai-handoff/current-task.md` using:

- Problem / Expected / Actual
- Files involved
- What we already tried (from `known-issues.md`)
- Acceptance test
- Do not touch

**From ReactWoo Flow:** prepare development handoff on an item, then **Export AI handoff files** — this generates `current-task.md` from triage + specification.

### 2. Cursor executes

Open the target plugin repo in Cursor and prompt:

```text
Read ai-handoff/current-task.md and ai-handoff/known-issues.md.
Implement the smallest safe fix. Update ai-handoff/cursor-output.md when done.
```

### 3. You test locally

Run the acceptance test. Paste the failing command and last ~80 lines into `ai-handoff/test-output.md`.

### 4. Planner reviews

Send only:

```markdown
## Cursor Output
(contents of cursor-output.md)

## Test Output
(contents of test-output.md)

## Current Question
What should be done next?
```

### 5. Update memory

- If a fix failed: append to `known-issues.md` under **Tried** / **Do not retry**.
- If an architecture choice was made: add a row to `decisions.md`.

## Cursor rules (enforced in `.cursor/rules/ai-handoff.mdc`)

- Read `known-issues.md` before changing code.
- Do not rewrite unrelated files.
- Identify root cause before patching.
- After one failed fix on the same symptom: stop guessing; fill the blocked section in `cursor-output.md`.
- Always update `cursor-output.md` after a pass.

## Golden prompt (planner)

Never ask: “Fix this.”

Ask:

> Find the root cause, list the smallest safe fix, identify files to change, then give me a patch. Do not change unrelated behaviour.

## Bootstrap another plugin repo

From **reactwoo-flow** repo root:

```bash
python scripts/init-ai-handoff.py --target "C:/path/to/reactwoo-geocore"
```

Copies `ai-handoff/` templates and `.cursor/rules/ai-handoff.mdc`.

## Relation to ReactWoo Flow MCP

- **MCP / JSON handoff** — optional automation when a bridge endpoint exists.
- **File handoff** — works everywhere, no credentials, manual or Flow export.

Use file handoff when you are shuttling between ChatGPT and Cursor on Local Sites without wiring APIs.
