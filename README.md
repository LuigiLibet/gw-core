# GlitchWood Core

Reusable modules for GlitchWood WordPress themes.

> **🔄 Update GW Core on a site:** log in as an administrator and open
> **`/wp-admin/admin.php?page=gwcore-updater`**, then click **Update now**.
> Details: [Updating GW Core on a site](#updating-gw-core-on-a-site).

**Docs:** [Updating a site](#updating-gw-core-on-a-site) ·
[Releasing a version](RELEASE_GUIDE.md) · [GitHub authentication](AUTHENTICATION.md) ·
[Custom blocks API](gw-custom-blocks/README.md) · [Audit](AUDIT.md)

This repository contains:

- Custom blocks
- Future AI integrations
- Shared utilities

This repo is used to generate downloadable versions for the auto-updater.

The manifest.json is automatically updated via GitHub Actions when a new version tag is created.

## Updating GW Core on a site

Each client site updates itself from this repository, but **only when an administrator
triggers it** — there is no automatic background update.

1. Log in to the site's WordPress admin as a user with `manage_options` (Administrator).
2. Open this relative URL on the site:

   ```
   /wp-admin/admin.php?page=gwcore-updater
   ```

   Example: `https://example.com/wp-admin/admin.php?page=gwcore-updater`.
   The page is intentionally hidden from the admin menu, so you must use the URL directly.
3. The page shows whether a newer version is available and the local version installed.
4. Click **Update now**.

What happens under the hood (code: [`gw-core-updater/gw-core-updater.php`](gw-core-updater/gw-core-updater.php)):

- It reads the remote manifest at
  `https://raw.githubusercontent.com/LuigiLibet/gw-core/main/manifest.json`.
- Downloads `zip_url` (the `gw-core.zip` attached to the GitHub Release) and verifies its
  SHA-256 `checksum`.
- Moves the current `gw/gw-core/` to `gw/gw-core-backup-YYYYMMDD-HHMMSS/` and puts the new
  version in its place (the old version is restored if the move fails).
- The installed version is read from the `manifest.json` bundled in `gw/gw-core/`.
- Logs every step to `wp-content/uploads/gwcore-update.log` — check it if an update fails.

The updater ships **inside** gw-core, so it updates itself with every release. It is loaded
from `gw-custom-blocks/gw-custom-blocks.php`, the one file every theme requires.

### Migrating from the legacy theme updater

Themes built before v1.4.0 carry their own updater in `gw/gw-updater.php` and
`gw/gw-core-version.php`, required from `functions.php`. Those files sit outside
`gw/gw-core/`, so they never received fixes. The migration is automatic:

1. On a legacy site, open `/wp-admin/admin.php?page=gwcore-updater` and click
   **Update now**. This first update is still performed by the legacy theme updater.
2. On the next admin page load, the new core:
   - removes the `require_once('gw/gw-updater.php');` and
     `require_once('gw/gw-core-version.php');` lines from the theme's `functions.php`;
   - deletes both legacy files once no theme file references them anymore;
   - if something in the theme still references a file, replaces it with an inert stub
     instead, so a leftover `require` can never cause a fatal error.

   A notice in the admin lists what was done. If the server needs FTP credentials to
   write files, nothing happens automatically: use **Remove legacy files** on the
   updater page instead.

> ⚠️ After the migration, the theme on the server no longer matches older local copies.
> If you re-upload an old `functions.php` that still requires the deleted files, the site
> will fatal. Pull the updated `functions.php` (or remove those two lines locally) first.

A new version only becomes available to sites after it has been released and the GitHub
Action has updated `manifest.json` — see [RELEASE_GUIDE.md](RELEASE_GUIDE.md).

## Deployment

GW Core is meant to be deployed **inside the theme** at `wp-content/themes/<theme>/gw/gw-core/`.
All asset URLs are resolved from `get_template_directory_uri() . '/gw/gw-core/'` (see the
`GW_CORE_URL` constant in `init.php`). If you place the plugin elsewhere, update that constant.

## Theme dependencies

Some included blocks rely on CSS/JS that ships with the GlitchWood theme, **not** with this
plugin. Outside the GlitchWood theme these blocks render but look or behave incompletely
unless you provide the following:

- **Share Icons** outputs `<i class="icon-facebook">`, `icon-x`, `icon-linkedin`,
  `icon-whatsapp`, `icon-envelope`. You must provide an icon font / CSS that defines those
  `.icon-*` classes, otherwise the icons are invisible.
- **Navigation Menu** (mobile) prints `#menu_trigger` and `#mobile_menu_container`. The
  show/hide toggle behaviour is wired up by the theme's JavaScript. Without it, the mobile
  menu markup is present but inert. Disable **Show mobile menu** in the block settings if the
  theme does not provide this script.

## Third-party assets

The **Slider** block uses SwiperJS, bundled locally in `included-blocks/slider/lib/swiper/`
(MIT license) — no CDN, so sites work offline. Swiper is only loaded on pages that contain a
Slider block. To bump it, replace both files with the new release from
<https://cdnjs.com/libraries/Swiper> and update `GW_SWIPER_VERSION` in `included-blocks.php`.