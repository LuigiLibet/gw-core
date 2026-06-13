# GlitchWood Core

Reusable modules for GlitchWood WordPress themes.

This repository contains:

- Custom blocks
- Future AI integrations
- Shared utilities

This repo is used to generate downloadable versions for the auto-updater.

The manifest.json is automatically updated via GitHub Actions when a new version tag is created.

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

The **Slider** block loads SwiperJS from cdnjs, pinned to a fixed version with Subresource
Integrity (SRI) hashes (see `GW_SWIPER_VERSION` / `GW_SWIPER_SRI_*` in `included-blocks.php`).
When bumping the Swiper version, update the version **and** both SRI hashes together —
get them from <https://cdnjs.com/libraries/Swiper>.