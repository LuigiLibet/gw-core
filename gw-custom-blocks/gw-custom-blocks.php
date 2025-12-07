<?php
/***
 * 
 * @package GW Custom Blocks
 * @version 1.1.0
 * @author Luigi Libet
 * @link https://github.com/LuigiLibet/gw-custom-blocks
 * @license GPL-2.0+
 * @copyright 2025 Luigi Libet
 */

// Prevent direct access
if (!defined('ABSPATH')) {
	exit;
}

/**
 * GW Custom Blocks
 * Simple helper to register dynamic (PHP-rendered) blocks with optional auto-discovery.
 *
 * Usage example:
 * gw_register_block('my-badge', array(
 *   'name'    => 'My Badge',
 *   'category'=> 'custom',
 *   'supports'=> array('color' => array('text' => true, 'background' => true)),
 *   'render'  => 'badge/view.php', // relative to blocks/ by default
 *   'editor_styles' => 'assets/css/editor.css', // optional
 *   'dir'     => '', // override base dir; defaults to blocks
 *   'fields'  => array(
 *       'label' => array('type' => 'string', 'control' => 'text', 'label' => 'Label', 'default' => ''),
 *       'size'  => array('type' => 'number', 'control' => 'range', 'label' => 'Size', 'min' => 8, 'max' => 64, 'step' => 1, 'default' => 16),
 *       'color' => array('type' => 'string', 'control' => 'color', 'label' => 'Color'),
 *   ),
 * ));
 *
 * Inline render (very simple template):
 *   'render' => '<span class="badge">{{label}}</span>'
 * Placeholders supported: {{content}} and {{attributeName}}
 */

// Keep a registry so we can provide a single editor script with config for all blocks.
if (!isset($GLOBALS['gw_custom_blocks_registry'])) {
	$GLOBALS['gw_custom_blocks_registry'] = array();
}

// Keep a registry for icon libraries
if (!isset($GLOBALS['gw_icon_libraries'])) {
	$GLOBALS['gw_icon_libraries'] = array();
}

/**
 * Register a Custom Block
 *
 * @param string $slug Block slug (without namespace). Final name will be "$namespace/$slug".
 * @param array  $args Settings:
 *  - name (string): UI title. Default: humanized slug
 *  - namespace (string): Default 'gw'
 *  - category (string): Default 'custom'
 *  - supports (array): Block supports
 *  - render (string): PHP file path (absolute or relative) OR inline HTML string
 *  - dir (string): Base dir for relative render paths. Default theme 'blocks'
 *  - icon (string|array): Block icon
 *  - keywords (array): Inserter keywords
 *  - editor_styles (string): Optional CSS file path to load in editor
 *  - fields (array): Attribute/controls spec. Each key is attribute name.
 *      Support: control types text, textarea, color, range, number, toggle, select, gallery, image, repeater, icon_picker
 */
