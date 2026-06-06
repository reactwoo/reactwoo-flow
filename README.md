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

## Installation

Copy or symlink the `reactwoo-flow/` directory into a WordPress installation's `wp-content/plugins/` directory, then activate **ReactWoo Flow** from the WordPress admin.

## Agent Triage

1. Open **ReactWoo Flow > Settings**.
2. Choose the planning agent provider/model and add provider credentials.
3. Create or edit an item in **ReactWoo Flow > Inbox**.
4. Click **Run Triage Agent** to save structured output and execution metadata to the item.

## Specification Generation

After saving an item, click **Generate Specification** on the item detail screen. ReactWoo Flow stores the generated specification in WordPress as editable Markdown and exposes an **Export Markdown** button when a specification exists.
