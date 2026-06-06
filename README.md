# ReactWoo Flow

ReactWoo Flow is an internal WordPress operations plugin for ReactWoo product intake and AI triage.

## MVP Scope

This repository currently contains the Phase 1 MVP:

- WordPress plugin skeleton in `reactwoo-flow/`
- Single custom post type: `rwf_item`
- ReactWoo Flow admin dashboard
- Inbox with filters and bulk actions
- Item detail screen for request, environment, attachment, AI analysis, specification, and future integration fields
- Settings page for OpenAI plus future Jira, Confluence, and GitHub metadata
- Secured REST endpoints for "Analyse with AI" and "Generate Specification"
- Prompt templates for AI item analysis and specification generation
- Editable Markdown specification storage and export

Jira, GitHub, Confluence, Cursor MCP, QA, UX, and release-management integrations are intentionally placeholders for future phases.

## Installation

Copy or symlink the `reactwoo-flow/` directory into a WordPress installation's `wp-content/plugins/` directory, then activate **ReactWoo Flow** from the WordPress admin.

## AI Triage

1. Open **ReactWoo Flow > Settings**.
2. Add an OpenAI API key and model.
3. Create or edit an item in **ReactWoo Flow > Inbox**.
4. Click **Analyse with AI** to save structured AI output to the item.

## Specification Generation

After saving an item, click **Generate Specification** on the item detail screen. ReactWoo Flow stores the generated specification in WordPress as editable Markdown and exposes an **Export Markdown** button when a specification exists.
