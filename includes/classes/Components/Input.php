<?php

namespace Outstand\WP\Forms\Components;

use Outstand\WP\Forms\Fields\FieldInterface;

class Input extends AbstractComponent {

	/**
	 * Input type.
	 *
	 * @var string
	 */
	protected string $input_type;

	/**
	 * Constructor.
	 *
	 * @param FieldInterface $field      Field instance.
	 * @param string         $input_type Input type (text, email, number, etc.).
	 */
	public function __construct( FieldInterface $field, string $input_type = 'text' ) {
		parent::__construct( $field );
		$this->input_type = $input_type;
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_markup(): string {

		$field_id   = $this->get_field_id();
		$field_name = $this->get_field_name();
		$label_id   = $this->get_field_label_id();
		$attributes = $this->get_attributes();

		// Constraint attributes derive from the same validation rules used by
		// the client- and server-side validators, so the three surfaces agree.
		$rules = $this->field->get_validation_rules();

		$default_value = $attributes['defaultValue'] ?? '';
		$placeholder   = $attributes['placeholder'] ?? '';
		$autocomplete  = $attributes['autocomplete'] ?? '';
		$aria_label    = $attributes['ariaLabel'] ?? '';
		$required      = ! empty( $rules['required'] );
		$min_length    = $rules['minLength'] ?? 0;
		$max_length    = $rules['maxLength'] ?? 0;
		$pattern       = $rules['pattern'] ?? '';
		$min           = $rules['min'] ?? null;
		$max           = $rules['max'] ?? null;

		$is_number     = 'number' === $this->input_type;
		$supports_mask = ! in_array( $this->input_type, [ 'number', 'email', 'url' ], true );
		$step          = $is_number ? ( $attributes['step'] ?? 1 ) : 0;
		$mask          = $supports_mask ? ( $attributes['mask'] ?? '' ) : '';

		$conditional_attrs = [
			'{required}'        => $required ? 'required' : '',
			'{placeholder}'     => $placeholder ? sprintf( 'placeholder="%s"', esc_attr( $placeholder ) ) : '',
			'{autocomplete}'    => $autocomplete ? sprintf( 'autocomplete="%s"', esc_attr( $autocomplete ) ) : '',
			'{min_length}'      => $min_length ? sprintf( 'minlength="%d"', esc_attr( $min_length ) ) : '',
			'{max_length}'      => $max_length ? sprintf( 'maxlength="%d"', esc_attr( $max_length ) ) : '',
			'{step}'            => $step ? sprintf( 'step="%s"', esc_attr( $step ) ) : '',
			'{min}'             => null !== $min ? sprintf( 'min="%s"', esc_attr( $min ) ) : '',
			'{max}'             => null !== $max ? sprintf( 'max="%s"', esc_attr( $max ) ) : '',
			'{pattern}'         => $pattern ? sprintf( 'pattern="%s"', esc_attr( $pattern ) ) : '',
			'{mask_attribute}'  => $mask ? sprintf( 'data-inputmask="\'mask\': \'%s\'"', esc_attr( $mask ) ) : '',
			'{mask_directive}'  => $mask ? 'data-wp-init--mask="callbacks.initMask"' : '',
			'{aria_required}'   => $required ? 'aria-required="true"' : '',
			'{aria_label}'      => $aria_label ? sprintf( 'aria-label="%s"', esc_attr( $aria_label ) ) : '',
			'{aria_labelledby}' => $label_id ? sprintf( 'aria-labelledby="%s"', esc_attr( $label_id ) ) : '',
		];

		$template = '<input
			type="{type}"
			id="{id}"
			name="{name}"
			value="{value}"
			{required}
			{placeholder}
			{autocomplete}
			{min_length}
			{max_length}
			{step}
			{min}
			{max}
			{pattern}
			{mask_attribute}
			{mask_directive}
			{aria_required}
			{aria_label}
			{aria_labelledby}
			class="osf-field__input osf-field__input--{type}"
			data-wp-bind--value="context.value"
			data-wp-bind--aria-invalid="!context.isValid"
			data-wp-bind--aria-describedby="state.fieldAriaDescribedByAttribute"
			data-wp-on--focus="actions.handleFieldFocus"
			data-wp-on--blur="actions.handleFieldBlur"
			data-wp-on--change="actions.handleFieldChange"
			data-wp-init--register="callbacks.registerField"
			data-wp-on--osf-field-validate="actions.handleFieldValidate"
			data-wp-on--osf-field-server-error="actions.handleFieldServerErrors"
		/>';

		$replacements = array_merge(
			[
				'{type}'  => esc_attr( $this->input_type ),
				'{id}'    => esc_attr( $field_id ),
				'{name}'  => esc_attr( $field_name ),
				'{value}' => esc_attr( $default_value ),
			],
			$conditional_attrs
		);

		$markup = strtr( $template, $replacements );

		$markup = preg_replace( '/\s+/', ' ', $markup );
		$markup = trim( $markup );

		return $markup;
	}
}
