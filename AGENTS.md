# Agent workflow — ReactWoo Flow

ReactWoo Flow is an **internal WordPress operations plugin** for product intake, support operations, and agent orchestration across the ReactWoo ecosystem. It is not a customer-facing product plugin.

## Defaults

- Prefer **one coherent thread** (read → change → verify). See `PLAN.md` for scope and backlog.
- Jira, GitHub, Confluence, and Cursor MCP **send/sync** integrations ship in v0.2.0. Do not add Playwright QA or UX review pipelines unless explicitly requested.
- **Do not** assume a single AI vendor. Provider logic belongs in `includes/providers/` behind `RWF_Provider_Interface`.
- WordPress plugin code lives in **`reactwoo-flow/`** (inner install folder). Repo root holds `README.md`, `PLAN.md`, `CHANGELOG.md`.

## Architecture

| Layer | Role |
|-------|------|
| `RWF_CPT` | Single operational object: `rwf_item` + post meta |
| `RWF_Agent` | Model-agnostic orchestrator; delegates to providers |
| `RWF_AI` | Workflow helpers: triage, specification, release notes, handoff, context builders |
| `RWF_REST` | Authenticated agent and context endpoints |
| `RWF_Intake` | Public `[reactwoo_flow_intake]` shortcode |
| `RWF_Updater` | Plugin updates via `api.reactwoo.com` (free slug `reactwoo-flow`) |
| `RWF_Integration_*` | Jira, GitHub, Confluence, Cursor MCP clients in `includes/integrations/` |
| `RWF_Automation` | Post-triage, post-spec, and post-handoff workflow shortcuts |
| `RWF_Integrations` | Integration configuration summary and connectivity tests |

## Agent types

| Type | Default provider | Executable today |
|------|------------------|------------------|
| planning | OpenAI | Yes (triage, specification) |
| release | OpenAI | Yes (release notes) |
| development | Cursor MCP | Prepare handoff only |
| qa | OpenAI (default) | Yes (QA review Markdown) |
| ux | OpenAI (default) | Yes (UX review Markdown) |

## Agent overrides

Per-item fields under **Agent Overrides** on the item editor. Precedence for each run: REST/runtime body → per-item meta → site Settings defaults.

## Key paths

- Bootstrap: `reactwoo-flow/reactwoo-flow.php`
- Prompt templates: `reactwoo-flow/prompts/*.md`
- Admin views: `reactwoo-flow/admin/views/`
- REST namespace: `reactwoo-flow/v1`
- Packaging: `package.json`, `scripts/package_zip.py` (repo root)
- Tests: `composer test` (PHPUnit with WP stubs in `tests/bootstrap.php`)

## Build and release (parity with Geo AI / Geo Core)

- **`npm run package:zip`** — local zip from repo root (versioned filename locally; CI uses unversioned `reactwoo-flow.zip`).
- **CI:** `.github/workflows/publish-update.yml` on tag `v*` — R2 upload + `POST /api/v5/updates/publish` with slug **`reactwoo-flow`**.
- **Secrets:** `R2_ACCESS_KEY_ID`, `R2_SECRET_ACCESS_KEY`, `R2_ENDPOINT`, `R2_BUCKET`, `UPDATES_PUBLISH_TOKEN`.
- **Docs:** `docs/releases-and-git-tags.md`, `.cursor/rules/release.mdc`.
- Do **not** commit `*.zip`.

## After implementation

When a change is **complete and validated**, unless the user opts out: update **`CHANGELOG.md`** and **`PLAN.md`**, bump **`RWF_VERSION`** + plugin header + **`readme.txt` Stable tag**, **commit**, **annotated tag** `v*`, **`git push origin main "vVERSION"`** (one push). Tag push triggers CI publish.

## References

- Living roadmap: `PLAN.md`
- Releases: `docs/releases-and-git-tags.md`
- Role boundaries: ReactWoo Flow = orchestration; Jira = delivery; GitHub = source; Cursor = development execution
