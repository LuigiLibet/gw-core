<?php
/**
 * GW Core Updater
 *
 * Updates this copy of gw-core in place from the release manifest published by
 * the GitHub Action, and migrates themes away from the legacy theme-level updater
 * (gw/gw-updater.php + gw/gw-core-version.php), which could never update itself.
 *
 * Admin page (hidden from the menu): /wp-admin/admin.php?page=gwcore-updater
 *
 * Loaded from gw-custom-blocks/gw-custom-blocks.php (the one file every theme
 * requires) and auto-loaded by init.php; require_once keeps it single.
 *
 * @package GW Core
 */

namespace GlitchWood\Core\Updater;

if (!defined('ABSPATH')) {
	exit;
}

const MANIFEST_URL = 'https://raw.githubusercontent.com/LuigiLibet/gw-core/main/manifest.json';
const PAGE_SLUG    = 'gwcore-updater';
const NONCE_ACTION = 'gwcore_update_action';
const NOTICE_KEY   = 'gwcore_migration_notice';

// Legacy theme files => a string that only the original legacy file contains.
// A file is only stubbed if it still has its signature (never clobber custom code).
const LEGACY_FILES = array(
	'gw-updater.php'      => 'namespace GlitchWood\\GWCoreUpdater;',
	'gw-core-version.php' => "define( 'GW_CORE_VERSION'",
);

// Admin-page hook registered by the legacy gw/gw-updater.php.
const LEGACY_MENU_CALLBACK = 'GlitchWood\\GWCoreUpdater\\gwcore_register_admin_page';

/* ---------------------------------------------------------
 * Paths & versions
 * --------------------------------------------------------- */

/** Root of this gw-core copy (…/gw/gw-core). */
function core_dir() {
	return dirname(__DIR__);
}

/** Folder that holds gw-core, its backups and the legacy files (…/gw). */
function gw_dir() {
	return dirname(core_dir());
}

/** Root of the theme that ships gw-core. */
function theme_dir() {
	return dirname(gw_dir());
}

/** "v1.3.1" and "1.3.1" must compare equal: version_compare() treats a leading "v" as text. */
function normalize_version($version) {
	return ltrim(trim((string) $version), 'vV');
}

/**
 * Installed version: the highest of the bundled manifest.json and the legacy
 * GW_CORE_VERSION constant (ZIPs built before v1.4.0 shipped the previous
 * release's manifest, so neither source alone is reliable during the migration).
 */
function installed_version() {
	$candidates = array();

	$manifest = json_decode((string) @file_get_contents(core_dir() . '/manifest.json'), true);
	if (is_array($manifest) && !empty($manifest['version'])) {
		$candidates[] = normalize_version($manifest['version']);
	}
	if (defined('GW_CORE_VERSION')) {
		$candidates[] = normalize_version(GW_CORE_VERSION);
	}
	if (!$candidates) {
		return '0.0.0';
	}

	usort($candidates, 'version_compare');
	return end($candidates);
}

// Keep GW_CORE_VERSION available for themes that read it once the legacy
// gw-core-version.php is gone. Deferred so the legacy file (required after
// gw-core by functions.php) can still define it without a "already defined" warning.
add_action('after_setup_theme', function () {
	if (!defined('GW_CORE_VERSION')) {
		define('GW_CORE_VERSION', installed_version());
	}
});

/* ---------------------------------------------------------
 * Remote manifest & update
 * --------------------------------------------------------- */

/** @return array|false The remote manifest, or false on any error. */
function get_remote_manifest() {
	$response = wp_remote_get(MANIFEST_URL, array('timeout' => 20));

	if (is_wp_error($response)) {
		log_message('Could not fetch remote manifest: ' . $response->get_error_message());
		return false;
	}

	$code = wp_remote_retrieve_response_code($response);
	if (200 !== (int) $code) {
		log_message('Remote manifest responded with HTTP ' . $code);
		return false;
	}

	$data = json_decode(wp_remote_retrieve_body($response), true);
	if (!is_array($data) || empty($data['version']) || empty($data['zip_url']) || !is_string($data['zip_url'])) {
		log_message('Remote manifest is invalid or incomplete.');
		return false;
	}

	return $data;
}

/** Make sure $wp_filesystem is ready. Returns false if it could not be initialized. */
function init_filesystem($creds = false) {
	global $wp_filesystem;

	require_once ABSPATH . 'wp-admin/includes/file.php';
	if (!empty($wp_filesystem) && false === $creds) {
		return true;
	}
	return (bool) WP_Filesystem($creds);
}

