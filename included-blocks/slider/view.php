<?php
/**
 * Block: GW Slider (Swiper)
 *
 * Parent block whose children are Slide blocks. On the frontend it renders the
 * Swiper markup (.swiper > .swiper-wrapper > .swiper-slide) and initializes a
 * Swiper instance from the data-* config. In the editor it renders as a plain
 * stack of slides (handled by the editor JS, not this file).
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

// Make sure Swiper assets are present even outside singular views.
if (function_exists('wp_enqueue_script')) {
    wp_enqueue_style('swiper');
    wp_enqueue_script('swiper');
}

// Config with sane defaults.
$spv_mobile   = isset($attributes['spvMobile']) ? (float) $attributes['spvMobile'] : 1;
$spv_tablet   = isset($attributes['spvTablet']) ? (float) $attributes['spvTablet'] : 2;
$spv_desktop  = isset($attributes['spvDesktop']) ? (float) $attributes['spvDesktop'] : 3;
$space        = isset($attributes['spaceBetween']) ? (int) $attributes['spaceBetween'] : 24;
$speed        = isset($attributes['speed']) ? (int) $attributes['speed'] : 500;
$centered     = !empty($attributes['centered']);
$loop         = !empty($attributes['loop']);
$autoplay     = !empty($attributes['autoplay']);
$autoplay_dly = isset($attributes['autoplayDelay']) ? (int) $attributes['autoplayDelay'] : 4000;
$arrows       = !isset($attributes['showArrows']) || !empty($attributes['showArrows']);
$pagination   = !isset($attributes['showPagination']) || !empty($attributes['showPagination']);

$uid = 'gw_slider_' . uniqid();

$wrapper_attributes = get_block_wrapper_attributes(array(
    'class'                  => 'swiper gw-slider',
    'id'                     => $uid,
    'data-spv'               => $spv_mobile,
    'data-spv-tablet'        => $spv_tablet,
    'data-spv-desktop'       => $spv_desktop,
    'data-gap'               => $space,
    'data-speed'             => $speed,
    'data-centered'          => $centered ? '1' : '0',
    'data-loop'              => $loop ? '1' : '0',
    'data-autoplay'          => $autoplay ? '1' : '0',
    'data-autoplay-delay'    => $autoplay_dly,
    'data-arrows'            => $arrows ? '1' : '0',
    'data-pagination'        => $pagination ? '1' : '0',
));

// Print shared init script + base CSS once per page.
static $gw_slider_assets_printed = false;
?>
<?php if (!$gw_slider_assets_printed) : $gw_slider_assets_printed = true; ?>
<style id="gw-slider-css">
    .gw-slider { width: 100%; overflow: hidden; }
    .gw-slider .swiper-slide { height: auto; box-sizing: border-box; }
</style>
<?php if (!is_admin()) : ?>
<script id="gw-slider-init">
(function () {
    function build(el) {
        if (el.dataset.gwSwiperInit) { return; }
        el.dataset.gwSwiperInit = '1';

        var num = function (v, d) { var n = parseFloat(v); return isNaN(n) ? d : n; };
        var cfg = {
            slidesPerView: num(el.dataset.spv, 1),
            spaceBetween: num(el.dataset.gap, 0),
            speed: num(el.dataset.speed, 500),
            loop: el.dataset.loop === '1',
            centeredSlides: el.dataset.centered === '1',
            breakpoints: {
                768:  { slidesPerView: num(el.dataset.spvTablet, num(el.dataset.spv, 1)) },
                1024: { slidesPerView: num(el.dataset.spvDesktop, num(el.dataset.spvTablet, num(el.dataset.spv, 1))) }
            }
        };
        if (el.dataset.autoplay === '1') {
            cfg.autoplay = { delay: num(el.dataset.autoplayDelay, 4000), disableOnInteraction: false };
        }
        if (el.dataset.pagination === '1') {
            var pag = el.querySelector('.swiper-pagination');
            if (pag) { cfg.pagination = { el: pag, clickable: true }; }
        }
        if (el.dataset.arrows === '1') {
            var next = el.querySelector('.swiper-button-next');
            var prev = el.querySelector('.swiper-button-prev');
            if (next && prev) { cfg.navigation = { nextEl: next, prevEl: prev }; }
        }
        new Swiper(el, cfg);
    }

    function run() {
        if (typeof Swiper === 'undefined') { return setTimeout(run, 60); }
        var nodes = document.querySelectorAll('.gw-slider');
        for (var i = 0; i < nodes.length; i++) { build(nodes[i]); }
    }

    if (document.readyState === 'complete') { run(); }
    else { window.addEventListener('load', run); }
})();
</script>
<?php endif; ?>
<?php endif; ?>

<div <?php echo $wrapper_attributes; ?>>
    <div class="swiper-wrapper">
        <?php echo $content; ?>
    </div>
    <?php if ($pagination) : ?>
        <div class="swiper-pagination"></div>
    <?php endif; ?>
    <?php if ($arrows) : ?>
        <div class="swiper-button-prev"></div>
        <div class="swiper-button-next"></div>
    <?php endif; ?>
</div>
