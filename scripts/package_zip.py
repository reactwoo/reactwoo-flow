#!/usr/bin/env python3
"""
Create a release zip with canonical WordPress plugin structure.

Reads reactwooBuild from package.json (pluginFolder, zipFile, sourceDir, mainPhp).
"""

from __future__ import annotations

import json
import os
import re
import zipfile
from pathlib import Path

_DEFAULT_FOLDER = "reactwoo-flow"

INCLUDE_DIRS = [
    "admin",
    "assets",
    "includes",
    "prompts",
]

INCLUDE_FILES = [
    "reactwoo-flow.php",
    "readme.txt",
]


def _is_ci_environment() -> bool:
    return os.environ.get("CI", "").lower() in ("1", "true", "yes")


def _read_build_config(base: Path) -> dict:
    pkg_path = base / "package.json"
    if not pkg_path.is_file():
        return {}
    try:
        data = json.loads(pkg_path.read_text(encoding="utf-8"))
    except (OSError, json.JSONDecodeError):
        return {}
    cfg = data.get("reactwooBuild")
    return cfg if isinstance(cfg, dict) else {}


def _plugin_source(base: Path, cfg: dict) -> Path:
    source_dir = cfg.get("sourceDir") or cfg.get("pluginFolder") or _DEFAULT_FOLDER
    source = base / str(source_dir)
    if source.is_dir():
        return source
    return base


def _read_plugin_version(source: Path, cfg: dict, folder: str) -> str | None:
    main_php = cfg.get("mainPhp") or f"{folder}.php"
    php_path = source / str(main_php)
    if not php_path.is_file():
        return None
    try:
        text = php_path.read_text(encoding="utf-8", errors="replace")
    except OSError:
        return None
    match = re.search(r"^\s*\*\s*Version:\s*([^\s\r\n]+)", text, re.MULTILINE)
    return match.group(1).strip() if match else None


def _zip_paths(base: Path, cfg: dict) -> tuple[Path, str, str]:
    folder = cfg.get("pluginFolder") or _DEFAULT_FOLDER
    zip_name = cfg.get("zipFile") or f"{folder}.zip"
    source = _plugin_source(base, cfg)

    version_in_zip = cfg.get("versionInZipFile", True)
    if version_in_zip and not _is_ci_environment():
        version = _read_plugin_version(source, cfg, folder)
        if version:
            stem = Path(zip_name).stem
            suffix = Path(zip_name).suffix or ".zip"
            zip_name = f"{stem}-{version}{suffix}"

    return source, str(folder), str(zip_name)


def main() -> None:
    repo_root = Path(__file__).resolve().parent.parent
    cfg = _read_build_config(repo_root)
    source, root_folder, zip_name = _zip_paths(repo_root, cfg)
    out = repo_root / zip_name

    if out.exists():
        out.unlink()

    with zipfile.ZipFile(out, "w", zipfile.ZIP_DEFLATED) as zf:
        for dirname in INCLUDE_DIRS:
            dirpath = source / dirname
            if not dirpath.is_dir():
                continue
            for root, _dirs, files in os.walk(dirpath):
                for filename in files:
                    filepath = Path(root) / filename
                    rel = filepath.relative_to(source).as_posix()
                    arcname = f"{root_folder}/{rel}"
                    zf.write(filepath, arcname=arcname)

        for filename in INCLUDE_FILES:
            filepath = source / filename
            if not filepath.is_file():
                continue
            arcname = f"{root_folder}/{filename}"
            zf.write(filepath, arcname=arcname)

    with zipfile.ZipFile(out, "r") as zf:
        names = zf.namelist()
        bad_backslashes = [n for n in names if "\\" in n]
        nested = [n for n in names if n.startswith(f"{root_folder}/{root_folder}/")]
        if bad_backslashes or nested:
            raise RuntimeError(
                "Invalid zip structure detected: "
                f"backslashes={len(bad_backslashes)} nested_root={len(nested)}"
            )

    print(f"Created: {out}")


if __name__ == "__main__":
    main()
