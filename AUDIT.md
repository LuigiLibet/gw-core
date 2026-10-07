# GW Core Audit

> Date: 2026-06-12
> Scope: PHP framework (`gw-custom-blocks.php`), templates for the 8 included blocks,
> editor JS core, release scripts and CI workflow.

Overall verdict: the framework is well designed (declarative block registration,
consistent escaping in almost every template, REST endpoints with `permission_callback`,
no hardcoded credentials). This audit lists the actionable findings, ordered by priority.

**Recommended priority for the next release:** #1, #2 and #6.

---

## 🔴 Security

### 1. PHP Object Injection in `gw_get_repeater_items()`
- **File:** `gw-custom-blocks/gw-custom-blocks.php:636`
- **Severity:** High — exploitable by any user allowed to edit posts.
- **Problem:** The fallback explicitly accepted strings starting with `O:`
  (serialized objects) and passed them to `@unserialize()` without restrictions. Block
  attributes are controlled by the editor, so an author could inject a serialized object
  and trigger a gadget (POP) chain if any installed plugin has exploitable classes.
- **Fix:**
  ```php
  // Remove the branch that accepts 'O:' (a repeater is never an object) and harden unserialize:
  $unserialized = @unserialize($trimmed, array('allowed_classes' => false));
  ```
- **Status:** ✅ Resolved

### 2. Unvalidated URL in Link Wrapper (stored XSS)
- **File:** `included-blocks/link-wrapper/view.php:13`
- **Severity:** High — stored XSS for roles without `unfiltered_html` (authors).
- **Problem:** The `url` attribute went straight into the `href` without `esc_url()`.
  `get_block_wrapper_attributes()` applies `esc_attr`, which does **not** block
  `javascript:alert(1)`.
- **Fix:** Apply `esc_url()` to the href and validate `target` against a whitelist
  (`_self`, `_blank`, `_parent`, `_top`), the same way `wrapperTag` is already handled in
  the menu.
  ```php
  if (!is_admin() && !empty($href)) {
      $wrapper_args['href'] = esc_url($href);
      $allowed_targets = array('_self', '_blank', '_parent', '_top');
      if (!empty($attributes['target']) && in_array($attributes['target'], $allowed_targets, true)) {
          $wrapper_args['target'] = $attributes['target'];
      }
      // ...rel
  }
  ```
- **Status:** ✅ Resolved

### 3. Swiper from a CDN without SRI
- **File:** `included-blocks.php:489`
- **Severity:** Medium — supply-chain risk.
- **Problem:** JS/CSS was loaded from jsDelivr on the frontend of every client site
  without an `integrity` attribute. If the CDN were compromised, every site would run
  third-party code.
- **Fix:** Bundle Swiper locally inside the plugin, or add SRI
  (`integrity` + `crossorigin`) to the registered handles.
- **Status:** ✅ Resolved

---

## 🟡 Bugs / fragility

### 4. Hidden (undocumented) theme dependencies
- **Severity:** Medium — the block breaks outside the GlitchWood theme.
- **Problem:** The plugin assumed things it didn't document anywhere:
  - **Icon CSS:** Share Icons outputs `<i class="icon-facebook">`
    (`included-blocks/share-icons/view.php:83`) but no plugin stylesheet defines those
    classes. If the theme doesn't ship them, the icons are invisible.
  - **Mobile menu JS:** `included-blocks/navigation-menu/view.php:106` prints
    `#menu_trigger` and `#mobile_menu_container`, but the JS that activates them lives in
    the theme, not the plugin.
- **Fix:** Document both dependencies in the README, or resolve them inside the plugin
  (ship base icon CSS and the mobile toggle JS).
- **Status:** ✅ Resolved

### 5. Inconsistent editor detection
- **Files:** `included-blocks/navigation-menu/view.php:52`,
  `included-blocks/link-wrapper/view.php:42,56`
