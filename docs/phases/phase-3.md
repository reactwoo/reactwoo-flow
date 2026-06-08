# Phase 3 — Integrations and quality

**Status:** Planned

## Goal

Connect ReactWoo Flow to external delivery systems and add automated regression coverage.

## Planned work

| # | Item | Notes |
|---|------|-------|
| 1 | PHPUnit suite | CPT status transitions, REST permissions, analysis normalisation |
| 2 | Jira REST client | Create/link issues from triage output |
| 3 | Cursor MCP bridge | POST handoff package to configured endpoint |
| 4 | GitHub integration | Link PRs, poll CI status |
| 5 | Confluence publish | Push specification Markdown to a space |

## Prerequisites

- Phase 2 complete (updater + per-item overrides stable)
- API credentials configured in **Settings** placeholders
- Cursor MCP endpoint available for development agent execution

## Non-goals

- Replacing Jira sprint tracking or GitHub as source of truth
- Running development agents inside WordPress PHP
