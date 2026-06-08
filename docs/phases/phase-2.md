# Phase 2 — Post-MVP polish

**Status:** Complete (v0.1.3)

## Goal

Harden the plugin for day-to-day internal use: self-updates from the ReactWoo catalog, per-item agent flexibility, and formal phase documentation.

## Delivered / in flight

| # | Item | Status |
|---|------|--------|
| 1 | WordPress self-updater via `api.reactwoo.com` (`RWF_Updater`) | Done |
| 2 | Per-item agent override fields (planning, release, development) | Done |
| 3 | REST + admin JS pass runtime `provider` / `model` overrides | Done |
| 4 | Phase docs (`docs/phases/`) | Done |
| 5 | Refresh stale `PLAN.md` assessment sections | Done |

## Override precedence

For each agent run:

1. **Runtime** — JSON body on REST agent endpoints (admin buttons read the form fields live)
2. **Per-item** — `override_{agent_type}_provider` / `_model` meta (saved with the item)
3. **Site default** — Settings → agent provider/model per type

## Exit criteria

- [x] Plugin checks for updates on **Plugins** screen without a license JWT
- [x] Operators can override provider/model per item or per click
- [x] PHPUnit foundation deferred to Phase 3

## Next

See [phase-3.md](phase-3.md) for integrations and automated tests.
