<?php

namespace Outstand\WP\Forms\Components;

use Outstand\WP\Forms\Fields\FieldInterface;

/**
 * A set of radios or checkboxes rendered under one field name.
 */
class Choice extends AbstractComponent implements GroupComponentInterface {

	/**
	 * Input type rendered for each option.
	 *
	 * @var string
	 */
	protected string $input_type;

	/**
	 * Constructor.
	 *
	 * @param FieldInterface $field      Field instance.
	 * @param string         $input_type Input type (radio or checkbox).
	 */
	public function __construct( FieldInterface $field, string $input_type = 'radio' ) {
		parent::__construct( $field );
		$this->input_type = $input_type;
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_markup(): string {

		$attributes = $this->get_attributes();
		$rules      = $this->field->get_validation_rules();

		$field_id    = $this->get_field_id();
		$field_name  = $this->get_field_name();
		$required    = ! empty( $rules['required'] );
		$options     = $attributes['options'] ?? [];
		$is_multiple = 'checkbox' === $this->input_type;

		// Checkboxes submit several values under one name, so the name carries
		// the array marker PHP needs to receive them as an array.
		$input_name    = $is_multiple ? $field_name . '[]' : $field_name;
		$default_value = $attributes['defaultValue'] ?? ( $is_multiple ? [] : '' );
		$checked       = array_map( 'strval', (array) $default_value );

		$markup = '';
		foreach ( $options as $index => $option ) {
			$option_id = sprintf( '%1$s-%2$d', $field_id, $index );

			$input_attributes = [
				'type'    => $this->input_type,
				'id'      => $option_id,
				'name'    => $input_name,
				'value'   => $option['value'],
				'checked' => in_array( (string) $option['value'], $checked, true ),
				'class'   => "osf-field__choice-input osf-field__choice-input--{$this->input_type}",
			] + $this->get_option_directives();

			$markup .= sprintf(
				'<div class="osf-field__choice" %1$s><input %2$s /><label class="osf-field__choice-label" for="%3$s">%4$s</label></div>',
				wp_interactivity_data_wp_context( [ 'optionValue' => (string) $option['value'] ] ),
				$this->build_attributes( $input_attributes ),
				esc_attr( $option_id ),
				esc_html( $option['label'] )
			);
		}

		$group_attributes = [
			// A radio group is a single tab stop with its own arrow-key
			// semantics; a set of checkboxes is not, so only radios get the
			// stronger role.
			'role'                           => 'radio' === $this->input_type ? 'radiogroup' : 'group',
			'aria-labelledby'                => $this->get_field_label_id(),
			'aria-required'                  => $required ? 'true' : null,
			'class'                          => "osf-field__choices osf-field__choices--{$this->input_type}",
			'data-wp-bind--aria-invalid'     => '!state.isFieldValid',
			'data-wp-bind--aria-describedby' => 'state.fieldAriaDescribedByAttribute',
			'data-wp-init---register'        => 'callbacks.registerField',
		];

		return sprintf(
			'<div %1$s>%2$s</div>',
			$this->build_attributes( $group_attributes ),
			$markup
		);
	}

	/**
	 * Get the Interactivity API directives bound to each option input.
	 *
	 * The shared directives bind `value` and listen for `input`, neither of
	 * which fits a control whose state is `checked` and which reports through
	 * `change`. Registration happens once on the group, not per option.
	 *
	 * @return array
	 */
	protected function get_option_directives(): array {
		return [
			'data-wp-bind--checked' => 'state.isOptionChecked',
			'data-wp-on--change'    => 'actions.handleChoiceChange',
			'data-wp-on--focus'     => 'actions.handleFieldFocus',
			'data-wp-on--blur'      => 'actions.handleFieldBlur',
		];
	}
}