/**
 * Download, verify and install the release in the remote manifest.
 *
 * The current gw-core is moved to gw/gw-core-backup-YYYYMMDD-HHMMSS and restored
 * if the new copy can't be put in place.
 *
 * @return true|\WP_Error
 */
function run_update(array $manifest) {
	global $wp_filesystem;

	if (!current_user_can('manage_options')) {
		return new \WP_Error('gwcore_permissions', 'You are not allowed to update GW Core.');
	}
	if (!init_filesystem()) {
		return new \WP_Error('gwcore_filesystem', 'Could not access the filesystem.');
	}
	// Never reinstall or downgrade: an older release may not ship this updater, which
	// would leave the site without any way to update.
	if (!version_compare(normalize_version($manifest['version']), installed_version(), '>')) {
		return new \WP_Error('gwcore_not_newer', 'The remote version is not newer than the installed one.');
	}

	require_once ABSPATH . 'wp-admin/includes/file.php';
	$tmp_file = download_url($manifest['zip_url']);
	if (is_wp_error($tmp_file)) {
		log_message('Error downloading ZIP: ' . $tmp_file->get_error_message());
		return $tmp_file;
	}

	if (!empty($manifest['checksum']) && 0 === strpos($manifest['checksum'], 'sha256:')) {
		$expected = substr($manifest['checksum'], strlen('sha256:'));
		if (!hash_equals($expected, (string) hash_file('sha256', $tmp_file))) {
			$wp_filesystem->delete($tmp_file);
			log_message('SHA-256 checksum mismatch. Update aborted.');
			return new \WP_Error('gwcore_checksum', 'The downloaded file failed the integrity check.');
		}
	}

	$target_dir = core_dir();
	$temp_dir   = gw_dir() . '/gw-core-temp';
	$backup_dir = gw_dir() . '/gw-core-backup-' . gmdate('Ymd-His');

	if ($wp_filesystem->is_dir($temp_dir)) {
		$wp_filesystem->rmdir($temp_dir, true);
	}
	$wp_filesystem->mkdir($temp_dir, FS_CHMOD_DIR);

	$result = unzip_file($tmp_file, $temp_dir);
	$wp_filesystem->delete($tmp_file);
	if (is_wp_error($result)) {
		log_message('Error unzipping: ' . $result->get_error_message());
		$wp_filesystem->rmdir($temp_dir, true);
		return $result;
	}

	// The release ZIP has the files at its root; also accept a single wrapping folder.
	$new_core_dir = null;
	if ($wp_filesystem->exists($temp_dir . '/init.php')) {
		$new_core_dir = $temp_dir;
	} else {
		foreach ((array) $wp_filesystem->dirlist($temp_dir) as $name => $details) {
			if ('d' === $details['type'] && $wp_filesystem->exists("$temp_dir/$name/init.php")) {
				$new_core_dir = "$temp_dir/$name";
				break;
			}
		}
	}
	if (!$new_core_dir) {
		log_message('The ZIP does not contain a valid gw-core (init.php missing).');
		$wp_filesystem->rmdir($temp_dir, true);
		return new \WP_Error('gwcore_invalid_zip', 'The downloaded package does not have the expected structure.');
	}

	if ($wp_filesystem->is_dir($target_dir)) {
		$wp_filesystem->move($target_dir, $backup_dir);
	}
	if (!$wp_filesystem->move($new_core_dir, $target_dir)) {
		log_message('Could not move the new gw-core into place. Restoring backup.');
		if ($wp_filesystem->is_dir($backup_dir)) {
			$wp_filesystem->move($backup_dir, $target_dir);
		}
		$wp_filesystem->rmdir($temp_dir, true);
		return new \WP_Error('gwcore_move_failed', 'Could not put the new gw-core in place.');
	}
	$wp_filesystem->rmdir($temp_dir, true);

	log_message('Update completed to version ' . $manifest['version']);
	return true;
}

/* ---------------------------------------------------------
 * Legacy theme updater migration
 * --------------------------------------------------------- */

/** Legacy files in the theme's gw/ folder that still contain the legacy code (stubs don't count). */
function legacy_files_present() {
	$present = array();
	foreach (LEGACY_FILES as $basename => $signature) {
		$path = gw_dir() . '/' . $basename;
		if (file_exists($path) && false !== strpos((string) file_get_contents($path), $signature)) {
			$present[] = $basename;
		}
	}
	return $present;
}