function gw_register_block($slug, $args = array()) {
	$defaults = array(
		'name'          => null,
		'namespace'     => 'gw',
		'category'      => 'custom',
		'supports'      => array(),
		'render'        => null,
		'dir'           => '',
		'icon'          => 'block-default',
		'keywords'      => array(),
		'editor_styles' => '',
		'fields'        => array(),
		'ui'            => array(),
	);
	$args = array_merge($defaults, $args);

	$title = $args['name'] ?: ucwords(trim(str_replace(array('-', '_'), ' ', $slug)));
	$namespace = $args['namespace'] ?: 'gw';
	$block_name = $namespace . '/' . $slug;

	// Base dir for PHP templates (default: theme 'blocks')
	$base_dir = $args['dir'];
	if (!$base_dir) {
		$base_dir = trailingslashit(get_theme_file_path('blocks'));
	} else {
		// If relative, make it theme-relative
		if (0 !== strpos($base_dir, ABSPATH)) {
			$base_dir = trailingslashit(get_theme_file_path($base_dir));
		} else {
			$base_dir = trailingslashit($base_dir);
		}
	}

	// Normalize fields -> attributes
	$attributes = array();
	foreach ($args['fields'] as $attr_key => $field) {
		$type = isset($field['type']) ? $field['type'] : 'string';
		$attr = array('type' => $type);
		if (array_key_exists('default', $field)) {
			$attr['default'] = $field['default'];
		}
		// For select, constrain enum if options provided
		if (isset($field['control']) && $field['control'] === 'select' && !empty($field['options']) && is_array($field['options'])) {
			$attr['enum'] = array_values(array_map(function($opt){
				return is_array($opt) && isset($opt['value']) ? $opt['value'] : $opt;
			}, $field['options']));
		}
		$attributes[$attr_key] = $attr;
	}

	// Add WordPress core attributes for supported features
	// Anchor support
	if (!empty($args['supports']['anchor'])) {
		if (!isset($attributes['anchor'])) {
			$attributes['anchor'] = array('type' => 'string');
		}
	}
	// Custom className support
	if (!empty($args['supports']['customClassName'])) {
		if (!isset($attributes['className'])) {
			$attributes['className'] = array('type' => 'string');
		}
	}

	// Store registry entry for editor script to read
	$GLOBALS['gw_custom_blocks_registry'][$block_name] = array(
		'name'      => $block_name,
		'title'     => $title,
		'icon'      => $args['icon'],
		'category'  => $args['category'],
		'supports'  => $args['supports'],
		'fields'    => $args['fields'],
		'attributes'=> $attributes,
		'ui'        => $args['ui'],
	);

	// Prepare editor style handle if provided
	$editor_style_handle = '';
	if (!empty($args['editor_styles'])) {
		$style_src = $args['editor_styles'];
		if (0 !== strpos($style_src, 'http') && 0 !== strpos($style_src, '/')) {
			// theme relative path
			$style_src = trailingslashit(get_template_directory_uri()) . ltrim($style_src, '/');
		} elseif (0 === strpos($style_src, '/')) {
			// Convert absolute path under theme to URI if it points inside theme
			$theme_dir = trailingslashit(get_template_directory());
			if (0 === strpos($style_src, $theme_dir)) {
				$style_src = str_replace($theme_dir, trailingslashit(get_template_directory_uri()), $style_src);
			}
		}
		$editor_style_handle = 'gw-custom-blocks-editor-style-' . sanitize_key($slug);
		wp_register_style($editor_style_handle, $style_src, array(), wp_get_theme()->get('Version'));
	}

	// Build render callback
	$render_spec = $args['render'];
	$render_cb = function($attrs = array(), $content = '', $block = null) use ($render_spec, $base_dir) {
		// Helper to include PHP template safely and capture output
		$include_php = function($path) use ($attrs, $content, $block) {
			if (!file_exists($path)) {
				return '';
			}
			ob_start();
			// Make attrs accessible inside template
			$attributes = $attrs;
			$post_id = get_the_ID();
			include $path;
			return ob_get_clean();
		};

		if (is_string($render_spec)) {
			// Inline HTML template if looks like markup
			$is_inline_template = false;
			$trimmed = trim($render_spec);
			if ($trimmed !== '' && $trimmed[0] === '<') {
				$is_inline_template = true;
			}

			if ($is_inline_template) {
				$replace = $trimmed;
				// Replace {{content}} or $content placeholder
				$replace = str_replace(array('{{content}}', '$content'), $content, $replace);
				// Replace {{attribute}}
				foreach ($attrs as $k => $v) {
					$replace = str_replace('{{' . $k . '}}', is_scalar($v) ? (string) $v : '', $replace);
					$replace = str_replace('$' . $k, is_scalar($v) ? (string) $v : '', $replace);
				}
				return $replace;
			}

			// Otherwise treat as path: absolute or relative to base_dir
			$template_path = $render_spec;
			if (0 !== strpos($template_path, ABSPATH)) {
				$template_path = $base_dir . ltrim($template_path, '/');
			}
			return $include_php($template_path);
		}

		// No render spec -> nothing
		return '';
	};

	// Ensure our editor script is registered and localized
	gw_custom_blocks_bootstrap_editor_script();

	$register_args = array(
		'api_version'     => 2,
		'title'           => $title,
		'category'        => $args['category'],
		'icon'            => $args['icon'],
		'keywords'        => $args['keywords'],
		'supports'        => $args['supports'],
		'attributes'      => $attributes,
		'editor_script'   => 'gw-custom-blocks-editor',
		'render_callback' => $render_cb,
	);
	if ($editor_style_handle) {
		$register_args['editor_style'] = $editor_style_handle;
	}

	register_block_type($block_name, $register_args);
}

