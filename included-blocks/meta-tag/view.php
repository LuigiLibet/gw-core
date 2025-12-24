<?php
/**
 * Block: GW Meta Tag (simple)
 * Displays a post meta wrapped in an HTML tag (default <span>).
 * - key (string) meta key; default 'ejemplo'
 * - tag (string) allowed HTML tag
 * - before/after (string) text around the meta value
 * - className (string) additional CSS classes
 */
$post_id = get_the_ID();
if (!$post_id) { $post_id = get_queried_object_id(); }
if (!$post_id) return;

// Allowed tag names to avoid invalid markup
$allowed_tags = array('span','small','strong','em','i','b','div');
$raw_tag = isset($attributes['tag']) ? strtolower(preg_replace('/[^a-z0-9:-]/i','', (string)$attributes['tag'])) : 'span';
$tag = in_array($raw_tag, $allowed_tags, true) ? $raw_tag : 'span';

$cls   = !empty($attributes['className']) ? ' class="'.esc_attr($attributes['className']).'"' : '';
$key   = sanitize_key($attributes['key'] ?? 'ejemplo');
if (!$key) return;
$before= (string)($attributes['before'] ?? '');
$after = (string)($attributes['after'] ?? '');

$raw = get_post_meta($post_id, $key, true);
if (is_array($raw)) { $raw = implode(', ', array_map('wp_strip_all_tags', array_filter($raw, 'strlen'))); }

if ($raw === '' || $raw === null) {
    // Fallback: humanized meta key, e.g., "book_author" -> "Book Author"
    $fallback = ucwords(trim(preg_replace('/[_\-]+/', ' ', ltrim($key, '_'))));
    echo "<{$tag}{$cls}>".wp_kses_post($before).esc_html($fallback).wp_kses_post($after)."</{$tag}>";
    return;
}

echo "<{$tag}{$cls}>".wp_kses_post($before).esc_html((string)$raw).wp_kses_post($after)."</{$tag}>";