/** Whether any theme PHP file outside gw/ still mentions gw/<basename>. */
function theme_references($basename) {
	$skip = array('gw', 'node_modules', 'vendor', '.git');
	$dirs = new \RecursiveCallbackFilterIterator(
		new \RecursiveDirectoryIterator(theme_dir(), \FilesystemIterator::SKIP_DOTS),
		function ($file) use ($skip) {
			return !($file->isDir() && in_array($file->getFilename(), $skip, true));
		}
	);
	foreach (new \RecursiveIteratorIterator($dirs) as $file) {
		if ('php' === $file->getExtension()
			&& false !== strpos((string) file_get_contents($file->getPathname()), 'gw/' . $basename)) {
			return true;
		}
	}
	return false;
}

/**
 * Remove the legacy updater from the theme:
 * 1. Drop the `require_once('gw/<file>');` lines from functions.php.
 * 2. Delete each legacy file once no theme file references it anymore.
 * 3. Otherwise overwrite it with an inert stub, so a leftover require can never fatal.
 *
 * @return string[] Human-readable list of what was done.
 */
function migrate_legacy_theme_files() {
	global $wp_filesystem;

	$present = legacy_files_present();
	if (!$present || !current_user_can('manage_options') || !init_filesystem()) {
		return array();
	}

	$done      = array();
	$functions = theme_dir() . '/functions.php';
	$original  = $wp_filesystem->exists($functions) ? (string) $wp_filesystem->get_contents($functions) : '';

	if ('' !== $original) {
		$updated = $original;
		foreach ($present as $basename) {
			$pattern = '/^[ \t]*(?:require|include)(?:_once)?[ \t]*\(?[ \t]*(?:__DIR__[ \t]*\.[ \t]*)?([\'"])\/?(?:\.\/)?gw\/'
				. preg_quote($basename, '/')
				. '\1[ \t]*\)?[ \t]*;[ \t]*\R?/m';
			$updated = (string) preg_replace($pattern, '', $updated);
		}

		if ($updated !== $original) {
			$wp_filesystem->put_contents($functions, $updated, FS_CHMOD_FILE);
			if ((string) $wp_filesystem->get_contents($functions) !== $updated) {
				// Partial write: put the original back rather than leave a broken functions.php.
				$wp_filesystem->put_contents($functions, $original, FS_CHMOD_FILE);
				log_message('Could not rewrite functions.php; original restored.');
			} else {
				$done[] = 'Removed the legacy require lines from functions.php.';
			}
		}
	}

	$stub = "<?php\n// Deprecated: GW Core now updates itself (gw/gw-core/gw-core-updater/).\n"
		. "// Safe to delete once no theme file requires it.\n";

	foreach ($present as $basename) {
		$path = gw_dir() . '/' . $basename;

		if (!theme_references($basename)) {
			if ($wp_filesystem->delete($path)) {
				$done[] = "Deleted gw/$basename.";
			}
			continue;
		}

		if (false !== strpos((string) $wp_filesystem->get_contents($path), LEGACY_FILES[$basename])) {
			$wp_filesystem->put_contents($path, $stub, FS_CHMOD_FILE);
			$done[] = "gw/$basename is still referenced by the theme; replaced it with an inert stub.";
		}
	}

	foreach ($done as $line) {
		log_message('Migration: ' . $line);
	}
	return $done;
}

// Migrate automatically on the first admin request after updating, when the
// filesystem is writable without credentials. Otherwise the updater page offers a button.
add_action('admin_init', function () {
	if (wp_doing_ajax() || !legacy_files_present() || !current_user_can('manage_options')) {
		return;
	}
	require_once ABSPATH . 'wp-admin/includes/file.php';
	if ('direct' !== get_filesystem_method()) {
		return;
	}
	$done = migrate_legacy_theme_files();
	if ($done) {
		set_transient(NOTICE_KEY, $done, HOUR_IN_SECONDS);
	}
});

add_action('admin_notices', function () {
	$done = get_transient(NOTICE_KEY);
	if (!$done || !current_user_can('manage_options')) {
		return;
	}
	delete_transient(NOTICE_KEY);
	echo '<div class="notice notice-info is-dismissible"><p><strong>GW Core:</strong> migrated to the built-in updater.</p><ul>';
	foreach ((array) $done as $line) {
		echo '<li>' . esc_html($line) . '</li>';
	}
	echo '</ul></div>';
});

/* ---------------------------------------------------------
 * Admin page
 * --------------------------------------------------------- */

