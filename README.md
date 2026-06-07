# ReactWoo Flow

ReactWoo Flow is an internal WordPress operations plugin for ReactWoo product intake, support operations, and agent orchestration.

ReactWoo Flow is not the AI engine. It captures requests, builds structured context, routes work to the selected agent/provider, stores outputs, and coordinates the delivery lifecycle.

## MVP Scope

This repository currently contains the Phase 1 MVP:

- WordPress plugin skeleton in `reactwoo-flow/`
- Single custom post type: `rwf_item`
- ReactWoo Flow admin dashboard
- Inbox with filters and bulk actions
- Item detail screen for request, environment, attachment, agent execution, analysis output, specification, and future integration fields
- Settings page for agent provider/model defaults plus provider, Jira, Confluence, and GitHub metadata
- Secured REST endpoints for "Run Triage Agent", "Generate Specification", and "Prepare Cursor Handoff"
- Prompt templates for agent item analysis, specification generation, and Cursor development handoff
- Editable Markdown specification storage and export
- Cursor handoff JSON package preparation and export
- Agent run history and JSON export for orchestration auditability
- Controlled workflow status transitions with status history
- Authenticated structured item context endpoint for future Cursor MCP consumption
- Manual full context JSON export from item detail pages
- Frontend shortcode intake form for website/support submissions

Jira, GitHub, Confluence, Cursor MCP, QA, UX, and release-management integrations are intentionally placeholders for future phases.

## Architecture

```text
ReactWoo Flow
    -> Agent Orchestrator
    -> Selected AI Provider
    -> Result Returned
```

ReactWoo Flow manages:

- Agent type
- Provider
- Model
- Prompt template
- Context payload
- Output
- Execution status
- Historical agent run records
- Workflow status transitions and lifecycle history

Cursor is the preferred future development agent for code generation, bug fixes, refactoring, test creation, development planning, and implementation assistance. ReactWoo Flow prepares structured context for Cursor; Cursor performs development work through a future lightweight MCP bridge.

## Cursor Development Handoff

After triage and/or specification generation, click **Prepare Cursor Handoff** on the item detail screen. ReactWoo Flow stores a pending development-agent package containing:

- item context
- saved triage output
- generated specification
- suggested branch, QA checklist, and developer notes
- future Jira/GitHub/release placeholders
- provider/model/prompt/status metadata

The **Export Handoff JSON** button downloads that package for manual use now and future MCP bridge consumption later.

## Cursor Context Endpoint

Future MCP bridge clients can read structured item context from:

```text
GET /wp-json/reactwoo-flow/v1/items/{id}/context
```

The response includes request fields, workflow status/history, agent analysis output, generated specification, development handoff metadata, agent run history, and future integration placeholders. The endpoint is authenticated and read-only; ReactWoo Flow prepares context while Cursor remains responsible for development execution.

The same payload can be downloaded from an item detail page with **Export Context JSON**.

## Agent Run History

Each triage, specification, and Cursor handoff action appends a compact run-history record to the item. The item detail screen shows recent runs and exposes **Export Agent Runs** for the full JSON audit trail.

## Workflow Orchestration

Item detail pages include controlled status transitions for moving work through intake, triage, specification, development handoff, QA, release, and closure states. Each transition records the previous status, next status, user, timestamp, and optional note.

## Installation

Copy or symlink the `reactwoo-flow/` directory into a WordPress installation's `wp-content/plugins/` directory, then activate **ReactWoo Flow** from the WordPress admin.

## Website Intake Form

Add the shortcode below to a WordPress page to capture website/support submissions directly into the ReactWoo Flow inbox:

```text
[reactwoo_flow_intake]
```

Optional defaults:

```text
[reactwoo_flow_intake product="reactwoo_core" item_type="support_ticket" title="Send ReactWoo Support Request"]
```

Submissions create `rwf_item` posts with source `Website Form`, medium priority, and workflow status `Needs Triage`.

## Agent Triage

1. Open **ReactWoo Flow > Settings**.
2. Choose the planning agent provider/model and add provider credentials.
3. Create or edit an item in **ReactWoo Flow > Inbox**.
4. Click **Run Triage Agent** to save structured output and execution metadata to the item.

## Specification Generation

After saving an item, click **Generate Specification** on the item detail screen. ReactWoo Flow stores the generated specification in WordPress as editable Markdown and exposes an **Export Markdown** button when a specification exists.
