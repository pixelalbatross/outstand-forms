<?php
/**
 * Field: Turnstile
 *
 * @var array     $attributes Block attributes.
 * @var string    $content    Block default content.
 * @var \WP_Block $block      Block instance.
 */

namespace Outstand\WP\Forms;

use Outstand\WP\Forms\Settings;

// Get Turnstile settings.
$settings = get_option( Settings::OPTION_NAME, [] );
$site_key = $settings['site_key'] ?? '';

if ( empty( $site_key ) ) {
	return;
}

$turnstile_theme = $attributes['theme'] ?? 'auto';
$turnstile_size  = $attributes['size'] ?? 'normal';

wp_enqueue_script( 'cloudflare-turnstile' );

wp_interactivity_config(
	'osf/field-turnstile',
	[
		'enabled' => true,
		'siteKey' => $site_key,
		'mode'    => 'always',
	]
);

$wrapper_attributes = get_block_wrapper_attributes(
	[
		'class'               => 'cf-turnstile',
		'data-wp-interactive' => 'osf/field-turnstile',
		'data-wp-init'        => 'callbacks.init',
	]
);

$context = wp_interactivity_data_wp_context(
	[
		'theme' => $turnstile_theme,
		'size'  => $turnstile_size,
	]
);

?>

<div
	<?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	<?php echo $context; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
>
	<input type="hidden" name="cf-turnstile-response" value="" />
</div>
