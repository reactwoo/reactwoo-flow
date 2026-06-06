# ReactWoo Flow

ReactWoo Flow is an internal WordPress operations plugin for ReactWoo product intake and AI triage.

## MVP Scope

This repository currently contains the Phase 1 MVP:

- WordPress plugin skeleton in `reactwoo-flow/`
- Single custom post type: `rwf_item`
- ReactWoo Flow admin dashboard
- Inbox with filters and bulk actions
- Item detail screen for request, environment, attachment, AI analysis, and future integration fields
- Settings page for OpenAI plus future Jira, Confluence, and GitHub metadata
- Secured REST endpoint for "Analyse with AI"
- Prompt templates for AI item analysis and future spec generation

Jira, GitHub, Confluence, Cursor MCP, QA, UX, and release-management integrations are intentionally placeholders for future phases.

## Installation

Copy or symlink the `reactwoo-flow/` directory into a WordPress installation's `wp-content/plugins/` directory, then activate **ReactWoo Flow** from the WordPress admin.

## AI Triage

1. Open **ReactWoo Flow > Settings**.
2. Add an OpenAI API key and model.
3. Create or edit an item in **ReactWoo Flow > Inbox**.
4. Click **Analyse with AI** to save structured AI output to the item.
