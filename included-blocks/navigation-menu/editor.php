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
$nav_id = !empty($attributes['navId']) ? sanitize_title_with_dashes($attributes['navId']) : 'custom_menu';
$nav_class = !empty($attributes['navClass']) ? esc_attr($attributes['navClass']) : '';
$show_mobile_menu = isset($attributes['showMobileMenu']) ? (bool) $attributes['showMobileMenu'] : true;
$cls = !empty($attributes['className']) ? esc_attr($attributes['className']) : ($show_mobile_menu ? 'd-none d-lg-flex' : 'd-flex');
if ($nav_class !== '') {
	$cls = trim($cls . ' ' . $nav_class);
}

if (!in_array($wrapper_tag, array('nav', 'ul'), true)) {
	$wrapper_tag = 'ul';
}

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

$opening_tag = '<' . $wrapper_tag . ' id="' . esc_attr($nav_id) . '" class="' . esc_attr($cls) . '">';
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
