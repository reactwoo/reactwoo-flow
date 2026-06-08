# Phase 1 — MVP foundation

**Status:** Complete (v0.1.0 → v0.1.2)

## Goal

Ship a usable internal operations platform: intake, triage, specifications, release notes, and Cursor handoff preparation — without external integrations.

## Delivered

| Area | Outcome |
|------|---------|
| Data model | `rwf_item` CPT with grouped meta fields and workflow statuses |
| Admin UI | Dashboard, inbox, item editor, settings |
| Agents | Model-agnostic `RWF_Agent` with OpenAI, Anthropic, and Cursor MCP adapters |
| Planning | AI triage (JSON) and Markdown specification generation |
| Release | Release notes agent + export |
| Development | Handoff package preparation (no remote execution) |
| REST | Context, analyse, specification, release notes, handoff endpoints |
| Intake | Shortcode with email notification and file uploads |
| Security | Custom capabilities (`manage_rwf`, `edit_rwf_items`, …) |
| Publish | CI zip → R2 + `api.reactwoo.com` updates catalog (`reactwoo-flow` free slug) |

## Exit criteria

- [x] Internal team can intake, triage, spec, and hand off items end-to-end
- [x] Agent runs are auditable (`agent_runs` meta)
- [x] Documentation baseline (`PLAN.md`, `CHANGELOG.md`, `AGENTS.md`)

## References

- `PLAN.md` — architecture and backlog
- `CHANGELOG.md` — version history
