# Releases, git tags, and R2 publish

ReactWoo Flow uses the same **tag-triggered CI publish** pattern as Geo Core and Geo AI satellites.

## Build manifest (`package.json` → `reactwooBuild`)

| Key | Value |
|-----|--------|
| `pluginFolder` | `reactwoo-flow` — directory inside the zip / `wp-content/plugins/` |
| `pluginSlug` | `reactwoo-flow` — R2 artifact path + API updates slug |
| `zipFile` | `reactwoo-flow.zip` |
| `sourceDir` | `reactwoo-flow` — inner folder containing plugin PHP (repo has docs at root) |
| `mainPhp` | `reactwoo-flow.php` |

Local build:

```bash
npm run package:zip
```

Produces `reactwoo-flow-0.1.2.zip` locally (version suffix). CI sets `CI=true` and emits unversioned `reactwoo-flow.zip` for R2.

## Version bump (before tag)

1. **`Version:`** in `reactwoo-flow/reactwoo-flow.php`
2. **`RWF_VERSION`** constant — same value
3. **`readme.txt`** **`Stable tag:`** and changelog entry
4. **`CHANGELOG.md`** at repo root

## Tag and push

From the **repo root** (not the inner plugin folder):

```bash
git add -A
git commit -m "Release VERSION — short summary"

git tag -a "vVERSION" -m "ReactWoo Flow vVERSION"

git push origin main "vVERSION"
```

**Do not** chain `commit && tag && push` on Windows. **Do not** push `main` and the tag as two separate steps unless the combined push failed.

Pushing tag `v*` triggers **`.github/workflows/publish-update.yml`**, which:

1. Runs `python scripts/package_zip.py`
2. Uploads to R2 at `plugins/reactwoo-flow/{version}/reactwoo-flow.zip`
3. Calls `POST https://api.reactwoo.com/api/v5/updates/publish`

## GitHub Actions secrets

Same org/repo secrets as Geo plugins (from `reactwoo-api` `.env` / `env.example`):

| Secret | Purpose |
|--------|---------|
| `R2_ACCESS_KEY_ID` | Cloudflare R2 upload |
| `R2_SECRET_ACCESS_KEY` | Cloudflare R2 upload |
| `R2_ENDPOINT` | R2 S3-compatible endpoint URL |
| `R2_BUCKET` | Bucket name |
| `UPDATES_PUBLISH_TOKEN` | Bearer token for updates publish API |

## API / license configuration

Publish succeeds only if slug **`reactwoo-flow`** is accepted by the updates API.

- **Internal / free distribution:** add `reactwoo-flow` to **`UPDATES_FREE_SLUGS`** on the ReactWoo API server (comma-separated with `reactwoo-geocore` if needed).
- **Licensed product:** create a **`packages`** row in the license DB with slug `reactwoo-flow` and require JWT on `/api/v5/updates/check`.

WordPress sites using **reactwoo-api-manager** (or equivalent updater) pass slug **`reactwoo-flow`** when checking for updates.

## Manual workflow dispatch

In GitHub Actions, run **Publish ReactWoo Flow Update** with optional version/channel/rollout overrides without creating a tag.
