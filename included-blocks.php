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
		'style'    => 'gw/gw-core/included-blocks/navigation-menu/style.css',
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
			'flexDirection' => array(
				'type'    => 'string',
				'control' => 'buttongroup',
				'label'   => __('Direction', 'gwblueprint'),
				'default' => '',
				'options' => array(
					array('label' => __('Row', 'gwblueprint'),    'value' => 'row',    'icon' => 'arrow-right-alt2'),
					array('label' => __('Column', 'gwblueprint'), 'value' => 'column', 'icon' => 'arrow-down-alt2'),
				),
			),
			'justifyContent' => array(
				'type'    => 'string',
				'control' => 'buttongroup',
				'label'   => __('Horizontal alignment', 'gwblueprint'),
				'default' => '',
				'options' => array(
					array('label' => __('Start', 'gwblueprint'),         'value' => 'flex-start',    'icon' => 'editor-alignleft'),
					array('label' => __('Center', 'gwblueprint'),        'value' => 'center',        'icon' => 'editor-aligncenter'),
					array('label' => __('End', 'gwblueprint'),           'value' => 'flex-end',      'icon' => 'editor-alignright'),
					array('label' => __('Space between', 'gwblueprint'), 'value' => 'space-between', 'icon' => 'align-full-width'),
				),
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
		'style'    => 'gw/gw-core/included-blocks/share-icons/style.css',
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

	// ── SLIDER + SLIDE: RETIRADOS el 2026-08-14 ──────────────────────────────────────────────
	// Eran lo único que quedaba pidiendo a un CDN (Swiper, desde cdnjs) después de vendorizar
	// tipografía e iconos — doc 42 §4.2 del repo `juicy-platform`. Juicy Pocket se instala en la
	// PC de una tienda y funciona SIN internet, así que un bloque cuyo motor vive en cdnjs no
	// puede quedarse.
	//
	// Se midió antes de tocar nada: `wp:gw/slider` aparece en **0 posts** de los CINCO installs
	// —dashboard de POS (4 blogs), dashboard de Espresso, juicypos.com y juicyespresso.com—.
	// Nadie lo usa. Y el producto ya tiene su propio carrusel **sin librería a propósito**
	// (`juicy-core/assets/js/main.js:1168`), así que este bloque contradecía el patrón de la casa.
	//
	// Se apaga NO REGISTRANDO, nunca borrando: el código de los dos bloques sigue en
	// `included-blocks/slider/` y `included-blocks/slide/`, y volver a encenderlos es
	// `add_filter('gw_slider_activo', '__return_true')`. Si algún día se enciende de verdad,
	// **vendoriza Swiper primero** — no lo devuelvas al CDN.
	if (gw_slider_activo()) :

	// Slider block (Swiper) — parent. Only accepts Slide blocks as children.
	gw_register_block('slider', array(
		'name'     => __('Slider', 'gwblueprint'),
		'category' => 'custom',
		'icon'     => 'images-alt2',
		'supports' => array(
			'anchor' => true,
			'customClassName' => true,
			'__experimentalInnerBlocks' => true,
			'innerBlocks' => true,
			'align' => array('wide', 'full'),
			'spacing' => array(
				'margin' => true,
				'padding' => true,
			),
		),
		// Restrict children to Slide blocks and seed one empty slide.
		'allowedBlocks' => array('gw/slide'),
		'template'      => array(array('gw/slide')),
		'render'   => 'slider/view.php',
		'dir'      => 'gw/gw-core/included-blocks',
		'fields'   => array(
			'spvMobile'  => array('type'=>'number','control'=>'number','label'=>__('Slides per view (mobile)','gwblueprint'),'default'=>1,'min'=>1,'step'=>1),
			'spvTablet'  => array('type'=>'number','control'=>'number','label'=>__('Slides per view (tablet ≥768px)','gwblueprint'),'default'=>2,'min'=>1,'step'=>1),
			'spvDesktop' => array('type'=>'number','control'=>'number','label'=>__('Slides per view (desktop ≥1024px)','gwblueprint'),'default'=>3,'min'=>1,'step'=>1),
			'spaceBetween' => array('type'=>'number','control'=>'number','label'=>__('Space between (px)','gwblueprint'),'default'=>24,'min'=>0,'step'=>1),
			'speed'      => array('type'=>'number','control'=>'number','label'=>__('Transition speed (ms)','gwblueprint'),'default'=>500,'min'=>0,'step'=>50),
			'centered'   => array('type'=>'boolean','control'=>'toggle','label'=>__('Centered slides','gwblueprint'),'default'=>false),
			'loop'       => array('type'=>'boolean','control'=>'toggle','label'=>__('Loop','gwblueprint'),'default'=>false),
			'autoplay'   => array('type'=>'boolean','control'=>'toggle','label'=>__('Autoplay','gwblueprint'),'default'=>false),
			'autoplayDelay' => array('type'=>'number','control'=>'number','label'=>__('Autoplay delay (ms)','gwblueprint'),'default'=>4000,'min'=>500,'step'=>250),
			'showArrows' => array('type'=>'boolean','control'=>'toggle','label'=>__('Show arrows','gwblueprint'),'default'=>true),
			'showPagination' => array('type'=>'boolean','control'=>'toggle','label'=>__('Show pagination dots','gwblueprint'),'default'=>true),
		),
		'ui' => array(
			'tabs' => array(
				array('name'=>'layout','title'=>__('Layout','gwblueprint'),'fields'=>array('spvMobile','spvTablet','spvDesktop','spaceBetween','centered','speed')),
				array('name'=>'autoplay','title'=>__('Autoplay & Loop','gwblueprint'),'fields'=>array('loop','autoplay','autoplayDelay')),
				array('name'=>'nav','title'=>__('Navigation','gwblueprint'),'fields'=>array('showArrows','showPagination')),
			),
		),
	));

	// Slide block — child of Slider. Accepts any blocks inside.
	gw_register_block('slide', array(
		'name'     => __('Slide', 'gwblueprint'),
		'category' => 'custom',
		'icon'     => 'slides',
		'supports' => array(
			'anchor' => true,
			'customClassName' => true,
			'__experimentalInnerBlocks' => true,
			'innerBlocks' => true,
			'color' => array(
				'text' => true,
				'background' => true,
				'gradients' => true,
			),
			'spacing' => array(
				'margin' => true,
				'padding' => true,
				'blockGap' => true,
			),
		),
		// Only insertable inside a Slider.
		'parent'   => array('gw/slider'),
		'render'   => 'slide/view.php',
		'dir'      => 'gw/gw-core/included-blocks',
		'fields'   => array(),
	));

	endif; // gw_slider_activo()

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

/**
 * ¿Están vivos los bloques Slider/Slide? — el interruptor, en UN solo sitio.
 *
 * Apagado desde el 2026-08-14: su motor (Swiper) vivía en `cdnjs.cloudflare.com` y era el último
 * asset externo del producto. El razonamiento largo está arriba, junto a `gw_register_block`.
 *
 * Encender = `add_filter('gw_slider_activo', '__return_true')`. Y si se enciende, **vendoriza
 * Swiper antes**: devolverlo al CDN reintroduce justo lo que se acaba de quitar, y el check del
 * selftest que guarda esto sólo mira las funciones del core, no este fichero.
 */
if (!function_exists('gw_slider_activo')) {
	function gw_slider_activo() {
		return (bool) apply_filters('gw_slider_activo', false);
	}
}

/**
 * Register SwiperJS (CDN) for the Slider block.
 *
 * Registered here and enqueued on demand: pre-enqueued on singular views that
 * contain a Slider block (so CSS lands in <head>), and also enqueued from the
 * Slider's view.php as a fallback for any other context. Both are idempotent.
 *
 * NOTA (2026-08-14): todo este bloque cuelga ahora de `gw_slider_activo()`. Con el slider
 * apagado, Swiper **ni siquiera se registra**, así que WordPress deja de emitir el
 * `dns-prefetch` a cdnjs que salía en el <head> de cada página. Las constantes y los hashes SRI
 * se conservan para el día que se encienda — pero apuntando a un fichero local, no al CDN.
 */
if (gw_slider_activo()) :

// Pinned version + Subresource Integrity hashes (from cdnjs) so a compromised CDN
// cannot inject altered code into client sites. Update all three together on bump.
define('GW_SWIPER_VERSION', '11.0.5');
define('GW_SWIPER_SRI_JS',  'sha512-Ysw1DcK1P+uYLqprEAzNQJP+J4hTx4t/3X2nbVwszao8wD+9afLjBQYjz7Uk4ADP+Er++mJoScI42ueGtQOzEA==');
define('GW_SWIPER_SRI_CSS', 'sha512-rd0qOHVMOcez6pLWPVFIv7EfSdGKLt+eafXh4RO/12Fgr41hDQxfGvoi1Vy55QIVcQEujUE1LQrATCLl2Fs+ag==');

add_action('wp_enqueue_scripts', function () {
	wp_register_style(
		'swiper',
		'https://cdnjs.cloudflare.com/ajax/libs/Swiper/' . GW_SWIPER_VERSION . '/swiper-bundle.min.css',
		array(),
		GW_SWIPER_VERSION
	);
	wp_register_script(
		'swiper',
		'https://cdnjs.cloudflare.com/ajax/libs/Swiper/' . GW_SWIPER_VERSION . '/swiper-bundle.min.js',
		array(),
		GW_SWIPER_VERSION,
		true
	);

	if (is_singular() && function_exists('has_block') && has_block('gw/slider')) {
		wp_enqueue_style('swiper');
		wp_enqueue_script('swiper');
	}
});

// Add integrity + crossorigin attributes to the Swiper <script>/<link> tags
// (wp_register_script/style do not support SRI natively).
add_filter('script_loader_tag', function ($tag, $handle) {
	if ('swiper' !== $handle) {
		return $tag;
	}
	return str_replace(
		' src=',
		' integrity="' . esc_attr(GW_SWIPER_SRI_JS) . '" crossorigin="anonymous" src=',
		$tag
	);
}, 10, 2);

add_filter('style_loader_tag', function ($tag, $handle) {
	if ('swiper' !== $handle) {
		return $tag;
	}
	return str_replace(
		' href=',
		' integrity="' . esc_attr(GW_SWIPER_SRI_CSS) . '" crossorigin="anonymous" href=',
		$tag
	);
}, 10, 2);

endif; // gw_slider_activo()

