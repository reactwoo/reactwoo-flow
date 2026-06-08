# Changelog

## Unreleased

### Added

### Changed

### Fixed

### Removed

---

## v0.3.0

Phase 4 — QA/UX agents, Jira status sync, and workflow automation.

### Added

- QA and UX review agents with prompts, field groups, REST endpoints, and admin actions.
- Jira issue status sync (`jira_status`, `POST /integrations/jira/sync-status`).
- Workflow automation settings: auto-create Jira after triage, auto-advance after specification.
- `RWF_Automation` hook class; per-item QA/UX agent overrides.

### Changed

- Default QA and UX agent providers set to OpenAI (requires API key like planning agent).

---

## v0.2.0

Phase 3 — external integrations and PHPUnit coverage.

### Added

- Jira integration: create issues from items (`RWF_Integration_Jira`), ADF helper, REST `POST /integrations/jira/create-issue`.
- GitHub integration: sync PR metadata by URL or branch (`RWF_Integration_GitHub`), REST `POST /integrations/github/sync-pull-request`.
- Confluence integration: publish specifications (`RWF_Integration_Confluence`), REST `POST /integrations/confluence/publish-specification`.
- Cursor MCP bridge: send handoff JSON to configured endpoint (`RWF_Integration_Cursor_MCP`), REST `POST /integrations/cursor/send-handoff`.
- Integration meta fields: `jira_url`, `github_pr_state`, `confluence_page_*`, `cursor_handoff_sent_at`.
- Settings: `rwf_github_token`, `rwf_confluence_parent_page_id`.
- PHPUnit suite (`tests/`, `composer.json`, `phpunit.xml.dist`) and `.github/workflows/tests.yml`.
- Admin item actions for each integration when credentials are configured.

### Changed

- Integrations panel and settings sections are active (no longer “future” placeholders).
- `RWF_AI::normalise_analysis()` is public for tests and reuse.

---

## v0.1.3

Post-MVP polish: self-updates and per-item agent overrides.

### Added

- `RWF_Updater` — WordPress plugin updates via `api.reactwoo.com` (free catalog slug `reactwoo-flow`).
- Per-item **Agent Overrides** field group (planning, release, development provider/model).
- Runtime `provider` / `model` JSON body on agent REST endpoints; admin buttons read override fields live.
- Phase documentation: `docs/phases/phase-1.md`, `phase-2.md`, `phase-3.md`.

### Changed

- `PLAN.md` assessment sections refreshed to match v0.1.2+ reality.

---

## v0.1.2

Release agent, Anthropic provider, and intake file uploads.

### Added

- Release agent workflow: `Generate Release Notes` action, `release_notes` field group, Markdown export.
- Prompt template `prompts/generate-release-notes.md`.
- REST endpoint `POST /items/{id}/generate-release-notes`.
- Anthropic Claude provider adapter (`RWF_Provider_Anthropic`) with API key setting.
- Public intake file uploads for screenshots (up to 5) and log files (up to 3) via `RWF_Uploads`.
- `AGENTS.md` and `.cursor/rules/reactwoo-flow.mdc` for Cursor agent workflow.

### Changed

- Item context export includes `release_notes` section.
- Intake form uses `multipart/form-data` and accepts uploads alongside URL fields.

---

## v0.1.1

MVP hardening: capabilities, provider adapters, and workflow polish.

### Added

- `release_task` item type (ten operational types now complete).
- Custom capabilities (`manage_rwf`, `edit_rwf_items`, etc.) granted to administrators on activation.
- Provider adapter layer: `RWF_Provider_Interface`, `RWF_Provider_OpenAI`, `RWF_Provider_Cursor_MCP`.
- Capability auto-upgrade on plugin version change (no re-activation required).
- Multi-select media library picker for screenshot attachments.

### Changed

- `rwf_item` CPT uses `capability_type` `rwf_item` instead of generic `post` caps.
- Admin menu, REST permissions, and meta auth use Flow-specific capabilities.
- `RWF_Agent::execute()` delegates to registered provider adapters.
- Successful triage auto-advances items from `new` or `needs_triage` to `confirmed`.
- Admin agent buttons surface REST error messages from the provider.

### Fixed

- Broken admin menu registration from prior edit (restored icon and submenu structure).

---

## v0.1.0

Initial plugin structure.

### Added

- WordPress plugin bootstrap (`reactwoo-flow/reactwoo-flow.php`, v0.1.0).
- Singleton orchestrator (`RWF_Plugin`) wiring CPT, settings, admin, REST, and intake.
- Custom post type `rwf_item` with extensive post meta schema (request, environment, attachments, agent execution, AI analysis, specification, future integrations).
- Ten product taxonomy options and nine item types (idea through research spike).
- Fourteen workflow statuses with controlled transition map and status history (JSON, capped at 100 entries).
- Admin dashboard with operational stats (new, needs triage, in development, ready for QA, released this month, open total, AI analysed, awaiting information).
- Inbox list with search and product/type/status filters, bulk actions (run triage, change status, archive).
- Item detail screen with grouped fields, workflow transitions, agent actions, and export buttons.
- Settings page for per-agent provider/model defaults, OpenAI API key, Cursor MCP endpoint placeholder, intake notification email, and Jira/Confluence/GitHub metadata placeholders.
- Model-agnostic agent orchestrator (`RWF_Agent`) with planning, development, QA, UX, and release agent types.
- OpenAI chat-completions execution for planning agents (triage and specification).
- Cursor development handoff preparation (context package only; no MCP execution).
- AI triage with structured JSON output normalisation and persistence to item meta.
- Markdown specification generation, editable storage, and export.
- Agent run history (JSON, capped at 50 runs per item) with export.
- Structured item context builder for future Cursor MCP consumption.
- REST API namespace `reactwoo-flow/v1`:
  - `GET /items/{id}/context`
  - `POST /items/{id}/analyse`
  - `POST /items/{id}/generate-specification`
  - `POST /items/{id}/prepare-development-handoff`
- Frontend intake shortcode `[reactwoo_flow_intake]` with honeypot spam protection and optional email notification.
- Prompt templates: `analyse-item.md`, `generate-spec.md`, `cursor-development-handoff.md`.
- Admin and frontend CSS/JS assets.
