<?php
/**
 * Bloque: GW All Settings Check (demo)
 * Muestra valores de atributos para probar UI/SSR con todos los tipos de campos.
 */
if (!defined('ABSPATH')) {
	exit;
}

$cls = !empty($attributes['className']) ? ' ' . esc_attr($attributes['className']) : '';
$post_id = get_the_ID();
if (!$post_id) {
	$post_id = get_queried_object_id();
}
$post_title = $post_id ? get_the_title($post_id) : '';

// Helper function to get attribute values
$get = static function($key, $default = '') use ($attributes) {
	return array_key_exists($key, $attributes) ? $attributes[$key] : $default;
};

// Basic fields
$title = (string)$get('title', 'All Settings Check');
$desc = (string)$get('description', 'Bloque de demostración para probar todos los tipos de campos.');
$quantity = isset($attributes['quantity']) ? (int)$attributes['quantity'] : 10;
$size = isset($attributes['size']) ? (int)$attributes['size'] : 16;
$enabled = !empty($attributes['enabled']);
$theme = (string)$get('theme', 'auto');
$color = (string)$get('color', '');

// Media fields
$gallery_ids = (string)$get('gallery', '');
$image_value = (string)$get('image', '');
$image_id_value = (string)$get('imageId', '');
$icon_value = (string)$get('icon', '');
$selected_page = (string)$get('selected_page', '');

// Repeater field
$items_data = (string)$get('items', '');

// Inline styles for quick visual feedback
$style = '';
if ($size) {
	$style .= 'font-size:' . intval($size) . 'px;';
}
if ($color) {
	$style .= ' color:' . esc_attr($color) . ';';
}

// Helper functions (if available)
$has_helpers = function_exists('gw_get_gallery_urls') && 
               function_exists('gw_get_image_url') && 
               function_exists('gw_get_repeater_items') && 
               function_exists('gw_icon');
