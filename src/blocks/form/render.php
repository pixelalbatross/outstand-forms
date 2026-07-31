<?php
/**
 * Form
 *
 * @var array     $attributes Block attributes.
 * @var string    $content    Block default content.
 * @var \WP_Block $block      Block instance.
 */

namespace Outstand\WP\Forms;

if ( empty( $attributes['formId'] ) ) {
	return;
}

$form_id     = $attributes['formId'];
$form_action = $attributes['formAction'] ?? '';

if ( empty( $form_action ) ) {
	$form_action = rest_url( 'outstand-forms/v1/forms/submit' );
}

$wrapper_attributes = get_block_wrapper_attributes(
	[
		'id'                           => sprintf( 'osf-%s', $form_id ),
		'method'                       => 'post',
		'action'                       => esc_url( $form_action ),
		'novalidate'                   => '',
		'data-wp-interactive'          => 'osf/form',
		'data-wp-on--submit'           => 'actions.handleFormSubmit',
	]
);

wp_interactivity_state( 'osf/form' );

$context = wp_interactivity_data_wp_context(
	[
		'formFields' => [],
	]
);

wp_interactivity_config(
	'osf/form',
	[
		'submissionMessages' => [
			/**
			 * Filters the form submission error message.
			 *
			 * @param string $message The error message.
			 * @param string $form_id The form ID.
			 * @return string
			 */
			'error' => apply_filters(
				'outstand_forms_submission_error_message',
				__( 'There was a problem submitting the form. Please try again.', 'outstand-forms' ),
				$form_id
			),
		],
		/**
		 * Filters the validation messages.
		 *
		 * @param array  $messages An associative array of messages keyed by rule name.
		 *                         Example:
		 *                         [
		 *                             'required'  => 'This field is required.',
		 *                             'minLength' => 'Please enter at least {{min}} characters.',
		 *                             'maxLength' => 'Please enter no more than {{max}} characters.',
		 *                         ]
		 * @param string $form_id  The form ID.
		 * @return array
		 */
		'validationMessages' => apply_filters(
			'outstand_forms_validation_messages',
			[
				'required'  => __( 'This field is required.', 'outstand-forms' ),
				'pattern'   => __( 'The value does not match the expected format.', 'outstand-forms' ),
				'email'     => __( 'Please enter a valid email address.', 'outstand-forms' ),
				'url'       => __( 'Please enter a valid URL.', 'outstand-forms' ),
				'minLength' => __( 'Please enter at least {{min}} characters.', 'outstand-forms' ),
				'maxLength' => __( 'Please enter no more than {{max}} characters.', 'outstand-forms' ),
				'min'       => __( 'Please enter a value greater than or equal to {{min}}.', 'outstand-forms' ),
				'max'       => __( 'Please enter a value less than or equal to {{max}}.', 'outstand-forms' ),
			],
			$form_id
		),
	]
);

?>

<form
	<?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	<?php echo $context; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
>
	<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

	<input type="hidden" name="form_id" value="<?php echo esc_attr( $form_id ); ?>">
	<input type="hidden" name="post_id" value="<?php echo esc_attr( get_the_ID() ); ?>">
	<input type="hidden" name="_wpnonce" value="<?php echo esc_attr( wp_create_nonce( 'wp_rest' ) ); ?>">
</form>