- **Severity:** Medium — incorrect render in the editor preview.
- **Problem:** There were three different ways to detect "I'm in the editor":
  `gw_in_editor()` (the robust one, in the framework), `defined('REST_REQUEST') && REST_REQUEST`,
  and `is_admin()`. `is_admin()` is **false** during an editor REST render, so the Link
  Wrapper likely rendered as `<a>` in the preview instead of `<div>` — exactly what the
  comment says it wants to avoid.
- **Fix:** Unify all editor-context detection on `gw_in_editor()`.
- **Status:** ✅ Resolved

### 6. `GW_CORE_VERSION` used but never defined
- **File:** `init.php:53,65,84`
- **Severity:** Medium-High — possible fatal error on PHP 8.
- **Problem:** The constant was passed to `wp_enqueue_*` as the cache-busting version, but
  there was no `define('GW_CORE_VERSION', ...)` anywhere in the repo. On PHP 8 an undefined
  constant is a fatal `Error`; on PHP 7 it is a warning and the literal string
  `"GW_CORE_VERSION"` is used (broken cache-busting).
- **Fix:** Define the constant, ideally reading the version from `manifest.json`:
  ```php
  if (!defined('GW_CORE_VERSION')) {
      $manifest = @json_decode(@file_get_contents(__DIR__ . '/manifest.json'), true);
      define('GW_CORE_VERSION', $manifest['version'] ?? '1.0.0');
  }
  ```
- **Status:** ✅ Resolved

---

## 🟢 Maintenance

### 7. Inconsistent path conventions in `init.php`
- **File:** `init.php:44,76` vs `gw-custom-blocks.php`
- **Problem:** `init.php` hardcoded `get_stylesheet_directory_uri() . '/components/gw-core/'`
  while `gw-custom-blocks.php` uses `/gw/gw-core/`. Two different conventions in the same
  plugin — one of the two enqueues loaded assets from the wrong path.
- **Status:** ✅ Resolved

### 8. `uniqid()` for slider IDs
- **File:** `included-blocks/slider/view.php:49`
- **Problem:** Not cryptographic and can collide. The ID isn't used for init (that's done
  via the `.gw-slider` class), so it can be removed or replaced with a static counter.
- **Status:** ✅ Resolved

### 9. Hand-written PHP-serialize parser in JS (dead code)
- **File:** `gw-custom-blocks/lib/utils.js:28`
- **Problem:** ~90 lines of fragile regex that in practice never ran, because
  `phpSerialize` already emits JSON. High-risk dead code.
- **Fix:** Delete it and keep only the JSON path.
- **Status:** ✅ Resolved

### 10. Commented-out debug `print_r`
- **File:** `included-blocks/link-wrapper/view.php:70`
- **Problem:** Commented-out debug line. Minor cleanup.
- **Status:** ✅ Resolved

### 11. `setInterval` polling to wait for `GW_CUSTOM_BLOCKS`
- **File:** `gw-custom-blocks/core/block-registry.js:14`
- **Problem:** Waits for the global variable by polling. Script dependencies are already
  declared correctly via `wp_register_script`, so `wp_localize_script` should guarantee the
  order and the polling is probably unnecessary.
- **Decision:** Kept by design. It is low-cost defensive code (5s timeout) that protects
  against plugins that defer or reorder scripts. Removing it risks block-registration
  regressions with no functional gain. Evaluated and retained.
- **Status:** ☑️ Evaluated (no changes)

### 12. Manual token flow in docs/scripts
- **Files:** `AUTHENTICATION.md`, `scripts/push-with-token.sh`
- **Problem:** They documented a manual PAT flow that invites pasting tokens into the
  terminal (where they stay in the shell history). With the `gh` CLI or the macOS
  credential helper, these could be retired.
- **Status:** ✅ Resolved

---

## ✅ What's good
- Correct, consistent escaping in meta-tag, footer-text, share-icons and the demo block.
- `wrapperTag` / `tag` validated against whitelists.
- `permission_callback` on both REST endpoints (`/posts`, `/terms`).
- Complete `.gitignore` (includes macOS `._*` files, which are not tracked).
- Graceful handling of a deleted menu in the editor (informative placeholders).
- No hardcoded credentials in the repo.
