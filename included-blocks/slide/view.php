<?php
/**
 * Block: GW Slide
 *
 * Child of the Slider block. Wraps any inner blocks in a `.swiper-slide`
 * element so Swiper can treat it as a slide on the frontend. In the editor it
 * renders as a normal (stacked) InnerBlocks area.
 */

// Ensure inner content is available (InnerBlocks fallback).
if (empty($content) && !empty($block)) {
    if (isset($block->inner_blocks) && !empty($block->inner_blocks)) {
        $content = '';
        foreach ($block->inner_blocks as $inner_block) {
            $content .= $inner_block->render();
        }
    } elseif (isset($block->inner_content) && !empty($block->inner_content)) {
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

$wrapper_attributes = get_block_wrapper_attributes(array(
    'class' => 'swiper-slide',
));
?>

<div <?php echo $wrapper_attributes; ?>>
    <?php echo $content; ?>
</div>
