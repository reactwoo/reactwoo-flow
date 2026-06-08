# Phase 7 — Triage delivery hints and Jira epic linking

**Status:** Complete (v0.5.0)

## Goal

Bridge triage agent output to delivery systems: apply suggested GitHub branches, link Jira issues to epics, and expose manual apply controls.

## Deliverables

| # | Item | Status |
|---|------|--------|
| 1 | `jira_epic_key` item field + Jira default epic / epic link field settings | Done |
| 2 | Epic link on Jira issue create (custom field or parent) | Done |
| 3 | Apply triage suggestions (branch + default epic) | Done |
| 4 | Automation: auto-apply after triage (optional settings) | Done |
| 5 | Item editor **Apply Triage Suggestions** action | Done |
| 6 | REST `POST /items/{id}/apply-triage-suggestions` | Done |
| 7 | PHPUnit coverage for epic resolution and branch substitution | Done |

## Settings → Jira

- **Default Epic Issue Key** — fallback epic for new issues (e.g. `RWF-100`)
- **Epic Link Custom Field** — Jira custom field id (e.g. `customfield_10014`)

## Settings → Workflow Automation

- **Auto-apply Suggested GitHub Branch** — copies triage branch to `github_branch` when empty
- **Auto-apply Default Epic Key** — copies site default epic to `jira_epic_key` when empty

## Non-goals

- Resolving `suggested_epic` names to Jira keys automatically
- GitHub webhooks
