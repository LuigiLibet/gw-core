<?php
/**
 * Block: GW Navigation Menu
 * Displays a WordPress navigation menu with customizable wrapper tag
 * 
 * Attributes:
 * - menuId: ID of the menu to display
 * - wrapperTag: HTML tag to wrap menu items (nav or ul)
 * - navId: ID attribute for the wrapper element
 * - className: Additional CSS classes
 * - showMobileMenu: Whether to show the mobile menu (boolean)
 */

// Security check
if (!defined('ABSPATH')) {
	exit;
}

// Get and sanitize attributes
$menu_id = isset($attributes['menuId']) && is_numeric($attributes['menuId']) 
	? (int) $attributes['menuId'] 
	: 0;
$wrapper_tag = !empty($attributes['wrapperTag']) ? sanitize_key($attributes['wrapperTag']) : 'ul';
$nav_id = !empty($attributes['navId']) ? sanitize_title_with_dashes($attributes['navId']) : 'custom_menu';
$show_mobile_menu = isset($attributes['showMobileMenu']) ? (bool) $attributes['showMobileMenu'] : true;
$cls = !empty($attributes['className']) ? esc_attr($attributes['className']) : ($show_mobile_menu ? 'd-none d-lg-flex' : 'd-flex');

// Validate wrapper tag - only allow 'nav' or 'ul'
if (!in_array($wrapper_tag, array('nav', 'ul'), true)) {
	$wrapper_tag = 'ul';
}

// Detect if we're in editor context
$is_editor = defined('REST_REQUEST') && REST_REQUEST;

// Error handling: No menu selected
if (!isset($attributes['menuId']) || $menu_id <= 0) {
	// Editor preview: show placeholder
	if ($is_editor) {
		echo '<div class="components-placeholder">';
		echo '<div class="components-placeholder__label">' . esc_html__('Navigation Menu', 'gwblueprint') . '</div>';
		echo '<div class="components-placeholder__instructions">' . esc_html__('Please select a menu from the block settings.', 'gwblueprint') . '</div>';
		echo '</div>';
	}
	// Frontend: render nothing
	return;
}

// Validate menu exists
$menu_obj = wp_get_nav_menu_object($menu_id);

// Error handling: Menu was deleted or doesn't exist
if (!$menu_obj || is_wp_error($menu_obj)) {
	// Editor preview: show error message
	if (defined('REST_REQUEST') && REST_REQUEST) {
		echo '<div class="components-placeholder">';
		echo '<div class="components-placeholder__label">' . esc_html__('Navigation Menu', 'gwblueprint') . '</div>';
		echo '<div class="components-placeholder__instructions" style="color: #d63638;">';
		echo esc_html__('The selected menu no longer exists. Please select a different menu.', 'gwblueprint');
		echo '</div>';
		echo '</div>';
	}
	// Frontend: render nothing (graceful degradation)
	return;
}

// Build items_wrap based on selected wrapper tag
$opening_tag = '<' . $wrapper_tag . ' id="' . $nav_id . '" class="' . $cls . '">';
$closing_tag = '</' . $wrapper_tag . '>';
$items_wrap = $opening_tag . '%3$s' . $closing_tag;

// Render menu
wp_nav_menu(array(
	'menu'         => $menu_id,
	'container'    => false,
	'items_wrap'   => $items_wrap,
	'item_spacing' => 'discard',
	'fallback_cb'  => false, // Don't show fallback if menu is empty
));

// Render mobile menu only if enabled
if ($show_mobile_menu) {
	$opening_tag = '<' . $wrapper_tag . ' id="' . $nav_id . '_mobile">';
	$closing_tag = '</' . $wrapper_tag . '>';
	$items_wrap = $opening_tag . '%3$s' . $closing_tag;

	?>
	<span id="menu_trigger" class="d-flex d-lg-none"><i></i></span>
	
	<div id="mobile_menu_container">
		<?php
		wp_nav_menu(array(
			'menu'			=> $menu_id,
			'container'		=> false,
			'items_wrap'	=> $items_wrap,
			'item_spacing'	=> 'discard',
			'fallback_cb'	=> false, // Don't show fallback if menu is empty
		));
		?>
	</div>
	<?php
}

