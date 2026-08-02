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

// Without a custom action the form posts back to the page it renders on, so a
// visitor without JavaScript gets an HTML response instead of the raw JSON the
// REST endpoint would hand them. The endpoint stays the submission target of
// the JavaScript path, which reads it from the context.
$posts_back = empty( $form_action );
$submit_url = $posts_back ? rest_url( 'outstand-forms/v1/forms/submit' ) : $form_action;

if ( $posts_back ) {
	$permalink   = get_permalink();
	$form_action = $permalink ? $permalink : home_url( '/' );
}

$submission_state = FormSubmission::get_render_state( $form_id );

FormSubmission::register_field_state();

$wrapper_attributes = get_block_wrapper_attributes(
	[
		'id'                           => sprintf( 'osf-%s', $form_id ),
		'method'                       => 'post',
		'action'                       => esc_url( $form_action ),
		'data-wp-interactive'          => 'osf/form',
		'data-wp-on--submit'           => 'actions.handleFormSubmit',
		'data-wp-class--is-submitting' => 'context.isSubmitting',
		'data-wp-init---novalidate'    => 'callbacks.initNoValidate',
	]
);

$context = wp_interactivity_data_wp_context(
	[
		// Registry of per-field records, keyed by field name and populated by
		// each field's own `callbacks.registerField` init. Cast so it
		// serializes as a JSON object rather than an empty JSON array.
		'formFields'         => (object) [],
		'submitUrl'          => $submit_url,
		'isSubmitting'       => false,
		'isSubmitted'        => $submission_state['isSubmitted'],
		'hasSubmissionError' => $submission_state['hasSubmissionError'],
		'submissionMessage'  => $submission_state['submissionMessage'],
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
		// Messages naming a number are pluralized per field, where the count is
		// known, and arrive in the field's own context. See ValidationMessages.
		'validationMessages' => ValidationMessages::for_form( $form_id ),
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
	<?php if ( $posts_back ) : ?>
		<input type="hidden" name="<?php echo esc_attr( FormSubmission::MARKER_FIELD ); ?>" value="1">
		<input type="hidden" name="<?php echo esc_attr( FormSubmission::NONCE_FIELD ); ?>" value="<?php echo esc_attr( wp_create_nonce( FormSubmission::NONCE_ACTION ) ); ?>">
	<?php endif; ?>
</form>