/**
 * Register blocks by auto-discovering PHP templates in a directory.
 *
 * - Auto-discovers block directories containing a view.php under $dir (default: theme 'blocks')
 * - Example path: blocks/my-block/view.php
 * - Each directory name becomes the slug; title is humanized from slug
 */
function gw_register_blocks_autodiscover($dir = '') {
	$base_dir = $dir ? $dir : get_theme_file_path('blocks');
	if (!is_dir($base_dir)) {
		return;
	}
	$files = glob(trailingslashit($base_dir) . '*/view.php');
	if (!$files) {
		return;
	}
	foreach ($files as $file) {
		$slug = basename(dirname($file));
		gw_register_block($slug, array(
			'render' => $file,
		));
	}
}

/**
 * Ensure our generic editor script is registered and localized with registry data.
 */
function gw_custom_blocks_bootstrap_editor_script() {
	static $bootstrapped = false;
	if ($bootstrapped) {
		// Update localization if new blocks were added
		gw_custom_blocks_update_localization();
		return;
	}
	$bootstrapped = true;

	$theme_uri = trailingslashit(get_template_directory_uri());
	$script_uri = $theme_uri . 'editor/gw-custom-blocks/gw-custom-blocks.js';
	$deps = array('wp-blocks', 'wp-element', 'wp-i18n', 'wp-components', 'wp-block-editor', 'wp-server-side-render', 'wp-data', 'wp-core-data', 'wp-api-fetch');
	wp_register_script('gw-custom-blocks-editor', $script_uri, $deps, wp_get_theme()->get('Version'), true);

	// Register editor styles for gallery control (will be enqueued via hook)
	$style_uri = $theme_uri . 'editor/gw-custom-blocks/gw-custom-blocks.css';
	wp_register_style('gw-custom-blocks-editor-styles', $style_uri, array(), wp_get_theme()->get('Version'));

	// Localize initial registry
	gw_custom_blocks_update_localization();
}

/**
 * Push registry into the editor script localization so JS can register the UI.
 */
function gw_custom_blocks_update_localization() {
	$blocks = array_values($GLOBALS['gw_custom_blocks_registry']);
	
	// Prepare icon libraries for localization
	$icon_libraries = array();
	$all_libraries = gw_get_all_icon_libraries();
	foreach ($all_libraries as $lib_id => $lib) {
		$icon_libraries[] = array(
			'id' => $lib['id'],
			'name' => $lib['name'],
			'template' => $lib['template'],
			'icons' => $lib['icons'],
		);
	}
	
	wp_localize_script('gw-custom-blocks-editor', 'GW_CUSTOM_BLOCKS', array(
		'blocks' => $blocks,
		'iconLibraries' => $icon_libraries,
	));
}

/**
 * Enqueue editor styles for custom blocks.
 */
function gw_custom_blocks_enqueue_editor_styles() {
	wp_enqueue_style('gw-custom-blocks-editor-styles');
	
	// Enqueue icon library CSS files in the editor
	$all_libraries = gw_get_all_icon_libraries();
	foreach ($all_libraries as $lib_id => $lib) {
		if (!empty($lib['css'])) {
			$css_url = $lib['css'];
			// Convert relative paths to absolute URLs
			if (0 !== strpos($css_url, 'http') && 0 !== strpos($css_url, '//')) {
				if (0 === strpos($css_url, '/')) {
					// Absolute path under theme
					$css_url = trailingslashit(get_template_directory_uri()) . ltrim($css_url, '/');
				} else {
					// Relative to theme
					$css_url = trailingslashit(get_template_directory_uri()) . $css_url;
				}
			}
			$handle = 'gw-icon-library-' . sanitize_key($lib_id);
			wp_enqueue_style($handle, $css_url, array(), wp_get_theme()->get('Version'));
		}
	}
}
add_action('enqueue_block_editor_assets', 'gw_custom_blocks_enqueue_editor_styles');

