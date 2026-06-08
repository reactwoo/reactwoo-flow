# Phase 6 — Delivery visibility and extended automation

**Status:** Complete (v0.4.0)

## Goal

Make delivery state visible in the inbox and extend workflow automation to Confluence, Cursor MCP, and GitHub after specification or handoff.

## Deliverables

| # | Item | Status |
|---|------|--------|
| 1 | Inbox columns: Jira key/status, GitHub CI | Done |
| 2 | Inbox integration filter (linked Jira, linked PR, unlinked) | Done |
| 3 | Automation: auto-publish Confluence after specification | Done |
| 4 | Automation: auto-send Cursor MCP after handoff | Done |
| 5 | Automation: auto-sync GitHub PR after handoff | Done |
| 6 | Inbox bulk **Sync GitHub PR** action | Done |
| 7 | REST `GET /items` list with filters | Done |
| 8 | Dashboard stat: Ready for Release | Done |

## REST endpoints

| Method | Route | Action |
|--------|-------|--------|
| GET | `/items` | List items with optional status/product/integration filters |

## Settings → Workflow Automation

- **Auto-publish Confluence After Specification** — when Confluence is configured and no page is linked
- **Auto-send Cursor MCP After Handoff** — POST handoff when endpoint is configured
- **Auto-sync GitHub PR After Handoff** — refresh PR metadata when GitHub is configured

## Non-goals

- Jira epic/story hierarchy from triage
- GitHub webhooks
- Scheduled background sync jobs
