# Phase 8 — GitHub webhooks

**Status:** Complete (v0.6.0)

## Goal

Receive GitHub `pull_request` and `status` events to refresh linked flow items without manual sync.

## Deliverables

| # | Item | Status |
|---|------|--------|
| 1 | Webhook settings (enable + secret) and URL display in Settings | Done |
| 2 | `POST /integrations/github/webhook` with HMAC signature validation | Done |
| 3 | Match items by `pr_url` or `github_branch` | Done |
| 4 | Update PR metadata and CI status from webhook payloads | Done |
| 5 | PHPUnit coverage for signature and payload handling | Done |

## REST endpoints

| Method | Route | Action |
|--------|-------|--------|
| POST | `/integrations/github/webhook` | Receive GitHub webhook events |

## Settings → GitHub

- **Enable GitHub Webhook** — must be Yes for the endpoint to process events
- **GitHub Webhook Secret** — shared secret for `X-Hub-Signature-256` validation

## Non-goals

- Creating branches or pull requests from WordPress
- GitHub App installation flow
