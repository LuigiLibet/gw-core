<?php
/**
 * Block: GW Share Icons (dynamic)
 * Recommended attributes (add them when registering if needed):
 * - facebook,x,linkedin,whatsapp,email (toggles) → default true except email
 * - shareText,useTitle,hashtags
 * - utm_source,utm_medium,utm_campaign
 * - className,nofollow,noopener,noreferrer
 */
$post_id = get_the_ID();
if (!$post_id) { $post_id = get_queried_object_id(); }
if (!$post_id) return;

$base_url = get_permalink($post_id);

// Optional UTM parameters
$utm = array_filter([
  'utm_source'   => $attributes['utm_source']   ?? '',
  'utm_medium'   => $attributes['utm_medium']   ?? '',
  'utm_campaign' => $attributes['utm_campaign'] ?? '',
]);
if (!empty($utm)) {
  $base_url = add_query_arg($utm, $base_url);
}

// Text to share
$title      = get_the_title($post_id);
$share_text = trim((string)($attributes['shareText'] ?? ''));
if ($share_text === '' && !empty($attributes['useTitle'])) { $share_text = $title; }

$hashtags = array_filter(array_map('trim', explode(',', (string)($attributes['hashtags'] ?? ''))));

$enc = static function($s){ return rawurlencode((string)$s); };
$url_enc  = $enc($base_url);
$text_enc = $enc($share_text);
$hash_enc = $enc(implode(',', $hashtags));

$rels = [];
if (!empty($attributes['nofollow']))   $rels[] = 'nofollow';
if (!empty($attributes['noopener']))   $rels[] = 'noopener';
if (!empty($attributes['noreferrer'])) $rels[] = 'noreferrer';
$rel_attr = empty($rels) ? '' : ' rel="'.esc_attr(implode(' ', $rels)).'"';
$target   = ' target="_blank"';

$cls = !empty($attributes['className']) ? ' '.esc_attr($attributes['className']) : '';

$enabled = [
  'facebook' => array_key_exists('facebook',$attributes) ? !empty($attributes['facebook']) : true,
  'x'        => array_key_exists('x',$attributes) ? !empty($attributes['x']) : true,
  'linkedin' => array_key_exists('linkedin',$attributes) ? !empty($attributes['linkedin']) : true,
  'whatsapp' => array_key_exists('whatsapp',$attributes) ? !empty($attributes['whatsapp']) : true,
  'email'    => !empty($attributes['email']),
];

$links = [];
if ($enabled['facebook']) {
  $links['facebook'] = ['href' => 'https://www.facebook.com/sharer/sharer.php?u='.$url_enc, 'label'=>'Facebook','icon'=>'facebook'];
}
if ($enabled['x']) {
  $href = 'https://twitter.com/intent/tweet?url='.$url_enc;
  if ($share_text !== '') { $href .= '&text='.$text_enc; }
  if (!empty($hashtags))  { $href .= '&hashtags='.$hash_enc; }
  $links['x'] = ['href'=>$href,'label'=>'X','icon'=>'x'];
}
if ($enabled['linkedin']) {
  $links['linkedin'] = ['href'=>'https://www.linkedin.com/sharing/share-offsite/?url='.$url_enc,'label'=>'LinkedIn','icon'=>'linkedin'];
}
if ($enabled['whatsapp']) {
  $wa_text = $share_text !== '' ? ($share_text.' '.$base_url) : $base_url;
  $links['whatsapp'] = ['href'=>'https://api.whatsapp.com/send?text='.$enc($wa_text),'label'=>'WhatsApp','icon'=>'whatsapp'];
}
if ($enabled['email']) {
  $subject = $share_text !== '' ? $share_text : $title;
  $body    = $base_url;
  $links['email'] = ['href'=>'mailto:?subject='.$enc($subject).'&body='.$enc($body),'label'=>'Email','icon'=>'envelope'];
}

if (empty($links)) return;
?>
<nav class="share_icons d-flex<?php echo $cls; ?>">
  <?php foreach ($links as $key => $data): ?>
    <a href="<?php echo esc_url($data['href']); ?>"<?php echo $target.$rel_attr; ?> aria-label="<?php echo esc_attr('Share on '.$data['label']); ?>">
      <i class="icon-<?php echo esc_attr($data['icon']); ?>"></i>
    </a>
  <?php endforeach; ?>
</nav>

