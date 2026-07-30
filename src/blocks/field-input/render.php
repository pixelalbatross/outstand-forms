<?php
/**
 * Field: Input
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

$field_type         = $attributes['type'] ?? 'text';
$default_value      = $attributes['defaultValue'] ?? '';
$required           = $attributes['required'] ?? false;
$label              = $attributes['label'] ?? '';
$label_position     = $attributes['labelPosition'];
$help_text          = $attributes['helpText'] ?? '';
$help_text_position = $attributes['helpTextPosition'];

$factory = new FieldFactory();
$field   = $factory->create( $field_type, $attributes );

$wrapper_classes = [
	'osf-field',
	'osf-field-input',
	"osf-field-input--{$field_type}",
	"osf-field-input--label-{$label_position}",
	"osf-field-input--help-{$help_text_position}",
	$required ? 'osf-field-input--required' : '',
	$label ? 'osf-field-input--has-label' : '',
	$help_text ? 'osf-field-input--has-help' : '',
];
$wrapper_classes = array_filter( $wrapper_classes );
$wrapper_classes = array_map( 'sanitize_html_class', $wrapper_classes );

$wrapper_attributes = get_block_wrapper_attributes(
	[
		'class'                     => implode( ' ', $wrapper_classes ),
		'data-wp-class--is-focused' => 'context.isFocused',
		'data-wp-class--is-invalid' => '!context.isValid',
	]
);

$context = wp_interactivity_data_wp_context(
	[
		'type'            => $field_type,
		'value'           => $default_value,
		'isValid'         => true,
		'isFocused'       => false,
		'fieldId'         => $field->get_field_id(),
		'fieldName'       => $field->get_field_name(),
		'helpTextId'      => $field->get_help_text_id(),
		'errorId'         => $field->get_error_id(),
		'validationRules' => $field->get_validation_rules(),
	]
);

?>

<div
	<?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	<?php echo $context; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
>
	<?php $field->render(); ?>
</div>
