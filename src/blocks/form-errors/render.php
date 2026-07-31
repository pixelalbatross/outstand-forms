<?php
/**
 * Form Errors
 *
 * @var array     $attributes Block attributes.
 * @var string    $content    Block default content.
 * @var \WP_Block $block      Block instance.
 */

namespace Outstand\WP\Forms;

$wrapper_attributes = get_block_wrapper_attributes(
	[
		'role'                 => 'alert',
		'data-wp-bind--hidden' => '!context.hasSubmissionError',
		'data-wp-text'         => 'context.submissionMessage',
	]
);

?>

<p <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>></p>
