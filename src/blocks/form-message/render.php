<?php
/**
 * Form Message
 *
 * @var array     $attributes Block attributes.
 * @var string    $content    Block default content.
 * @var \WP_Block $block      Block instance.
 */

namespace Outstand\WP\Forms;

$wrapper_attributes = get_block_wrapper_attributes(
	[
		'role'                 => 'status',
		'aria-live'            => 'polite',
		'data-wp-bind--hidden' => '!context.isSubmitted',
	]
);

?>

<div <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
</div>
