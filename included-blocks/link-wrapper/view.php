<?php
/**
 * Block: GW Link Wrapper
 * In the editor renders as <div> for better UX
 * On the frontend renders as <a> for link functionality
 */

// Determine URL
$href = '';
if (!empty($attributes['selected_page'])) {
    $href = get_permalink((int)$attributes['selected_page']);
} elseif (!empty($attributes['url'])) {
    $href = $attributes['url'];
}

// Ensure content is available (fallback for InnerBlocks)
if (empty($content) && !empty($block)) {
    if (isset($block->inner_blocks) && !empty($block->inner_blocks)) {
        $content = '';
        foreach ($block->inner_blocks as $inner_block) {
            $content .= $inner_block->render();
        }
    } elseif (isset($block->inner_content) && !empty($block->inner_content)) {
        // Fallback: render inner_content if available
        $content = '';
        foreach ($block->inner_content as $chunk) {
            if (is_string($chunk)) {
                $content .= $chunk;
            } elseif (isset($chunk->parsed_block)) {
                $content .= render_block($chunk->parsed_block);
            }
        }
    }
}

// Prepare attributes for get_block_wrapper_attributes
$wrapper_args = array(
    'class' => 'link_wrapper',
);

// If frontend and we have a URL, add anchor attributes
if (!is_admin() && !empty($href)) {
    $wrapper_args['href'] = $href;
    if (!empty($attributes['target'])) {
        $wrapper_args['target'] = $attributes['target'];
    }
    if (!empty($attributes['rel'])) {
        $wrapper_args['rel'] = $attributes['rel'];
    }
}

$wrapper_attributes = get_block_wrapper_attributes($wrapper_args);
?>

<?php if (!empty($href)): ?>
    <?php if (is_admin()): ?>
        <?php // Editor: use <div> for better UX ?>
        <div <?php echo $wrapper_attributes; ?>>
            <?php echo $content; ?>
        </div>
    <?php else: ?>
        <?php // Frontend: use <a> for link functionality ?>
        <a <?php echo $wrapper_attributes; ?>>
            <?php echo $content; ?>
        </a>
    <?php endif; ?>
<?php else: ?>
    <?php // No URL: just show content (and wrapper for styles) ?>
    <div <?php echo $wrapper_attributes; ?>>
        <?php // print_r($attributes); // Debug ?>
        <?php echo $content; ?>
    </div>
<?php endif; ?>

