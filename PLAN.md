# ReactWoo Flow — Implementation Plan

Authoritative roadmap for the ReactWoo Flow WordPress plugin. Update this document when major architectural decisions change.

---

# Vision

ReactWoo Flow is the **operational orchestration platform** for the entire ReactWoo ecosystem. It is not a product idea inbox alone.

It manages intake, context building, AI triage, specifications, agent routing, and release coordination across:

- Product Ideas
- Feature Requests
- Customer Support
- Bug Reports
- UX/UI Issues
- Security Issues
- Technical Debt
- Documentation Requests
- Research Tasks
- Release Tasks

### Role boundaries

| System | Owns |
|--------|------|
| **ReactWoo Flow** | Intake, AI triage, context building, specifications, agent routing, release coordination |
| **Jira** | Sprint tracking, epics, stories, bugs |
| **GitHub** | Source control, pull requests, build status |
| **Cursor** | Development, refactoring, bug fixing, test generation, AI agent execution |

ReactWoo Flow owns orchestration and context. Cursor owns development execution. Jira owns delivery tracking. GitHub owns source control. AI providers are interchangeable execution engines selected according to the task.

---

# Architecture

## Repository layout

```text
reactwoo-flow/                    # Git repo root (README, CHANGELOG, PLAN)
└── reactwoo-flow/                # WordPress plugin directory (install this folder)
    ├── reactwoo-flow.php         # Bootstrap
    ├── includes/                 # PHP classes
    ├── admin/views/              # Admin page templates
    ├── assets/                   # CSS/JS
    └── prompts/                  # Agent prompt templates (Markdown)
```

## Core classes

| Class | Responsibility |
|-------|----------------|
| `RWF_Plugin` | Singleton; loads dependencies and initialises hooks |
| `RWF_CPT` | `rwf_item` post type, field schema, meta helpers, workflow transitions |
| `RWF_Admin` | Dashboard, inbox, item editor, settings UI, exports, bulk actions |
| `RWF_Settings` | WordPress options for agents, providers, integration placeholders |
| `RWF_Agent` | Model-agnostic agent orchestrator (prepare + execute) |
| `RWF_AI` | Workflow helpers: triage, specification, handoff, context builders |
| `RWF_REST` | Authenticated REST endpoints |
| `RWF_Integrations` | Integration configuration summary and connectivity tests |
| `RWF_Automation` | Workflow automation hooks (auto Jira, auto-advance) |
| `RWF_Intake` | Frontend shortcode and public submission handler |

## Data model

- **Storage:** WordPress CPT `rwf_item` + post meta (`_rwf_*` prefix). No custom database tables.
- **Content:** `post_title`, `post_content` (description), `post_author`.
- **Structured data:** ~80+ meta fields grouped into request, environment, attachments, agent execution, AI analysis, specification, and future integrations.
- **History:** `agent_runs` and `status_history` stored as JSON strings in post meta.

## Agent abstraction

Each agent execution record supports:

- `agent_name`, `agent_type`, `provider`, `model`
- `prompt_template`, `input_context`, `output`, `status`
- `started_at`, `completed_at`, `error`

Agent types (configured in settings, executed via `RWF_Agent`):

| Agent | Preferred provider | Status |
|-------|-------------------|--------|
| Planning | OpenAI / GPT | **Executable** (triage + specification) |
| Development | Cursor MCP | **Prepare only** (handoff package; MCP send when configured) |
| QA | OpenAI / GPT | **Executable** (Markdown review) |
| UX | OpenAI / GPT | **Executable** (Markdown review) |
| Release | OpenAI / GPT | **Executable** (release notes) |

Provider registry: `openai`, `anthropic`, `cursor_mcp` (prepare-only), `manual`.

## AI layer

The original OpenAI-only approach is **deprecated in favour of** the model-agnostic `RWF_Agent` orchestrator. MVP still uses OpenAI for planning agents; architecture must never assume a single vendor.

Execution flow:

```text
Admin/REST action
  → RWF_AI (workflow + context building)
    → RWF_Agent::prepare_agent() / execute()
      → Provider adapter (OpenAI today)
    → Persist output + append agent_runs
```

---

# Current MVP

Build **only** what is listed below until MVP is declared complete. Design for future integrations; do not implement them yet.

## In scope (MVP)

| Feature | Status |
|---------|--------|
| Plugin structure | Done |
| Dashboard | Done |
| Inbox (filters, bulk actions) | Done |
| Item management (CRUD, workflow transitions) | Done |
| Technical environment capture | Done |
| Attachments (URL/text fields; media library; intake uploads) | Done |
| AI triage (planning agent via OpenAI) | Done |
| Structured AI outputs | Done |
| Specification generation (Markdown) | Done |
| Release notes generation (release agent) | Done |
| Cursor handoff preparation + JSON export | Done |
| Item context REST endpoint + export | Done |
| Frontend intake shortcode | Done |
| Agent run history | Done |
| Settings (agents, OpenAI key, integration placeholders) | Done |
| `PLAN.md` | Done |
| `CHANGELOG.md` | Done |

