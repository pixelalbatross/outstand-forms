<?php
/**
 * Field: Turnstile
 *
 * @var array     $attributes Block attributes.
 * @var string    $content    Block default content.
 * @var \WP_Block $block      Block instance.
 */

namespace Outstand\WP\Forms;

use Outstand\WP\Forms\Blocks\FieldTurnstile;
use Outstand\WP\Forms\Settings;

// When Turnstile isn't fully configured (site key and/or secret key
// missing), don't render the widget: without a secret key the backend
// can never verify a token anyway (@see FieldTurnstile::verify_form_submission()),
// so rendering it here would be misleading. This is the same test used
// on the backend, see FieldTurnstile::is_configured().
if ( ! FieldTurnstile::is_configured() ) {
	return;
}

$settings = get_option( Settings::OPTION_NAME, [] );
$site_key = $settings['site_key'] ?? '';

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