/**
 * Get gallery image URLs from comma-separated image IDs string.
 *
 * @param string $ids_string Comma-separated string of image IDs (e.g., "123,456,789")
 * @param string $size Image size to retrieve. Default 'full'. Can be 'thumbnail', 'medium', 'large', 'full', or any registered size.
 * @return array Array of image URLs. Returns empty array if no valid IDs found.
 *
 * @example
 * $gallery_ids = "123,456,789";
 * $urls = gw_get_gallery_urls($gallery_ids);
 * // Returns: ['https://example.com/wp-content/uploads/image1.jpg', ...]
 *
 * @example
 * $urls = gw_get_gallery_urls($gallery_ids, 'thumbnail');
 * // Returns thumbnail URLs
 */
function gw_get_gallery_urls($ids_string, $size = 'full') {
	if (empty($ids_string)) {
		return array();
	}

	// Parse comma-separated IDs
	$ids = array_map('trim', explode(',', $ids_string));
	$ids = array_filter($ids, function($id) {
		return is_numeric($id) && $id > 0;
	});

	if (empty($ids)) {
		return array();
	}

	$urls = array();
	foreach ($ids as $id) {
		$image_url = wp_get_attachment_image_url((int) $id, $size);
		if ($image_url) {
			$urls[] = $image_url;
		}
	}

	return $urls;
}

/**
 * Get repeater items from serialized string (JSON or PHP serialized format).
 *
 * @param string $serialized_string Serialized string containing repeater items (JSON or PHP serialized format)
 * @return array Array of items. Each item is an associative array with field values. Returns empty array if invalid or empty.
 *
 * @example
 * $items_data = $attributes['items'];
 * $items = gw_get_repeater_items($items_data);
 * foreach ($items as $item) {
 *     echo esc_html($item['title']);
 *     echo esc_html($item['description']);
 * }
 *
 * @example
 * // With nested gallery field
 * $items = gw_get_repeater_items($attributes['items']);
 * foreach ($items as $item) {
 *     $image_urls = gw_get_gallery_urls($item['image'], 'large');
 *     foreach ($image_urls as $url) {
 *         echo '<img src="' . esc_url($url) . '" />';
 *     }
 * }
 */
function gw_get_repeater_items($serialized_string) {
	if (empty($serialized_string) || !is_string($serialized_string)) {
		return array();
	}

	$trimmed = trim($serialized_string);
	if ($trimmed === '') {
		return array();
	}

	// Try JSON first (preferred format, easier to handle)
	if (($trimmed[0] === '[' || $trimmed[0] === '{')) {
		$decoded = json_decode($trimmed, true);
		if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
			return $decoded;
		}
	}

	// Fallback to PHP unserialize
	if (function_exists('unserialize')) {
		// Check if it looks like PHP serialized data
		if (strpos($trimmed, 'a:') === 0 || strpos($trimmed, 'O:') === 0) {
			$unserialized = @unserialize($trimmed);
			if ($unserialized !== false && is_array($unserialized)) {
				return $unserialized;
			}
		}
	}

	// If all else fails, return empty array
	return array();
}