?>
<div class="gw-all-settings-check p-3 border" style="<?php echo esc_attr($style); ?>">
	<h1>All Settings Check</h1>
	<h3 style="margin-top:0;"><?php echo esc_html($title); ?></h3>
	
	<?php if ($desc !== ''): ?>
		<div class="text-muted mb-3"><?php echo wp_kses_post($desc); ?></div>
	<?php endif; ?>

	<div class="row">
		<div class="col-md-6">
			<h4>Basic Fields</h4>
			<ul class="m-0 mt-2 p-0" style="list-style:none">
				<li><strong>Text:</strong> <code>title</code> = <?php echo esc_html($title); ?></li>
				<li><strong>Textarea:</strong> <code>description</code> = <?php echo esc_html(substr($desc, 0, 50)) . (strlen($desc) > 50 ? '...' : ''); ?></li>
				<li><strong>Number:</strong> <code>quantity</code> = <?php echo esc_html((string)$quantity); ?></li>
				<li><strong>Range:</strong> <code>size</code> = <?php echo esc_html((string)$size); ?>px</li>
				<li><strong>Toggle:</strong> <code>enabled</code> = <?php echo $enabled ? '<span style="color:green;">true</span>' : '<span style="color:red;">false</span>'; ?></li>
				<li><strong>Select:</strong> <code>theme</code> = <?php echo esc_html($theme); ?></li>
				<li><strong>Color:</strong> <code>color</code> = 
					<span style="display:inline-block;width:1em;height:1em;vertical-align:middle;background:<?php echo esc_attr($color ?: 'transparent'); ?>;border:1px solid #ccc;margin:0 4px;"></span>
					<?php echo esc_html($color ?: '(none)'); ?>
				</li>
			</ul>
		</div>

		<div class="col-md-6">
			<h4>Media Fields</h4>
			<ul class="m-0 mt-2 p-0" style="list-style:none">
				<li><strong>Gallery:</strong> <code>gallery</code> = 
					<?php 
					if (!empty($gallery_ids)) {
						if ($has_helpers) {
							$gallery_urls = gw_get_gallery_urls($gallery_ids, 'thumbnail');
							echo esc_html($gallery_ids) . ' (' . count($gallery_urls) . ' images)';
						} else {
							echo esc_html($gallery_ids);
						}
					} else {
						echo '<span style="color:#999;">(empty)</span>';
					}
					?>
				</li>
				<li><strong>Image (URL):</strong> <code>image</code> = 
					<?php 
					if (!empty($image_value)) {
						if ($has_helpers) {
							$img_url = gw_get_image_url($image_value, 'thumbnail');
							echo esc_html($image_value) . ' <span style="color:#999;">(' . (is_numeric($image_value) ? 'ID' : 'URL') . ')</span>';
						} else {
							echo esc_html($image_value);
						}
					} else {
						echo '<span style="color:#999;">(empty)</span>';
					}
					?>
				</li>
				<li><strong>Image (ID):</strong> <code>imageId</code> = 
					<?php 
					if (!empty($image_id_value)) {
						if ($has_helpers) {
							$img_url = gw_get_image_url($image_id_value, 'thumbnail');
							echo esc_html($image_id_value) . ' <span style="color:#999;">(ID)</span>';
						} else {
							echo esc_html($image_id_value);
						}
					} else {
						echo '<span style="color:#999;">(empty)</span>';
					}
					?>
				</li>
				<li><strong>Icon:</strong> <code>icon</code> = 
					<?php 
					if (!empty($icon_value)) {
						if ($has_helpers) {
							echo esc_html($icon_value);
							echo ' ' . gw_icon($icon_value);
						} else {
							echo esc_html($icon_value);
						}
					} else {
						echo '<span style="color:#999;">(empty)</span>';
					}
					?>
				</li>
			</ul>
		</div>
	</div>

	<div class="row mt-3">
		<div class="col-md-6">
			<h4>Advanced Fields</h4>
			<ul class="m-0 mt-2 p-0" style="list-style:none">
				<li><strong>Post Select:</strong> <code>selected_page</code> = 
					<?php 
					if (!empty($selected_page)) {
						$page_title = get_the_title((int)$selected_page);
						echo esc_html($selected_page) . ' - ' . esc_html($page_title);
					} else {
						echo '<span style="color:#999;">(empty)</span>';
					}
					?>
				</li>
			</ul>
		</div>

		<div class="col-md-6">
			<h4>Repeater Field</h4>
			<?php 
			if (!empty($items_data)) {
				if ($has_helpers) {
					$items = gw_get_repeater_items($items_data);
					if (!empty($items)) {
						echo '<ul class="m-0 mt-2 p-0" style="list-style:none">';
						foreach ($items as $index => $item) {
							$item_title = isset($item['title']) ? esc_html($item['title']) : '(no title)';
							$item_desc = isset($item['description']) ? esc_html(substr($item['description'], 0, 30)) : '';
							$item_active = isset($item['active']) && $item['active'] ? '✓' : '✗';
							echo '<li><strong>Item ' . ($index + 1) . ':</strong> ' . $item_title . ' ' . $item_active;
							if ($item_desc) {
								echo ' - ' . $item_desc . '...';
							}
							echo '</li>';
						}
						echo '</ul>';
					} else {
						echo '<span style="color:#999;">(empty array)</span>';
					}
				} else {
					echo '<code>' . esc_html(substr($items_data, 0, 50)) . '...</code>';
				}
			} else {
				echo '<span style="color:#999;">(empty)</span>';
			}
			?>
		</div>
	</div>

	<?php if ($post_title): ?>
		<div class="mt-3 pt-3 border-top">
			<small><strong>Current Post:</strong> <?php echo esc_html($post_title); ?> (#<?php echo esc_html((string)$post_id); ?>)</small>
		</div>
	<?php endif; ?>

	<?php if (!empty($gallery_ids) && $has_helpers): 
		$gallery_urls = gw_get_gallery_urls($gallery_ids, 'thumbnail');
		if (!empty($gallery_urls)):
	?>
		<div class="mt-3 pt-3 border-top">
			<h4>Gallery Preview</h4>
			<div style="display:flex;gap:8px;flex-wrap:wrap;">
				<?php foreach ($gallery_urls as $url): ?>
					<img src="<?php echo esc_url($url); ?>" alt="" style="max-width:100px;height:auto;border:1px solid #ddd;border-radius:4px;" />
				<?php endforeach; ?>
			</div>
		</div>
	<?php 
		endif;
	endif; 
	?>

	<?php if (!empty($image_value) && $has_helpers): 
		$img_url = gw_get_image_url($image_value, 'medium');
		if (!empty($img_url)):
	?>
		<div class="mt-3 pt-3 border-top">
			<h4>Image Preview (URL)</h4>
			<img src="<?php echo esc_url($img_url); ?>" alt="" style="max-width:200px;height:auto;border:1px solid #ddd;border-radius:4px;" />
		</div>
	<?php 
		endif;
	endif; 
	?>

	<?php if (!empty($image_id_value) && $has_helpers): 
		$img_url = gw_get_image_url($image_id_value, 'medium');
		if (!empty($img_url)):
	?>
		<div class="mt-3 pt-3 border-top">
			<h4>Image Preview (ID)</h4>
			<img src="<?php echo esc_url($img_url); ?>" alt="" style="max-width:200px;height:auto;border:1px solid #ddd;border-radius:4px;" />
		</div>
	<?php 
		endif;
	endif; 
	?>
</div>

