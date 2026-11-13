# Phase 9 — Support foundations (support plan Phase 0)

**Status:** Complete in source (v0.7.0). Not released or deployed.

## Goal

Make Flow safe to extend into a customer support system before any remote site access is added. Customer data must not reach AI providers, the public form must only offer customer choices, and support work needs its own workflow.

Full plan: *ReactWoo Flow — Support sessions: review and plan* (Claude doc, 13 Nov 2026).

## Deliverables

| # | Item | Status |
|---|------|--------|
| 1 | `RWF_Redact`: excluded fields + text redaction for every external agent | Done |
| 2 | Redaction in `build_item_context`, development handoff, Cursor context and `RWF_Agent::prepare_agent` | Done |
| 3 | xAI (Grok) provider, OpenAI-compatible, `grok-4.3` default | Done |
| 4 | Token usage and exact provider cost stored on every agent run | Done |
| 5 | Public intake limited to customer request types and the current product catalogue | Done |
| 6 | Support statuses and transitions; support items go to Diagnosing after triage | Done |
| 7 | Dashboard **In Support** count | Done |
| 8 | PHPUnit coverage (redaction, usage, support workflow) | Done |

## Redaction

Excluded fields (never sent): `customer_email`, `reporter`, `license_key`, `site_url`, `screenshots`, `log_files`. Filter: `rwf_ai_excluded_fields`.

Text redaction: emails → `[email]`, URLs → `[url]` (except github.com, reactwoo.com, atlassian.net without a query string; filter `rwf_ai_safe_url_hosts`), IPv4 → `[ip]`, UUIDs and licence-shaped keys → `[key]`, password/token/key assignments and Bearer/Basic headers → `[secret]`, opaque tokens (24+ chars, 4+ digits and 4+ letters) → `[key]`.

The REST context endpoint and **Export Context JSON** are redacted too. Staff still see full data on the item screen.

## Usage and cost

Each agent run stores `usage`: `input_tokens`, `output_tokens`, `total_tokens`, `cost_usd`, `cost_source`. xAI reports the exact charge (`usage.cost_in_usd_ticks`, 1 USD = 10^10 ticks). OpenAI and Anthropic report tokens only; `cost_usd` stays `null` for them because Flow never estimates a charge it was not given. Failed runs keep any reported cost.

## Support workflow

New statuses: Awaiting Site Access, Diagnosing, Fix Proposed, Awaiting Customer Approval, Applying Fix, Verifying Fix, Resolved.

After triage, `support_ticket` items, and `bug_report` items from the website form, move to **Diagnosing**. Other items still move to **Confirmed**. A support item escalates to development by moving from Diagnosing to Confirmed. A fix cannot skip Verifying.

## Settings

- **xAI (Grok) API Key** (Providers). Set the planning agent provider to **xAI Grok** and model `grok-4.3` to use it.

## Not in this phase

- Customer identity, entitlement and My Account support screens (Phase 1 of the support plan)
- Remote diagnostics through the connector and bridge (Phase 2)
- Converting AI cost into support minutes and debiting the ledger (Phase 3)
- Auto-closing resolved items after 7 days
- Agent run history is capped at 50 runs per item; billing in Phase 3 needs its own durable record.