/**
 * Get image URL from ID or URL value.
 *
 * Helper function to get image URL regardless of whether the field stores ID or URL.
 * If value is numeric (ID), fetches the image URL from WordPress attachment.
 * If value is already a URL, returns it directly.
 *
 * @param string|int $value Image ID (numeric) or image URL (string)
 * @param string $size Image size to retrieve. Default 'full'. Can be 'thumbnail', 'medium', 'large', 'full', or any registered size.
 * @return string Image URL. Returns empty string if no valid value found.
 *
 * @example
 * // When field saves as ID
 * $image_id = $attributes['image']; // e.g., "123"
 * $image_url = gw_get_image_url($image_id, 'large');
 * // Returns: 'https://example.com/wp-content/uploads/image.jpg'
 *
 * @example
 * // When field saves as URL
 * $image_url = $attributes['image']; // e.g., "https://example.com/image.jpg"
 * $image_url = gw_get_image_url($image_url);
 * // Returns: 'https://example.com/image.jpg'
 *
 * @example
 * // In template
 * $image_value = isset($attributes['image']) ? $attributes['image'] : '';
 * if (!empty($image_value)) {
 *     $url = gw_get_image_url($image_value, 'large');
 *     echo '<img src="' . esc_url($url) . '" alt="" />';
 * }
 */
function gw_get_image_url($value, $size = 'full') {
	if (empty($value)) {
		return '';
	}

	// Check if value is numeric (ID)
	if (is_numeric($value)) {
		$image_id = (int) $value;
		if ($image_id > 0) {
			$image_url = wp_get_attachment_image_url($image_id, $size);
			if ($image_url) {
				return $image_url;
			}
		}
	}

	// If not numeric or wp_get_attachment_image_url failed, treat as URL
	// Validate it's a valid URL format
	if (is_string($value) && (filter_var($value, FILTER_VALIDATE_URL) !== false || strpos($value, '/') === 0)) {
		return $value;
	}

	// If all else fails, return empty string
	return '';
}



/**
 * Register REST API route for searching posts.
 * Used by the Post Select control.
 */
