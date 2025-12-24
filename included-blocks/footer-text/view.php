<?php
/**
 * Block: GW Footer Text
 * Renders a paragraph, replacing [year] with the current year.
 * Attributes:
 * - text (string) content; supports [year] token
 * - tag (string) HTML tag, default 'p'
 * - className (string) additional CSS classes
 */
$allowed_tags = array('p','small','div','span');
$raw_tag = isset($attributes['tag']) ? strtolower(preg_replace('/[^a-z0-9:-]/i','', (string)$attributes['tag'])) : 'p';
$tag = in_array($raw_tag, $allowed_tags, true) ? $raw_tag : 'p';

$classes = array();
if (!empty($attributes['className'])) { $classes[] = $attributes['className']; }

// Add has-text-align-* class when requested
$text_align = isset($attributes['textAlign']) ? (string)$attributes['textAlign'] : '';
if (in_array($text_align, array('left','center','right','justify'), true)) {
  $classes[] = 'has-text-align-' . $text_align;
}

$cls = empty($classes) ? '' : ' class="'.esc_attr(implode(' ', $classes)).'"';

$default_text = '© [year] [site_name]';
$text = (string)($attributes['text'] ?? $default_text);

// Token replacements
$replacements = array(
  '[year]'      => date('Y'),
  '[site_name]' => get_bloginfo('name'),
  '[home_url]'  => home_url('/'),
);
$text = strtr($text, $replacements);

echo "<{$tag}{$cls}>".wp_kses_post($text)."</{$tag}>";

