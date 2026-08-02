<?php
/**
 * Field: Textarea
 *
 * @var array     $attributes Block attributes.
 * @var string    $content    Block default content.
 * @var \WP_Block $block      Block instance.
 */

namespace Outstand\WP\Forms;

if ( empty( $block->context['osf/formId'] ) || empty( $attributes['fieldId'] ) ) {
	return;
}

$attributes = array_merge(
	[
		'formId'            => $block->context['osf/formId'],
		'labelPosition'     => $block->context['osf/labelPosition'],
		'helpTextPosition'  => $block->context['osf/helpTextPosition'],
		'requiredIndicator' => $block->context['osf/requiredIndicator'],
	],
	$attributes
);

$default_value      = $attributes['defaultValue'] ?? '';
$required           = $attributes['required'] ?? false;
$label              = $attributes['label'] ?? '';
$label_position     = $attributes['labelPosition'];
$help_text          = $attributes['helpText'] ?? '';
$help_text_position = $attributes['helpTextPosition'];

$factory = FieldFactory::instance();
$field   = $factory->create( 'textarea', $attributes );

// A submission that failed server-side redirects back here, so the field
// re-renders with what the visitor typed and the rules it broke.
$submission_state = FormSubmission::get_render_state( $attributes['formId'] );
$field_name       = $field->get_field_name();
$field_errors     = $submission_state['errors'][ $field_name ] ?? [];

if ( array_key_exists( $field_name, $submission_state['values'] ) ) {
	$default_value              = $submission_state['values'][ $field_name ];
	$attributes['defaultValue'] = $default_value;
	$field                      = $factory->create( 'textarea', $attributes );
}

$wrapper_classes = [
	'osf-field',
	'osf-field-textarea',
	"osf-field--label-{$label_position}",
	"osf-field--help-{$help_text_position}",
	$required ? 'osf-field--required' : '',
	$label ? 'osf-field--has-label' : '',
	$help_text ? 'osf-field--has-help' : '',
];
$wrapper_classes = array_filter( $wrapper_classes );
$wrapper_classes = array_map( 'sanitize_html_class', $wrapper_classes );

$wrapper_attributes = get_block_wrapper_attributes(
	[
		'class'                     => implode( ' ', $wrapper_classes ),
		'data-wp-class--is-focused' => 'state.isFieldFocused',
		'data-wp-class--is-invalid' => '!state.isFieldValid',
	]
);

// Field-local context carries identity only. `initialRecord` is a one-way
// seed consumed by `callbacks.registerField`, which moves it into the form's
// `formFields` registry; the form owns every mutable field value from then on.
$context = wp_interactivity_data_wp_context(
	[
		'fieldId'       => $field->get_field_id(),
		'fieldName'     => $field->get_field_name(),
		'helpTextId'    => $field->get_help_text_id(),
		'errorId'       => $field->get_error_id(),
		// Only the rules naming a number, and only when this field has them, so
		// a field that needs no pluralization carries nothing.
		'fieldMessages' => ValidationMessages::for_field( $attributes['formId'], $field->get_validation_rules() ),
		'initialRecord' => [
			'value'           => $default_value,
			'validationRules' => $field->get_validation_rules(),
			'isValid'         => empty( $field_errors ),
			'errors'          => $field_errors,
		],
	]
);

?>

<div
	<?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	<?php echo $context; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
>
	<?php $field->render(); ?>
</div>