function gw_custom_blocks_register_rest_routes() {
	register_rest_route('gw/v1', '/posts', array(
		'methods' => 'GET',
		'callback' => 'gw_custom_blocks_rest_search_posts',
		'permission_callback' => function() {
			return current_user_can('edit_posts');
		},
		'args' => array(
			'type' => array(
				'default' => 'page',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'search' => array(
				'default' => '',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'per_page' => array(
				'default' => 10,
				'sanitize_callback' => 'absint',
			),
		),
	));
}
add_action('rest_api_init', 'gw_custom_blocks_register_rest_routes');

/**
 * REST API callback for searching posts.
 */
function gw_custom_blocks_rest_search_posts($request) {
	$post_type = $request->get_param('type');
	$search = $request->get_param('search');
	$per_page = $request->get_param('per_page');

	// Validate post type
	if (!post_type_exists($post_type)) {
		return new WP_Error('invalid_post_type', 'Invalid post type', array('status' => 400));
	}

	$args = array(
		'post_type' => $post_type,
		'posts_per_page' => $per_page,
		'post_status' => 'publish',
		'orderby' => 'title',
		'order' => 'ASC',
	);

	if (!empty($search)) {
		$args['s'] = $search;
	}

	$query = new WP_Query($args);
	$posts = array();

	if ($query->have_posts()) {
		foreach ($query->posts as $post) {
			$posts[] = array(
				'value' => $post->ID,
				'label' => $post->post_title . ' (ID: ' . $post->ID . ')',
			);
		}
	}

	// If we have a specific value selected but it's not in the search results,
	// we might want to fetch it specifically? 
	// For now, the UI handles fetching the selected value separately if needed, 
	// but usually we just store the ID.

	return rest_ensure_response($posts);
}

/**
 * Register an icon library.
 *
 * @param string $id Unique identifier for the library (e.g., 'uicons-rr')
 * @param array  $args Library configuration:
 *   - name (string): Display name of the library
 *   - css (string): CSS file URL or path (for reference, CSS should be enqueued manually in functions.php)
 *   - template (string): HTML template with {{icon}} placeholder (e.g., '<i class="{{icon}}"></i>')
 *   - icons (array): Array of icons. Can be:
 *     * Array with 'value' => 'label' pairs: array('fi-rr-home' => 'Home', 'fi-rr-user' => 'User')
 *     * Array of arrays: array(array('value' => 'fi-rr-home', 'label' => 'Home'))
 *   - class_prefix (string): Optional. Class prefix for parsing CSS (e.g., 'fi-rr-')
 *
 * @example
 * gw_register_icon_library('uicons-rr', array(
 *   'name' => 'Flaticon UIcons Regular Rounded',
 *   'css' => 'https://cdn-uicons.flaticon.com/3.0.0/uicons-regular-rounded/css/uicons-regular-rounded.css',
 *   'template' => '<i class="{{icon}}"></i>',
 *   'icons' => array('fi-rr-home' => 'Home', 'fi-rr-user' => 'User'),
 * ));
 */
function gw_register_icon_library($id, $args = array()) {
	$defaults = array(
		'name' => '',
		'css' => '',
		'template' => '<i class="{{icon}}"></i>',
		'icons' => array(),
		'class_prefix' => '',
	);
	$args = array_merge($defaults, $args);

	// Normalize icons array
	$normalized_icons = array();
	if (!empty($args['icons']) && is_array($args['icons'])) {
		foreach ($args['icons'] as $key => $value) {
			if (is_array($value) && isset($value['value'])) {
				// Already in normalized format
				$normalized_icons[] = array(
					'value' => $value['value'],
					'label' => isset($value['label']) ? $value['label'] : $value['value'],
				);
			} else {
				// Key-value pair format
				$normalized_icons[] = array(
					'value' => is_string($key) ? $key : $value,
					'label' => is_string($value) ? $value : (is_string($key) ? $key : ''),
				);
			}
		}
	}

	$GLOBALS['gw_icon_libraries'][$id] = array(
		'id' => $id,
		'name' => $args['name'],
		'css' => $args['css'],
		'template' => $args['template'],
		'icons' => $normalized_icons,
		'class_prefix' => $args['class_prefix'],
	);
}

/**
 * Get icon library configuration.
 *
 * @param string $id Library identifier
 * @return array|null Library configuration or null if not found
 */
function gw_get_icon_library($id) {
	if (isset($GLOBALS['gw_icon_libraries'][$id])) {
		return $GLOBALS['gw_icon_libraries'][$id];
	}
	return null;
}

/**
 * Get all registered icon libraries.
 *
 * @return array Array of all registered icon libraries
 */
function gw_get_all_icon_libraries() {
	return $GLOBALS['gw_icon_libraries'];
}

/**
 * Parse icon library CSS file to extract icon classes.
 *
 * Downloads or reads CSS file and extracts all classes matching the given prefix.
 *
 * @param string $css_url CSS file URL or local file path
 * @param string $class_prefix Class prefix to match (e.g., 'fi-rr-')
 * @return array Array of icons in format array('fi-rr-home' => 'Home', 'fi-rr-user' => 'User')
 *
 * @example
 * $icons = gw_parse_icon_library_css(
 *   'https://cdn-uicons.flaticon.com/3.0.0/uicons-regular-rounded/css/uicons-regular-rounded.css',
 *   'fi-rr-'
 * );
 */
function gw_parse_icon_library_css($css_url, $class_prefix) {
	$icons = array();

	// Get CSS content
	$css_content = '';
	if (filter_var($css_url, FILTER_VALIDATE_URL)) {
		// Remote URL - use wp_remote_get
		$response = wp_remote_get($css_url, array(
			'timeout' => 10,
			'sslverify' => true,
		));
		if (!is_wp_error($response) && isset($response['body'])) {
			$css_content = $response['body'];
		}
	} else {
		// Local file path
		if (file_exists($css_url)) {
			$css_content = file_get_contents($css_url);
		} elseif (file_exists(get_template_directory() . '/' . ltrim($css_url, '/'))) {
			$css_content = file_get_contents(get_template_directory() . '/' . ltrim($css_url, '/'));
		}
	}

	if (empty($css_content)) {
		return $icons;
	}

	// Escape prefix for regex
	$escaped_prefix = preg_quote($class_prefix, '/');

	// Pattern to match: .fi-rr-icon-name:before { content: "..."; }
	// Also matches: .fi-rr-icon-name:before { content: "\e001"; }
	$pattern = '/\.(' . $escaped_prefix . '[a-zA-Z0-9\-]+):before\s*\{[^}]*content:\s*["\']([^"\']+)["\']/';

	preg_match_all($pattern, $css_content, $matches, PREG_SET_ORDER);

	foreach ($matches as $match) {
		$class_name = $match[1];
		// Generate human-readable label from class name
		// Remove prefix and convert to title case
		$label = str_replace($class_prefix, '', $class_name);
		$label = str_replace(array('-', '_'), ' ', $label);
		$label = ucwords($label);
		$icons[$class_name] = $label;
	}

	return $icons;
}

/**
 * Render icon from stored value.
 *
 * Parses stored value (format: "library:icon" or just "icon" if single library),
 * gets library configuration, and renders the icon HTML.
 *
 * @param string $value Stored value (format: "library:icon" or just "icon" if single library)
 * @param array  $args Optional arguments:
 *   - template (string): Custom template override
 *   - library (string): Force specific library ID
 *   - class (string): Additional CSS classes to add
 * @return string HTML output. Returns empty string if invalid.
 *
 * @example
 * // With library prefix
 * echo gw_icon('uicons-rr:fi-rr-home');
 *
 * @example
 * // Without prefix (single library)
 * echo gw_icon('fi-rr-home');
 *
 * @example
 * // With custom template
 * echo gw_icon('fi-rr-home', array('template' => '<span class="{{icon}}"></span>'));
 *
 * @example
 * // With additional classes
 * echo gw_icon('fi-rr-home', array('class' => 'my-icon'));
 */
function gw_icon($value, $args = array()) {
	if (empty($value)) {
		return '';
	}

	$defaults = array(
		'template' => '',
		'library' => '',
		'class' => '',
	);
	$args = array_merge($defaults, $args);

	// Parse value: "library:icon" or just "icon"
	$parts = explode(':', $value, 2);
	$library_id = '';
	$icon_class = '';

	if (count($parts) === 2) {
		// Format: "library:icon"
		$library_id = $parts[0];
		$icon_class = $parts[1];
	} else {
		// No library prefix - try to detect from available libraries
		$icon_class = $parts[0];
		if (!empty($args['library'])) {
			$library_id = $args['library'];
		} else {
			// If only one library registered, use it
			$all_libraries = gw_get_all_icon_libraries();
			if (count($all_libraries) === 1) {
				$library_id = key($all_libraries);
			}
		}
	}

	// Get library configuration
	$library = null;
	if (!empty($library_id)) {
		$library = gw_get_icon_library($library_id);
	}

	// If no library found, try to find one that contains this icon class
	if (!$library) {
		$all_libraries = gw_get_all_icon_libraries();
		foreach ($all_libraries as $lib) {
			foreach ($lib['icons'] as $icon) {
				if ($icon['value'] === $icon_class) {
					$library = $lib;
					$library_id = $lib['id'];
					break 2;
				}
			}
		}
	}

	if (!$library) {
		return '';
	}

	// Use custom template or library template
	$template = !empty($args['template']) ? $args['template'] : $library['template'];

	// Replace placeholder
	$html = str_replace('{{icon}}', esc_attr($icon_class), $template);

	// Add additional classes if specified
	if (!empty($args['class'])) {
		// Try to add class to existing class attribute
		if (preg_match('/class=["\']([^"\']*)["\']/', $html, $matches)) {
			$existing_classes = $matches[1];
			$new_classes = trim($existing_classes . ' ' . esc_attr($args['class']));
			$html = preg_replace('/class=["\'][^"\']*["\']/', 'class="' . esc_attr($new_classes) . '"', $html);
		} else {
			// No class attribute, add it to the first tag
			$html = preg_replace('/<(\w+)([^>]*)>/', '<$1 class="' . esc_attr($args['class']) . '"$2>', $html, 1);
		}
	}

	return $html;
}
