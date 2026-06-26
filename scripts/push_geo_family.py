#!/usr/bin/env python3
"""
Push main on each ReactWoo Geo family repo sequentially.
Stops on first failure and prints that repo's diagnostic (no blind retries).

Usage:
  python scripts/push_geo_family.py
  python scripts/push_geo_family.py --include-flow
"""

from __future__ import annotations

import argparse
import subprocess
import sys
from pathlib import Path

FLOW_ROOT = Path(__file__).resolve().parent.parent
GIT_PUSH = FLOW_ROOT / "scripts" / "git_push.py"

GEO_REPOS = [
    Path(r"C:/Users/User/Local Sites/reactwoo/app/public/wp-content/plugins/reactwoo-geocore"),
    Path(r"C:/Users/User/Local Sites/reactwoo/app/public/wp-content/plugins/reactwoo-geocore-pro"),
    Path(r"C:/Users/User/Local Sites/reactwoo/app/public/wp-content/plugins/reactwoo-geo-ai"),
    Path(r"C:/Users/User/Local Sites/reactwoo/app/public/wp-content/plugins/reactwoo-geo-optimise"),
    Path(r"C:/Users/User/Local Sites/reactwoo/app/public/wp-content/plugins/reactwoo-geo-commerce"),
]


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("--include-flow", action="store_true")
    args = parser.parse_args()

    repos = list(GEO_REPOS)
    if args.include_flow:
        repos.insert(0, FLOW_ROOT)

    failed = False
    for repo in repos:
        if not repo.is_dir():
            print(f"SKIP missing: {repo}")
            continue
        print(f"\n--- {repo.name} ---")
        proc = subprocess.run(
            [sys.executable, str(GIT_PUSH), "--repo", str(repo)],
            cwd=FLOW_ROOT,
        )
        if proc.returncode != 0:
            print(f"STOP: {repo.name} push failed (exit {proc.returncode}). Fix diagnostic above.", file=sys.stderr)
            failed = True
            break

    return 1 if failed else 0


if __name__ == "__main__":
    sys.exit(main())
