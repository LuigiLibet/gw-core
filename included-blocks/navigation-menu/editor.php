<?php
/**
 * Editor preview for GW Navigation Menu block.
 * Renders menu links as <span> elements to prevent navigation in the block editor.
 */

if (!defined('ABSPATH')) {
	exit;
}

$menu_id = isset($attributes['menuId']) && is_numeric($attributes['menuId'])
	? (int) $attributes['menuId']
	: 0;
$wrapper_tag = !empty($attributes['wrapperTag']) ? sanitize_key($attributes['wrapperTag']) : 'ul';
$nav_id = !empty($attributes['anchor']) ? sanitize_title_with_dashes($attributes['anchor']) : 'custom_menu';
$show_mobile_menu = isset($attributes['showMobileMenu']) ? (bool) $attributes['showMobileMenu'] : true;
// Editor preview always shows the inline menu (no responsive collapse, no trigger here),
// so the desktop layout stays visible regardless of the editor canvas width.
$base_cls = 'gw-nav';
$extra_cls = !empty($attributes['className']) ? esc_attr($attributes['className']) : '';
$cls = trim($base_cls . ' ' . $extra_cls);

if (!in_array($wrapper_tag, array('nav', 'ul'), true)) {
	$wrapper_tag = 'ul';
}

$styles = array();
if (!empty($attributes['flexDirection']) && in_array($attributes['flexDirection'], array('row', 'column'), true)) {
	$styles[] = 'flex-direction:' . $attributes['flexDirection'];
}
if (!empty($attributes['justifyContent']) && in_array($attributes['justifyContent'], array('flex-start', 'center', 'flex-end', 'space-between'), true)) {
	$styles[] = 'justify-content:' . $attributes['justifyContent'];
}
$style_attr = !empty($styles) ? ' style="' . esc_attr(implode(';', $styles)) . '"' : '';

if (!isset($attributes['menuId']) || $menu_id <= 0) {
	echo '<div class="components-placeholder">';
	echo '<div class="components-placeholder__label">' . esc_html__('Navigation Menu', 'gwblueprint') . '</div>';
	echo '<div class="components-placeholder__instructions">' . esc_html__('Please select a menu from the block settings.', 'gwblueprint') . '</div>';
	echo '</div>';
	return;
}

$menu_obj = wp_get_nav_menu_object($menu_id);
if (!$menu_obj || is_wp_error($menu_obj)) {
	echo '<div class="components-placeholder">';
	echo '<div class="components-placeholder__label">' . esc_html__('Navigation Menu', 'gwblueprint') . '</div>';
	echo '<div class="components-placeholder__instructions" style="color: #d63638;">';
	echo esc_html__('The selected menu no longer exists. Please select a different menu.', 'gwblueprint');
	echo '</div>';
	echo '</div>';
	return;
}

// Walker that replaces <a> with <span> so links are not clickable in the editor
if (!class_exists('GW_Nav_Menu_Editor_Walker')) {
	class GW_Nav_Menu_Editor_Walker extends Walker_Nav_Menu {
		public function start_el(&$output, $item, $depth = 0, $args = null, $id = 0) {
			$item_output = '';
			parent::start_el($item_output, $item, $depth, $args, $id);
			$item_output = preg_replace('/<a\b[^>]*>/', '<span class="menu-link">', $item_output);
			$item_output = str_replace('</a>', '</span>', $item_output);
			$output .= $item_output;
		}
	}
}

$opening_tag = '<' . $wrapper_tag . ' id="' . esc_attr($nav_id) . '" class="' . esc_attr($cls) . '"' . $style_attr . '>';
$closing_tag = '</' . $wrapper_tag . '>';
$items_wrap = $opening_tag . '%3$s' . $closing_tag;

wp_nav_menu(array(
	'menu'         => $menu_id,
	'container'    => false,
	'items_wrap'   => $items_wrap,
	'item_spacing' => 'discard',
	'fallback_cb'  => false,
	'walker'       => new GW_Nav_Menu_Editor_Walker(),
));
