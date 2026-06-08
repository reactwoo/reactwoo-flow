=== ReactWoo Flow ===
Contributors: reactwoo
Requires at least: 6.2
Tested up to: 6.8
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Stable tag: 0.5.0

Internal ReactWoo operations platform for intake, triage, and agent orchestration.

== Description ==

ReactWoo Flow manages product ideas, support tickets, bug reports, and release tasks for the ReactWoo ecosystem. It provides AI triage, specifications, release notes, and Cursor development handoff preparation.

== Installation ==

1. Upload the `reactwoo-flow` folder to `wp-content/plugins/`.
2. Activate **ReactWoo Flow** from the WordPress admin.
3. Configure agent providers under **ReactWoo Flow → Settings**.

== Changelog ==

= 0.3.0 =
* QA and UX review agents, Jira status sync, workflow automation settings.

= 0.2.0 =
* Jira, GitHub, Confluence, and Cursor MCP integrations with REST endpoints and admin actions.
* PHPUnit test suite and GitHub Actions test workflow.

= 0.1.3 =
* WordPress self-updater via api.reactwoo.com.
* Per-item agent provider/model overrides for planning, release, and development runs.

= 0.1.2 =
* Release agent, Anthropic provider, intake file uploads, agent documentation.

= 0.1.1 =
* Custom capabilities, provider adapters, release_task type, triage auto-transition.

= 0.1.0 =
* Initial plugin structure and MVP admin workflow.
