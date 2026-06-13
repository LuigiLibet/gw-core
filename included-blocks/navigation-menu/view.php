<?php
/**
 * Block: GW Navigation Menu
 * Displays a WordPress navigation menu with customizable wrapper tag
 * 
 * Attributes:
 * - menuId: ID of the menu to display
 * - wrapperTag: HTML tag to wrap menu items (nav or ul)
 * - anchor: native WordPress "HTML anchor" -> wrapper id (falls back to 'custom_menu')
 * - className: native WordPress "Additional CSS class(es)" -> appended to the wrapper
 * - flexDirection: optional flex-direction for the wrapper (row|column)
 * - justifyContent: optional justify-content for the wrapper (flex-start|center|flex-end|space-between)
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
// Native WP "HTML anchor" -> wrapper id (keep 'custom_menu' fallback so mobile menu + theme CSS keep working)
$nav_id = !empty($attributes['anchor']) ? sanitize_title_with_dashes($attributes['anchor']) : 'custom_menu';
$show_mobile_menu = isset($attributes['showMobileMenu']) ? (bool) $attributes['showMobileMenu'] : true;
// Functional base classes (self-contained, styled by style.css — no Bootstrap dependency)
// + native "Additional CSS class(es)" appended on top.
$base_cls = $show_mobile_menu ? 'gw-nav gw-nav--collapsible' : 'gw-nav';
$extra_cls = !empty($attributes['className']) ? esc_attr($attributes['className']) : '';
$cls = trim($base_cls . ' ' . $extra_cls);

// Validate wrapper tag - only allow 'nav' or 'ul'
if (!in_array($wrapper_tag, array('nav', 'ul'), true)) {
	$wrapper_tag = 'ul';
}

// Optional flex layout (pill controls). Applied inline so it composes with the base classes
// without forcing display (which would override the responsive collapse on mobile).
$styles = array();
if (!empty($attributes['flexDirection']) && in_array($attributes['flexDirection'], array('row', 'column'), true)) {
	$styles[] = 'flex-direction:' . $attributes['flexDirection'];
}
if (!empty($attributes['justifyContent']) && in_array($attributes['justifyContent'], array('flex-start', 'center', 'flex-end', 'space-between'), true)) {
	$styles[] = 'justify-content:' . $attributes['justifyContent'];
}
$style_attr = !empty($styles) ? ' style="' . esc_attr(implode(';', $styles)) . '"' : '';

// Detect if we're in editor context (robust check shared across the framework).
$is_editor = function_exists('gw_in_editor') ? gw_in_editor() : (defined('REST_REQUEST') && REST_REQUEST);

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
	if ($is_editor) {
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
$opening_tag = '<' . $wrapper_tag . ' id="' . esc_attr($nav_id) . '" class="' . esc_attr($cls) . '"' . $style_attr . '>';
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
	$opening_tag = '<' . $wrapper_tag . ' id="' . esc_attr($nav_id) . '_mobile">';
	$closing_tag = '</' . $wrapper_tag . '>';
	$items_wrap = $opening_tag . '%3$s' . $closing_tag;

	?>
	<span id="menu_trigger" class="gw-nav__trigger"><i></i></span>
	
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

