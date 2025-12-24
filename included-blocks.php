<?php
/**
 * GW Core - Included Blocks
 * 
 * Registration file for blocks included with GW Core.
 * These blocks are automatically loaded by gw-core/init.php
 * 
 * @package GW Core
 * @version 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
	exit;
}

// Blocks registration
add_action('init', function(){
	if (!function_exists('register_block_type')) {
		return;
	}
	
	// Navigation Menu block
	// Get created menus - build dropdown options with fallback
	$__gw_created_menus_options = array(
		array('label' => __('-- Select a menu --', 'gwblueprint'), 'value' => 0),
	);
	
	$created_menus = wp_get_nav_menus();
	if (!empty($created_menus) && !is_wp_error($created_menus)) {
		foreach ($created_menus as $menu) {
			$__gw_created_menus_options[] = array(
				'label' => esc_html($menu->name),
				'value' => (int) $menu->term_id,
			);
		}
	}

	gw_register_block('navigation-menu', array(
		'name'     => __('Navigation Menu', 'gwblueprint'),
		'category' => 'custom',
		'icon'     => 'menu',
		'supports' => array(
			'anchor' => true,
			'customClassName' => true,
		),
		'render'   => 'navigation-menu/view.php',
		'dir'      => 'gw/gw-core/included-blocks',
		'fields'   => array(
			'menuId' => array(
				'type'    => 'integer',
				'control' => 'select',
				'label'   => __('Select Menu', 'gwblueprint'),
				'default' => 0,
				'options' => $__gw_created_menus_options,
			),
			'wrapperTag' => array(
				'type'    => 'string',
				'control' => 'select',
				'label'   => __('HTML wrapper tag', 'gwblueprint'),
				'default' => 'ul',
				'options' => array(
					array('label' => '<ul>', 'value' => 'ul'),
					array('label' => '<nav>', 'value' => 'nav'),
				),
			),
			'navId' => array(
				'type'    => 'string',
				'control' => 'text',
				'label'   => __('Element ID attribute', 'gwblueprint'),
				'default' => 'custom_menu',
			),
			'showMobileMenu' => array(
				'type'    => 'boolean',
				'control' => 'toggle',
				'label'   => __('Show mobile menu', 'gwblueprint'),
				'default' => true,
			),
		),
	));

	// Share Icons block
	gw_register_block('share-icons', array(
		'name'     => __('Share Icons', 'gwblueprint'),
		'category' => 'custom',
		'icon'     => 'share',
		'supports' => array(
			'anchor' => true,
			'customClassName' => true,
		),
		'render'   => 'share-icons/view.php',
		'dir'      => 'gw/gw-core/included-blocks',
		'fields'   => array(
			// Networks
			'facebook' => array('type'=>'boolean','control'=>'toggle','label'=>__('Facebook','gwblueprint'),'default'=>true),
			'x'        => array('type'=>'boolean','control'=>'toggle','label'=>__('X (Twitter)','gwblueprint'),'default'=>true),
			'linkedin' => array('type'=>'boolean','control'=>'toggle','label'=>__('LinkedIn','gwblueprint'),'default'=>true),
			'whatsapp' => array('type'=>'boolean','control'=>'toggle','label'=>__('WhatsApp','gwblueprint'),'default'=>true),
			'email'    => array('type'=>'boolean','control'=>'toggle','label'=>__('Email','gwblueprint'),'default'=>false),
			// Text / metadata
			'shareText'=> array('type'=>'string','control'=>'textarea','label'=>__('Share text','gwblueprint'),'default'=>''),
			'useTitle' => array('type'=>'boolean','control'=>'toggle','label'=>__('Use post title if empty','gwblueprint'),'default'=>true),
			'hashtags' => array('type'=>'string','control'=>'text','label'=>__('Hashtags (comma separated)','gwblueprint'),'default'=>''),
			// UTM
			'utm_source'   => array('type'=>'string','control'=>'text','label'=>__('utm_source','gwblueprint'),'default'=>''),
			'utm_medium'   => array('type'=>'string','control'=>'text','label'=>__('utm_medium','gwblueprint'),'default'=>''),
			'utm_campaign' => array('type'=>'string','control'=>'text','label'=>__('utm_campaign','gwblueprint'),'default'=>''),
			// Rel attributes
			'nofollow'   => array('type'=>'boolean','control'=>'toggle','label'=>__('rel="nofollow"','gwblueprint'),'default'=>false),
			'noopener'   => array('type'=>'boolean','control'=>'toggle','label'=>__('rel="noopener"','gwblueprint'),'default'=>true),
			'noreferrer' => array('type'=>'boolean','control'=>'toggle','label'=>__('rel="noreferrer"','gwblueprint'),'default'=>true),
		),
	));

	// Meta Tag block (renamed from span-meta)
	gw_register_block('meta-tag', array(
		'name'     => __('Meta Tag', 'gwblueprint'),
		'category' => 'custom',
		'icon'     => 'editor-italic',
		'supports' => array(
			'anchor' => true,
			'customClassName' => true,
		),
		'render'   => 'meta-tag/view.php',
		'dir'      => 'gw/gw-core/included-blocks',
		'fields'   => array(
			'key'    => array('type'=>'string','control'=>'text','label'=>__('Meta key','gwblueprint'),'default'=>'ejemplo'),
			'tag'    => array('type'=>'string','control'=>'select','label'=>__('HTML tag','gwblueprint'),'default'=>'span','options'=>array('span','small','strong','em','i','b','div')),
			'before' => array('type'=>'string','control'=>'text','label'=>__('Text before','gwblueprint'),'default'=>''),
			'after'  => array('type'=>'string','control'=>'text','label'=>__('Text after','gwblueprint'),'default'=>''),
		),
	));

	// Footer Text block
	gw_register_block('footer-text', array(
		'name'     => __('Footer Text', 'gwblueprint'),
		'category' => 'custom',
		'icon'     => 'editor-textcolor',
		'supports' => array(
			'anchor' => true,
			'customClassName' => true,
		),
		'render'   => 'footer-text/view.php',
		'dir'      => 'gw/gw-core/included-blocks',
		'fields'   => array(
			'text' => array(
				'type'    => 'string',
				'control' => 'textarea',
				'label'   => __('Text', 'gwblueprint'),
				'default' => '© [year] [site_name]',
			),
			'tag'  => array(
				'type'    => 'string',
				'control' => 'select',
				'label'   => __('HTML tag', 'gwblueprint'),
				'default' => 'p',
				'options' => array('p','small','div','span'),
			),
			'textAlign' => array(
				'type'    => 'string',
				'control' => 'select',
				'label'   => __('Text alignment', 'gwblueprint'),
				'default' => '',
				'options' => array(
					array('label' => __('(Default)','gwblueprint'), 'value' => ''),
					array('label' => __('Left','gwblueprint'), 'value' => 'left'),
					array('label' => __('Center','gwblueprint'), 'value' => 'center'),
					array('label' => __('Right','gwblueprint'), 'value' => 'right'),
					array('label' => __('Justify','gwblueprint'), 'value' => 'justify'),
				),
			),
		),
		'ui' => array(
			'toolbar' => array(
				array(
					'title' => __('Alignment', 'gwblueprint'),
					'controls' => array(
						array('type'=>'value','attribute'=>'textAlign','value'=>'left','icon'=>'editor-alignleft','label'=>__('Left','gwblueprint')),
						array('type'=>'value','attribute'=>'textAlign','value'=>'center','icon'=>'editor-aligncenter','label'=>__('Center','gwblueprint')),
						array('type'=>'value','attribute'=>'textAlign','value'=>'right','icon'=>'editor-alignright','label'=>__('Right','gwblueprint')),
						array('type'=>'value','attribute'=>'textAlign','value'=>'justify','icon'=>'editor-justify','label'=>__('Justify','gwblueprint')),
					),
				),
			),
		),
	));

	// Link Wrapper block
	gw_register_block('link-wrapper', array(
		'name'     => __('Link Wrapper', 'gwblueprint'),
		'category' => 'custom',
		'icon'     => 'admin-links',
		'supports' => array(
			'anchor' => true,
			'customClassName' => true,
			'__experimentalInnerBlocks' => true,
			'innerBlocks' => true,
			'color' => array(
				'text' => true,
				'background' => true,
				'link' => true,
				'gradients' => true,
			),
			'spacing' => array(
				'margin' => true,
				'padding' => true,
				'blockGap' => true,
			),
			'typography' => array(
				'fontSize' => true,
				'lineHeight' => true,
				'__experimentalFontFamily' => true,
				'__experimentalFontWeight' => true,
				'__experimentalFontStyle' => true,
				'__experimentalTextTransform' => true,
				'__experimentalTextDecoration' => true,
				'__experimentalLetterSpacing' => true,
				'__experimentalDefaultControls' => array(
					'fontSize' => true,
				),
			),
			'layout' => true,
		),
		'render'   => 'link-wrapper/view.php',
		'dir'      => 'gw/gw-core/included-blocks',
		'fields'   => array(
			'selected_page' => array( 'type' => 'string', 'control' => 'post_select', 'label' => 'Select a Page', ),
			'url'    => array('type'=>'string','control'=>'text','label'=>__('URL','gwblueprint'),'default'=>''),
			'target' => array('type'=>'string','control'=>'select','label'=>__('Target','gwblueprint'),'default'=>'_self','options'=>array('_self','_blank','_parent','_top')),
			'rel'    => array('type'=>'string','control'=>'text','label'=>__('Rel','gwblueprint'),'default'=>''),
		),
	));

	// All Settings Check block (demo block)
	gw_register_block('all-settings-check', array(
		'name'     => __('All Settings Check', 'gwblueprint'),
		'category' => 'custom',
		'icon'     => 'admin-settings',
		'supports' => array(
			'anchor' => true,
			'customClassName' => true,
		),
		'render'   => 'all-settings-check/view.php',
		'dir'      => 'gw/gw-core/included-blocks',
		'fields'   => array(
			// Text field
			'title' => array(
				'type'    => 'string',
				'control' => 'text',
				'label'   => __('Title', 'gwblueprint'),
				'default' => 'All Settings Check',
			),
			// Textarea field
			'description' => array(
				'type'    => 'string',
				'control' => 'textarea',
				'label'   => __('Description', 'gwblueprint'),
				'default' => 'Bloque de demostración para probar todos los tipos de campos.',
			),
			// Number field
			'quantity' => array(
				'type'    => 'number',
				'control' => 'number',
				'label'   => __('Quantity', 'gwblueprint'),
				'default' => 10,
			),
			// Range field
			'size' => array(
				'type'    => 'number',
				'control' => 'range',
				'label'   => __('Size', 'gwblueprint'),
				'min'     => 8,
				'max'     => 64,
				'step'    => 1,
				'default' => 16,
			),
			// Toggle field
			'enabled' => array(
				'type'    => 'boolean',
				'control' => 'toggle',
				'label'   => __('Enabled', 'gwblueprint'),
				'default' => true,
			),
			// Select field
			'theme' => array(
				'type'    => 'string',
				'control' => 'select',
				'label'   => __('Theme', 'gwblueprint'),
				'default' => 'auto',
				'options' => array(
					array('label' => __('Auto', 'gwblueprint'), 'value' => 'auto'),
					array('label' => __('Light', 'gwblueprint'), 'value' => 'light'),
					array('label' => __('Dark', 'gwblueprint'), 'value' => 'dark'),
				),
			),
			// Color field
			'color' => array(
				'type'    => 'string',
				'control' => 'color',
				'label'   => __('Color', 'gwblueprint'),
				'default' => '',
			),
			// Gallery field
			'gallery' => array(
				'type'    => 'string',
				'control' => 'gallery',
				'label'   => __('Gallery', 'gwblueprint'),
				'default' => '',
			),
			// Image field (saves as URL by default)
			'image' => array(
				'type'    => 'string',
				'control' => 'image',
				'label'   => __('Image', 'gwblueprint'),
				'default' => '',
				'saveAs'  => 'url',
			),
			// Image field (saves as ID)
			'imageId' => array(
				'type'    => 'string',
				'control' => 'image',
				'label'   => __('Image (as ID)', 'gwblueprint'),
				'default' => '',
				'saveAs'  => 'id',
			),
			// Post Select field
			'selected_page' => array(
				'type'    => 'string',
				'control' => 'post_select',
				'label'   => __('Selected Page', 'gwblueprint'),
				'post_type' => 'page',
			),
			// Icon Picker field
			'icon' => array(
				'type'    => 'string',
				'control' => 'icon_picker',
				'label'   => __('Icon', 'gwblueprint'),
				'default' => '',
			),
			// Repeater field
			'items' => array(
				'type'    => 'string',
				'control' => 'repeater',
				'label'   => __('Items', 'gwblueprint'),
				'default' => '',
				'subFields' => array(
					'title' => array(
						'type'    => 'string',
						'control' => 'text',
						'label'   => __('Title', 'gwblueprint'),
						'default' => '',
					),
					'description' => array(
						'type'    => 'string',
						'control' => 'textarea',
						'label'   => __('Description', 'gwblueprint'),
						'default' => '',
					),
					'active' => array(
						'type'    => 'boolean',
						'control' => 'toggle',
						'label'   => __('Active', 'gwblueprint'),
						'default' => false,
					),
				),
			),
		),
		'ui' => array(
			'tabs' => array(
				array(
					'name' => 'content',
					'title' => __('Content', 'gwblueprint'),
					'fields' => array('title', 'description', 'quantity', 'size', 'enabled', 'theme'),
				),
				array(
					'name' => 'media',
					'title' => __('Media', 'gwblueprint'),
					'fields' => array('gallery', 'image', 'imageId', 'icon'),
				),
				array(
					'name' => 'advanced',
					'title' => __('Advanced', 'gwblueprint'),
					'fields' => array('color', 'selected_page', 'items'),
				),
			),
		),
	));
});

