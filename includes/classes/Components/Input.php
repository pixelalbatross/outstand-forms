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

		$default_value = $attributes['defaultValue'] ?? '';
		$required      = $attributes['required'] ?? false;
		$placeholder   = $attributes['placeholder'] ?? '';
		$autocomplete  = $attributes['autocomplete'] ?? '';
		$min_length    = $attributes['minLength'] ?? 0;
		$max_length    = $attributes['maxLength'] ?? 0;
		$step          = $attributes['step'] ?? 1;
		$min           = $attributes['min'] ?? 0;
		$max           = $attributes['max'] ?? 0;
		$pattern       = $attributes['pattern'] ?? '';
		$mask          = $attributes['mask'] ?? '';
		$aria_label    = $attributes['ariaLabel'] ?? '';

		$conditional_attrs = [
			'{required}'        => $required ? 'required' : '',
			'{placeholder}'     => $placeholder ? sprintf( 'placeholder="%s"', esc_attr( $placeholder ) ) : '',
			'{autocomplete}'    => $autocomplete ? sprintf( 'autocomplete="%s"', esc_attr( $autocomplete ) ) : '',
			'{min_length}'      => $min_length ? sprintf( 'minlength="%d"', esc_attr( $min_length ) ) : '',
			'{max_length}'      => $max_length ? sprintf( 'maxlength="%d"', esc_attr( $max_length ) ) : '',
			'{step}'            => '',
			'{min}'             => '',
			'{max}'             => '',
			'{pattern}'         => $pattern ? sprintf( 'pattern="%s"', esc_attr( $pattern ) ) : '',
			'{mask_attribute}'  => $mask ? sprintf( 'data-inputmask="\'mask\': \'%s\'"', esc_attr( $mask ) ) : '',
			'{mask_directive}'  => $mask ? 'data-wp-init--mask="callbacks.initMask"' : '',
			'{aria_required}'   => $required ? 'aria-required="true"' : '',
			'{aria_label}'      => $aria_label ? sprintf( 'aria-label="%s"', esc_attr( $aria_label ) ) : '',
			'{aria_labelledby}' => $label_id ? sprintf( 'aria-labelledby="%s"', esc_attr( $label_id ) ) : '',
		];

		switch ( $this->input_type ) {
			case 'number':
				$conditional_attrs['{min_length}']     = '';
				$conditional_attrs['{max_length}']     = '';
				$conditional_attrs['{step}']           = $step ? sprintf( 'step="%d"', esc_attr( $step ) ) : '';
				$conditional_attrs['{min}']            = $min ? sprintf( 'min="%d"', esc_attr( $min ) ) : '';
				$conditional_attrs['{max}']            = $max ? sprintf( 'max="%d"', esc_attr( $max ) ) : '';
				$conditional_attrs['{pattern}']        = '';
				$conditional_attrs['{mask_attribute}'] = '';
				$conditional_attrs['{mask_directive}'] = '';
				break;

			case 'email':
				$conditional_attrs['{mask_attribute}'] = '';
				$conditional_attrs['{mask_directive}'] = '';
				break;

			case 'url':
				$conditional_attrs['{mask_attribute}'] = '';
				$conditional_attrs['{mask_directive}'] = '';
				break;
		}

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