add_action('admin_menu', function () {
	// The legacy theme updater registers the same page slug; this one replaces it.
	remove_action('admin_menu', LEGACY_MENU_CALLBACK);
}, 0);

add_action('admin_menu', function () {
	// add_menu_page() + remove_menu_page() keeps the page reachable by URL but out of
	// the menu (a null parent in add_submenu_page() triggers deprecation notices).
	$hook = add_menu_page('GlitchWood Core', 'GlitchWood Core', 'manage_options', PAGE_SLUG, __NAMESPACE__ . '\\render_admin_page');
	if ($hook) {
		remove_menu_page(PAGE_SLUG);
	}
});

function render_admin_page() {
	if (!current_user_can('manage_options')) {
		return;
	}

	$url    = admin_url('admin.php?page=' . PAGE_SLUG);
	$action = isset($_POST['gwcore_action']) ? sanitize_key(wp_unslash($_POST['gwcore_action'])) : '';

	echo '<div class="wrap"><h1>GlitchWood Core</h1>';

	if (defined('DISALLOW_FILE_MODS') && DISALLOW_FILE_MODS) {
		echo '<div class="notice notice-warning"><p>File changes are disabled on this site (DISALLOW_FILE_MODS).</p></div></div>';
		return;
	}

	if ($action && check_admin_referer(NONCE_ACTION, 'gwcore_nonce')) {
		$creds = request_filesystem_credentials($url, '', false, false, array('gwcore_action', 'gwcore_nonce'));
		if (false === $creds) {
			echo '</div>';
			return; // The credentials form is being shown.
		}
		if (!init_filesystem($creds)) {
			request_filesystem_credentials($url, '', true, false, array('gwcore_action', 'gwcore_nonce'));
			echo '</div>';
			return;
		}

		if ('update' === $action) {
			$manifest = get_remote_manifest();
			$result   = $manifest ? run_update($manifest) : new \WP_Error('gwcore_manifest', 'Could not fetch the remote manifest.');
			if (is_wp_error($result)) {
				echo '<div class="notice notice-error"><p>' . esc_html($result->get_error_message()) . '</p></div>';
			} else {
				echo '<div class="notice notice-success"><p>GW Core updated to ' . esc_html($manifest['version']) . '.</p></div>';
			}
		}

		$done = migrate_legacy_theme_files();
		if ($done) {
			echo '<div class="notice notice-info"><ul><li>' . implode('</li><li>', array_map('esc_html', $done)) . '</li></ul></div>';
		}
	}

	$installed = installed_version();
	$manifest  = get_remote_manifest();
	$remote    = $manifest ? normalize_version($manifest['version']) : null;

	echo '<p><strong>Installed version:</strong> ' . esc_html($installed) . '<br>';
	echo '<strong>Latest version:</strong> ' . esc_html($remote ?: 'unavailable (see log)') . '</p>';

	if ($remote && version_compare($remote, $installed, '>')) {
		echo '<p>A new version is available.</p>';
		echo '<form method="post">';
		wp_nonce_field(NONCE_ACTION, 'gwcore_nonce');
		echo '<input type="hidden" name="gwcore_action" value="update">';
		submit_button('Update now', 'primary', 'submit', false);
		echo '</form>';
	} elseif ($remote) {
		echo '<p>You are running the latest version.</p>';
	}

	$legacy = legacy_files_present();
	if ($legacy) {
		echo '<h2>Legacy theme files</h2><p>Still present in <code>gw/</code>: <code>'
			. esc_html(implode('</code>, <code>', $legacy)) . '</code></p>';
		echo '<form method="post">';
		wp_nonce_field(NONCE_ACTION, 'gwcore_nonce');
		echo '<input type="hidden" name="gwcore_action" value="migrate">';
		submit_button('Remove legacy files', 'secondary', 'submit', false);
		echo '</form>';
	}

	echo '<p class="description">Log: <code>wp-content/uploads/gwcore-update.log</code></p></div>';
}

/* ---------------------------------------------------------
 * Logging
 * --------------------------------------------------------- */

/** Append a line to wp-content/uploads/gwcore-update.log. */
function log_message($message) {
	$uploads = wp_upload_dir(null, false);
	$base    = (empty($uploads['error']) && !empty($uploads['basedir'])) ? $uploads['basedir'] : WP_CONTENT_DIR . '/uploads';
	@file_put_contents(trailingslashit($base) . 'gwcore-update.log', '[' . gmdate('Y-m-d H:i:s') . '] ' . $message . PHP_EOL, FILE_APPEND);
}
