#!/usr/bin/env python3
"""
Copy ai-handoff templates and Cursor rule to another plugin repo.

Usage:
  python scripts/init-ai-handoff.py --target /path/to/repo
  python scripts/init-ai-handoff.py --family geo
  python scripts/init-ai-handoff.py --family geo --force
"""

from __future__ import annotations

import argparse
import shutil
from pathlib import Path

REPO_ROOT = Path(__file__).resolve().parent.parent

# ReactWoo Geo Core + satellites (Local Sites plugin paths).
GEO_FAMILY_TARGETS = [
    Path(r"C:/Users/User/Local Sites/reactwoo/app/public/wp-content/plugins/reactwoo-geocore"),
    Path(r"C:/Users/User/Local Sites/reactwoo/app/public/wp-content/plugins/reactwoo-geocore-pro"),
    Path(r"C:/Users/User/Local Sites/reactwoo/app/public/wp-content/plugins/reactwoo-geo-ai"),
    Path(r"C:/Users/User/Local Sites/reactwoo/app/public/wp-content/plugins/reactwoo-geo-optimise"),
    Path(r"C:/Users/User/Local Sites/reactwoo/app/public/wp-content/plugins/reactwoo-geo-commerce"),
]

GEO_GIT_DOC = GEO_FAMILY_TARGETS[0] / "docs" / "git-push-windows.md"
GEO_GIT_RULE = GEO_FAMILY_TARGETS[0] / ".cursor" / "rules" / "git-push-windows.mdc"

# Reset each init so copies stay task-neutral.
EPHEMERAL_FILES = ("cursor-output.md", "test-output.md", "current-task.md")


def bootstrap_repo(target: Path, force: bool, family: str | None) -> None:
    src_handoff = REPO_ROOT / "ai-handoff"
    dst_handoff = target / "ai-handoff"
    src_rule = REPO_ROOT / ".cursor" / "rules" / "ai-handoff.mdc"
    dst_rule = target / ".cursor" / "rules" / "ai-handoff.mdc"
    dst_git_rule = target / ".cursor" / "rules" / "git-push-windows.mdc"

    if not src_handoff.is_dir():
        raise SystemExit(f"Source ai-handoff/ missing: {src_handoff}")

    if dst_handoff.exists() and not force:
        print(f"Skip {dst_handoff} (exists; use --force to overwrite)")
    else:
        if dst_handoff.exists():
            shutil.rmtree(dst_handoff)
        shutil.copytree(src_handoff, dst_handoff)
        for name in EPHEMERAL_FILES:
            src = src_handoff / name
            if src.is_file():
                shutil.copy2(src, dst_handoff / name)
        print(f"Copied {src_handoff} -> {dst_handoff}")

    if src_rule.is_file():
        dst_rule.parent.mkdir(parents=True, exist_ok=True)
        if dst_rule.exists() and not force:
            print(f"Skip {dst_rule} (exists; use --force to overwrite)")
        else:
            shutil.copy2(src_rule, dst_rule)
            print(f"Copied {src_rule} -> {dst_rule}")
    else:
        print(f"Warning: rule not found at {src_rule}")

    if family == "geo" and GEO_GIT_DOC.is_file():
        dst_doc = target / "docs" / "git-push-windows.md"
        if dst_doc.exists() and not force:
            print(f"Skip {dst_doc} (exists; use --force)")
        else:
            dst_doc.parent.mkdir(parents=True, exist_ok=True)
            shutil.copy2(GEO_GIT_DOC, dst_doc)
            print(f"Copied {GEO_GIT_DOC} -> {dst_doc}")

    git_rule_src = GEO_GIT_RULE if GEO_GIT_RULE.is_file() else None
    if git_rule_src and git_rule_src.is_file():
        dst_git_rule.parent.mkdir(parents=True, exist_ok=True)
        if dst_git_rule.exists() and not force:
            print(f"Skip {dst_git_rule} (exists; use --force to overwrite)")
        else:
            shutil.copy2(git_rule_src, dst_git_rule)
            print(f"Copied {git_rule_src} -> {dst_git_rule}")

    print(f"Done: {target.name}")


def main() -> None:
    parser = argparse.ArgumentParser(description="Bootstrap ai-handoff/ in target repo(s).")
    parser.add_argument(
        "--target",
        help="Absolute path to a destination git repo root",
    )
    parser.add_argument(
        "--family",
        choices=("geo",),
        help="Bootstrap all repos in a known plugin family",
    )
    parser.add_argument(
        "--force",
        action="store_true",
        help="Overwrite existing ai-handoff files and Cursor rule",
    )
    args = parser.parse_args()

    if not args.target and not args.family:
        parser.error("Provide --target or --family")

    if args.target and args.family:
        parser.error("Use --target or --family, not both")

    targets: list[Path] = []
    if args.family == "geo":
        targets = GEO_FAMILY_TARGETS
    else:
        targets = [Path(args.target).resolve()]

    for target in targets:
        if not target.is_dir():
            print(f"Skip missing directory: {target}")
            continue
        bootstrap_repo(target, args.force, args.family)

    print("See ai-handoff/README.md and reactwoo-geocore/docs/ai-handoff-workflow.md (Geo family).")


if __name__ == "__main__":
    main()
