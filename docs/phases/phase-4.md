# Phase 4 — QA/UX agents and workflow automation

**Status:** Complete (v0.3.0)

## Goal

Close the remaining agent gaps (QA and UX) and add lightweight workflow automation plus Jira status sync.

## Delivered

| # | Item | Status |
|---|------|--------|
| 1 | QA agent — prompt, execution, `qa_review` field group | Done |
| 2 | UX agent — prompt, execution, `ux_review` field group | Done |
| 3 | Jira status sync (`jira_status` meta) | Done |
| 4 | Workflow automation settings (auto Jira on triage, auto-advance after spec) | Done |
| 5 | Per-item QA/UX agent overrides | Done |

## REST endpoints

| Method | Route | Action |
|--------|-------|--------|
| POST | `/items/{id}/run-qa-review` | QA review agent |
| POST | `/items/{id}/run-ux-review` | UX review agent |
| POST | `/items/{id}/integrations/jira/sync-status` | Pull Jira issue status |

## Settings → Workflow Automation

- **Auto-create Jira Issue After Triage** — when enabled and Jira is configured
- **Auto-advance to Ready for Development** — after specification when item is in Ready for Specification

## Non-goals

- Playwright / visual regression automation
- Remote Cursor agent execution inside WordPress
