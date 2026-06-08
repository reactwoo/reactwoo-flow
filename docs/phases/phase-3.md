# Phase 3 — Integrations and quality

**Status:** Complete (v0.2.0)

## Goal

Connect ReactWoo Flow to external delivery systems and add automated regression coverage.

## Delivered

| # | Item | Status |
|---|------|--------|
| 1 | PHPUnit suite (transitions, normalisation, overrides, REST permissions) | Done |
| 2 | Jira REST client — create issue from item | Done |
| 3 | Cursor MCP bridge — POST handoff to configured endpoint | Done |
| 4 | GitHub — sync PR metadata by URL or branch | Done |
| 5 | Confluence — publish specification Markdown | Done |

## REST endpoints (integrations)

| Method | Route | Action |
|--------|-------|--------|
| POST | `/items/{id}/integrations/jira/create-issue` | Create Jira issue |
| POST | `/items/{id}/integrations/github/sync-pull-request` | Sync PR state |
| POST | `/items/{id}/integrations/confluence/publish-specification` | Publish spec page |
| POST | `/items/{id}/integrations/cursor/send-handoff` | Send handoff JSON |

## Configuration

| Integration | Required settings |
|-------------|-------------------|
| Jira | `rwf_jira_url`, email, API token, project key |
| Confluence | Jira credentials + `rwf_confluence_space_key` (+ optional parent page ID) |
| GitHub | `rwf_github_repository` (`owner/repo`) + `rwf_github_token` |
| Cursor MCP | `rwf_cursor_mcp_endpoint` |

## Tests

```bash
composer install
composer test
```

CI runs on push/PR to `main` via `.github/workflows/tests.yml`.

## Non-goals (unchanged)

- Replacing Jira sprint tracking or GitHub as source of truth
- Running development agents inside WordPress PHP
