#!/usr/bin/env bash
# Reliable push to origin/main on Windows Git Bash (SSH, no credential GUI hang).
# Usage: bash scripts/push-main.sh [extra git push args, e.g. "v1.2.3"]
set -euo pipefail

export GIT_SSH_COMMAND="${GIT_SSH_COMMAND:-ssh -o BatchMode=yes -o ConnectTimeout=15}"

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

REMOTE="$(git remote get-url origin)"
if [[ "$REMOTE" == https://github.com/* ]]; then
  REPO_PATH="${REMOTE#https://github.com/}"
  REPO_PATH="${REPO_PATH%.git}"
  echo "Warning: origin is HTTPS ($REMOTE). Prefer: git@github.com:${REPO_PATH}.git" >&2
fi

echo "Pushing main from $(basename "$ROOT") ..."
git push origin main "$@"

git fetch origin
git status -sb | head -1
