#!/usr/bin/env bash
# Wrapper — use git_push.py for diagnostics. Optional tag refs: bash scripts/push-main.sh v1.2.3
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
ARGS=()
for ref in "$@"; do
  ARGS+=(--ref "$ref")
done
exec python "$ROOT/scripts/git_push.py" "${ARGS[@]}"
