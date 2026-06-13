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

// Use the framework's robust editor detection (is_admin() is false during the
// REST render of the editor, which would wrongly emit an <a> in the preview).
$is_editor = function_exists('gw_in_editor') ? gw_in_editor() : (defined('REST_REQUEST') && REST_REQUEST);

// Prepare attributes for get_block_wrapper_attributes
$wrapper_args = array(
    'class' => 'link_wrapper',
);

// If frontend and we have a URL, add anchor attributes
if (!$is_editor && !empty($href)) {
    // esc_url() strips dangerous schemes (e.g. javascript:) that esc_attr alone would not.
    $safe_href = esc_url($href);
    if (!empty($safe_href)) {
        $wrapper_args['href'] = $safe_href;
        $allowed_targets = array('_self', '_blank', '_parent', '_top');
        if (!empty($attributes['target']) && in_array($attributes['target'], $allowed_targets, true)) {
            $wrapper_args['target'] = $attributes['target'];
            // Harden new-tab links against reverse tabnabbing.
            if ($attributes['target'] === '_blank') {
                $rel = !empty($attributes['rel']) ? $attributes['rel'] . ' ' : '';
                $wrapper_args['rel'] = $rel . 'noopener noreferrer';
            }
        }
        if (empty($wrapper_args['rel']) && !empty($attributes['rel'])) {
            $wrapper_args['rel'] = $attributes['rel'];
        }
    }
}

$wrapper_attributes = get_block_wrapper_attributes($wrapper_args);

$render_as_link = !$is_editor && !empty($wrapper_args['href']);
?>

<?php if ($render_as_link): ?>
    <?php // Frontend with a valid URL: use <a> for link functionality ?>
    <a <?php echo $wrapper_attributes; ?>>
        <?php echo $content; ?>
    </a>
<?php else: ?>
    <?php // Editor preview, or no/invalid URL: use <div> for better UX and styling ?>
    <div <?php echo $wrapper_attributes; ?>>
        <?php echo $content; ?>
    </div>
<?php endif; ?>