## Integrations (implemented)

- Jira — create issues, sync status (v0.2.0–v0.3.0)
- GitHub — PR metadata and CI status sync (v0.2.0, CI v0.3.1)
- Confluence — publish specifications (v0.2.0)
- Cursor MCP — handoff delivery when endpoint configured (v0.2.0)

## Still out of scope

- Playwright / automated QA pipeline
- Remote Cursor agent execution inside WordPress
- Scheduled integration health monitoring

---

# Phases

Formal phase docs live under `docs/phases/`.

| Phase | Focus | Status |
|-------|-------|--------|
| [Phase 1](docs/phases/phase-1.md) | MVP foundation (intake → triage → spec → handoff → publish) | Complete (v0.1.2) |
| [Phase 2](docs/phases/phase-2.md) | Self-updater, per-item agent overrides, doc refresh | Complete (v0.1.3) |
| [Phase 3](docs/phases/phase-3.md) | Integrations (Jira, Cursor MCP, GitHub, Confluence) + PHPUnit | Complete (v0.2.0) |
| [Phase 4](docs/phases/phase-4.md) | QA/UX agents, Jira sync, workflow automation | Complete (v0.3.0) |
| [Phase 5](docs/phases/phase-5.md) | Integration health, exports, inbox polish | Complete (v0.3.1) |

---

# Future Integrations

## Jira

- Settings placeholders exist (`rwf_jira_url`, email, API token, project key).
- Item meta field `jira_id` for manual linking today.
- **Done:** create issue (v0.2.0), sync status (v0.3.0). Future: epic/story from triage output.

## GitHub

- Settings placeholder `rwf_github_repository`.
- Item fields: `github_branch`, `pr_url`, `release_version`.
- AI suggests branch names; future: create branch/PR webhooks.

## Cursor MCP

- Settings placeholder `rwf_cursor_mcp_endpoint`.
- `GET /items/{id}/context` and handoff JSON export ready for bridge consumption.
- Development agent provider returns explicit "not connected" until bridge ships.

## Confluence

- Settings placeholder `rwf_confluence_space_key`.
- Future: publish specifications to space.

## QA / UX agents

- **Done (v0.3.0)** — QA and UX review agents with Markdown output and admin/REST execution.
- Playwright / visual regression remains out of scope.

---

# Backlog

Prioritised work after documentation baseline. Re-order as priorities shift.

## MVP completion / polish

1. ~~Add missing item type **`release_task`**~~ (done v0.1.1)
2. ~~True file upload on intake form~~ (done v0.1.2)
3. ~~Richer REST error responses in admin JS~~ (done v0.1.1)
4. ~~Dedicated capability/`manage_rwf`~~ (done v0.1.1)
5. ~~Auto-transition workflow status after successful triage~~ (done v0.1.1)

## Agent orchestrator

6. ~~Provider adapter interface (`RWF_Provider_Interface`) extracted from `RWF_Agent`~~ (done v0.1.1)
7. ~~Anthropic adapter~~ (done v0.1.2)
8. ~~Release agent: changelog/release-notes prompt + execution path~~ (done v0.1.2)
9. ~~Per-item agent override UI (choose provider/model for a single run)~~ (done v0.1.3)

## Integrations (post-MVP)

10. ~~Jira REST client: create issue from item~~ (done v0.2.0)
11. ~~Cursor MCP bridge: POST handoff package to configured endpoint~~ (done v0.2.0)
12. ~~GitHub: link PR, poll CI status~~ (done v0.2.0 — PR sync by URL/branch)
13. ~~Confluence: publish specification page~~ (done v0.2.0)

## Operations

14. ~~Packaging script / CI (align with other ReactWoo plugins)~~ (done — `package.json`, `scripts/package_zip.py`, `publish-update.yml`)
15. ~~`.cursor/rules` and `AGENTS.md`~~ (done)
16. ~~Automated tests (PHPUnit for CPT transitions, REST permissions, analysis normalisation)~~ (done v0.2.0)
17. ~~Register **`reactwoo-flow`** in API `UPDATES_FREE_SLUGS`~~ (done in reactwoo-api v0.1.36)
18. ~~WordPress self-updater (`RWF_Updater`)~~ (done v0.1.3)

---

# Completed

- [x] v0.1.0 plugin skeleton and `rwf_item` CPT with full meta schema
- [x] Admin dashboard, inbox, item editor, settings
- [x] Workflow status machine with history
- [x] Model-agnostic `RWF_Agent` orchestrator (OpenAI execution path)
- [x] AI triage with structured JSON output
- [x] Specification generation and Markdown export
- [x] Cursor handoff package preparation and JSON export
- [x] REST endpoints for analyse, specification, handoff, context
- [x] Frontend intake shortcode with notification email
- [x] Agent run audit trail
- [x] Integration placeholder fields (Jira, GitHub, Confluence)
- [x] Project documentation (`README.md`, `CHANGELOG.md`, `PLAN.md`)
- [x] v0.1.2 — Release agent, Anthropic provider, intake uploads, AGENTS.md + Cursor rule
- [x] R2/API publish pipeline (`package.json`, `package_zip.py`, `publish-update.yml`, release docs)
- [x] v0.1.3 — Self-updater, per-item agent overrides, phase docs
- [x] v0.2.0 — Jira, GitHub, Confluence, Cursor MCP integrations + PHPUnit

