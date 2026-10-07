# Quick Guide: Releasing a New Version

> Releasing publishes a new `manifest.json`. Client sites do **not** update on their own —
> see [Updating GW Core on a site](README.md#updating-gw-core-on-a-site).

## Full Flow

### 1. Make your code changes
Edit whatever files you need.

### 2. Create the new version

You have **two options**:

#### Option A: From Cursor / VS Code (Recommended)
1. Press `Cmd+Shift+P` (Mac) or `Ctrl+Shift+P` (Windows/Linux)
2. Type "Tasks: Run Task"
3. Select:
   - **"Release: Patch Version"** → for small fixes (1.2.3 → 1.2.4)
   - **"Release: Minor Version"** → for new features (1.2.3 → 1.3.0)
   - **"Release: Major Version"** → for breaking changes (1.2.3 → 2.0.0)

#### Option B: From the terminal
```bash
# Patch version (1.2.3 → 1.2.4)
./scripts/release.sh patch "Description of changes"

# Minor version (1.2.3 → 1.3.0)
./scripts/release.sh minor "New features"

# Major version (1.2.3 → 2.0.0)
./scripts/release.sh major "Breaking changes"
```

The script automatically:
- ✅ Detects the latest tag
- ✅ Calculates the new version
- ✅ Commits your changes
- ✅ Creates the tag
- ✅ Pushes the commit and the tag (if Git is authenticated — see `AUTHENTICATION.md`)

### 3. If the push failed: authenticate and push

The commit and tag are already created locally. The recommended fix is to authenticate
once with `gh auth login` (or the macOS credential helper) and push:

```bash
git push origin main
git push origin vX.Y.Z
```

As a one-off fallback you can use the token script (the token ends up in your shell
history — see `AUTHENTICATION.md`):

```bash
./scripts/push-with-token.sh YOUR_TOKEN_HERE
```

The token is only used temporarily and is removed from the remote after the push.

### 4. Check the workflow

The GitHub Actions workflow runs automatically, builds `gw-core.zip`, attaches it to the
GitHub Release and updates `manifest.json`. To check it:

1. Go to: https://github.com/LuigiLibet/gw-core/actions
2. Or run `git pull origin main` after a few minutes to see the updated manifest

## Flow Summary

```
Code changes
    ↓
./scripts/release.sh patch "message"
    ↓
(push, if the script couldn't)
    ↓
GitHub Actions builds the ZIP and updates manifest.json
    ↓
✅ Version published
    ↓
On each site: /wp-admin/admin.php?page=gwcore-updater → "Update now"
```

## Useful Files

- `scripts/release.sh` - Main script for creating versions
- `scripts/push-with-token.sh` - Fallback script to push with a token
- `.vscode/tasks.json` - Editor tasks to run releases from the IDE
- `AUTHENTICATION.md` - Detailed authentication guide

## Tips

- **Store your token safely** (use a password manager)
- The script detects the latest tag automatically, so you don't need to track it
- If you forget to push, the commits and tags are still local — just push them
- The workflow takes 1-2 minutes to update `manifest.json`
