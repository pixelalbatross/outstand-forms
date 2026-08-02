<?php

namespace Outstand\WP\Forms;

use WP_Block;

/**
 * Renders a choice field block on the front end.
 *
 * The four choice blocks differ only in their type and class prefix, so the
 * wrapper, the context seed and the failed-submission replay live here once
 * instead of in four near-identical `render.php` files.
 */
class FieldRenderer {

	/**
	 * Render a field block.
	 *
	 * @param array    $attributes Block attributes.
	 * @param WP_Block $block      Block instance.
	 * @param string   $type       Field type, as registered on the server.
	 * @return string
	 */
	public static function render( array $attributes, WP_Block $block, string $type ): string {

		if ( empty( $block->context['osf/formId'] ) || empty( $attributes['fieldId'] ) ) {
			return '';
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

		// Options are authored as child blocks; the field system only ever sees
		// the normalized array.
		$attributes['options'] = Options::from_block_list( $block->inner_blocks );

		$required           = $attributes['required'] ?? false;
		$label              = $attributes['label'] ?? '';
		$help_text          = $attributes['helpText'] ?? '';
		$help_text_position = $attributes['helpTextPosition'];

		$field      = FieldFactory::instance()->create( $type, $attributes );
		$field_name = $field->get_field_name();

		// Asked of the field rather than read from the attributes, so the
		// wrapper class always describes the position that was rendered.
		$label_position = $field->get_label_position();

		// A submission that failed server-side redirects back here, so the
		// field re-renders with what the visitor chose and the rules it broke.
		$submission_state = FormSubmission::get_render_state( $attributes['formId'] );
		$field_errors     = $submission_state['errors'][ $field_name ] ?? [];

		$default_value = $attributes['defaultValue'] ?? '';

		if ( array_key_exists( $field_name, $submission_state['values'] ) ) {
			$default_value              = $submission_state['values'][ $field_name ];
			$attributes['defaultValue'] = $default_value;
			$field                      = FieldFactory::instance()->create( $type, $attributes );
		}

		$wrapper_classes = [
			'osf-field',
			"osf-field-{$type}",
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

		// Field-local context carries identity only. `initialRecord` is a
		// one-way seed consumed by `callbacks.registerField`, which moves it
		// into the form's `formFields` registry.
		$validation_rules = $field->get_validation_rules();

		$context = wp_interactivity_data_wp_context(
			[
				'fieldId'       => $field->get_field_id(),
				'fieldName'     => $field_name,
				'helpTextId'    => $field->get_help_text_id(),
				'errorId'       => $field->get_error_id(),
				// Only the rules naming a number, and only when this field has
				// them, so a field that needs no pluralization carries nothing.
				'fieldMessages' => ValidationMessages::for_field( $attributes['formId'], $validation_rules ),
				'initialRecord' => [
					'value'           => $default_value,
					'validationRules' => $field->get_validation_rules(),
					'isValid'         => empty( $field_errors ),
					'errors'          => $field_errors,
				],
			]
		);

		ob_start();
		$field->render();
		$markup = ob_get_clean();

		return sprintf(
			'<div %1$s %2$s>%3$s</div>',
			$wrapper_attributes,
			$context,
			$markup
		);
	}
}