---

# Technical Decisions

| Decision | Rationale | Date |
|----------|-----------|------|
| Single CPT `rwf_item` for all operational objects | One inbox, one workflow, shared context model | v0.1.0 |
| Post meta only (no custom tables) | Faster MVP; WordPress-native; sufficient for internal volume | v0.1.0 |
| Custom admin UI (not native CPT screens) | Controlled UX for grouped fields, agent actions, exports | v0.1.0 |
| `RWF_Agent` as orchestration layer | Deprecates OpenAI-only `RWF_AI`; supports future providers | v0.1.0 |
| Planning via OpenAI; development via Cursor (prepare-only) | Matches role boundaries; Cursor executes externally | v0.1.0 |
| Prompt templates as Markdown files in `prompts/` | Editable without code deploy; version controlled | v0.1.0 |
| JSON agent run + status history in meta | Audit trail without new tables; capped arrays | v0.1.0 |
| Nested install folder `reactwoo-flow/reactwoo-flow/` | Repo root holds docs; inner folder is WP plugin slug | v0.1.0 |
| Custom capabilities on activation | Operational access separate from generic `edit_posts` | v0.1.1 |
| Provider adapter interface | OpenAI logic extracted; future Anthropic/Cursor adapters plug in cleanly | v0.1.1 |
| Auto-advance to `confirmed` after triage | Reduces manual status clicks for intake workflow | v0.1.1 |

---

# Known Issues

| Issue | Severity | Notes |
|-------|----------|-------|
| ~~Missing `release_task` item type~~ | — | Added in v0.1.1 |
| ~~Intake form attachments URL-only~~ | — | File uploads added v0.1.2 |
| ~~`edit_posts` capability for all Flow admin~~ | — | Replaced with `edit_rwf_items` / `manage_rwf` in v0.1.1 |
| ~~Admin JS hides provider error details~~ | — | REST messages surfaced in v0.1.1 |
| `RWF_AI` class name implies vendor-specific AI | Low | Works as workflow facade; consider rename to `RWF_Workflow` later |
| Multiple executable providers | Low | OpenAI and Anthropic when API keys configured; Cursor MCP prepare-only |
| Bulk "analyse" can timeout on large batches | Low | Synchronous OpenAI calls in admin-post handler |
| Legacy option `rwf_openai_model` fallback | Low | Migration shim in `RWF_Settings::get_agent_model()` |
| No uninstall cleanup | Low | Meta remains on deactivation (WordPress default) |

---

# Assessment snapshot (2026-06-07)

Internal review baseline for continuing development.

## Current structure

- **Folders:** `includes/`, `includes/providers/`, `admin/views/`, `assets/`, `prompts/`
- **Classes:** 11 + 3 providers (`RWF_Capabilities`, `RWF_Updater`, `RWF_Uploads`, `RWF_Provider_OpenAI`, `RWF_Provider_Anthropic`, `RWF_Provider_Cursor_MCP`, …)
- **Admin pages:** Dashboard, Inbox, Item (hidden submenu), Settings
- **CPTs:** `rwf_item` only
- **Settings:** Agent provider/model per type, OpenAI key, Cursor MCP endpoint, intake email, Jira/Confluence/GitHub placeholders
- **AI:** Planning agent via OpenAI; handoff preparation without execution; prompt-driven JSON/Markdown outputs
- **Database:** WordPress posts + postmeta only
- **REST:** 5 routes under `reactwoo-flow/v1`

## Gap vs vision

| Area | Assessment |
|------|------------|
| Orchestration platform scope | **Aligned** — schema and workflow cover full operational model |
| Agent abstraction | **Done** — provider interface + OpenAI/Cursor MCP adapters |
| Release task type | **Done** (v0.1.1) |
| File uploads | **Done** — admin media library + intake multipart uploads |
| Self-updates | **Done** (v0.1.3) — `RWF_Updater` via free catalog slug |
| Per-item agent overrides | **Done** (v0.1.3) |
| Future integrations | **Designed, not built** — as intended for MVP |

## Technical debt / refactoring opportunities

- ~~Extract provider adapters from `RWF_Agent`~~ (done v0.1.1)
- Rename or clarify `RWF_AI` as workflow service (not vendor-specific)
- ~~Tighten capabilities~~ (done v0.1.1)
- ~~Media upload on public intake form~~ (done v0.1.2)
- Consider flattening repo layout (`reactwoo-flow/` as direct plugin root) for simpler Local Sites install
