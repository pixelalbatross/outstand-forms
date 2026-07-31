<?php

namespace Outstand\WP\Forms\Components;

class Textarea extends AbstractComponent {

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
		$rows          = $attributes['rows'] ?? 2;
		$cols          = $attributes['cols'] ?? 20;
		$mask          = $attributes['mask'] ?? '';
		$aria_label    = $attributes['ariaLabel'] ?? '';
		$required      = ! empty( $rules['required'] );
		$min_length    = $rules['minLength'] ?? 0;
		$max_length    = $rules['maxLength'] ?? 0;

		$conditional_attrs = [
			'{required}'        => $required ? 'required' : '',
			'{placeholder}'     => $placeholder ? sprintf( 'placeholder="%s"', esc_attr( $placeholder ) ) : '',
			'{autocomplete}'    => $autocomplete ? sprintf( 'autocomplete="%s"', esc_attr( $autocomplete ) ) : '',
			'{min_length}'      => $min_length ? sprintf( 'minlength="%s"', esc_attr( $min_length ) ) : '',
			'{max_length}'      => $max_length ? sprintf( 'maxlength="%s"', esc_attr( $max_length ) ) : '',
			'{rows}'            => $rows ? sprintf( 'rows="%s"', esc_attr( $rows ) ) : '',
			'{cols}'            => $cols ? sprintf( 'cols="%s"', esc_attr( $cols ) ) : '',
			'{mask_attribute}'  => $mask ? sprintf( 'data-inputmask="\'mask\': \'%s\'"', esc_attr( $mask ) ) : '',
			'{mask_directive}'  => $mask ? 'data-wp-init--mask="callbacks.initMask"' : '',
			'{aria_required}'   => $required ? 'aria-required="true"' : '',
			'{aria_label}'      => $aria_label ? sprintf( 'aria-label="%s"', esc_attr( $aria_label ) ) : '',
			'{aria_labelledby}' => $label_id ? sprintf( 'aria-labelledby="%s"', esc_attr( $label_id ) ) : '',
		];

		$template = '<textarea
			id="{id}"
			name="{name}"
			value="{value}"
			{required}
			{placeholder}
			{autocomplete}
			{min_length}
			{max_length}
			{rows}
			{cols}
			{mask_attribute}
			{mask_directive}
			{aria_required}
			{aria_label}
			{aria_labelledby}
			class="osf-field__textarea"
			data-wp-bind--value="context.value"
			data-wp-bind--aria-invalid="!context.isValid"
			data-wp-bind--aria-describedby="state.fieldAriaDescribedByAttribute"
			data-wp-on--focus="actions.handleFieldFocus"
			data-wp-on--blur="actions.handleFieldBlur"
			data-wp-on--change="actions.handleFieldChange"
			data-wp-init--register="callbacks.registerField"
			data-wp-on--osf-field-validate="actions.handleFieldValidate"
		></textarea>';

		$replacements = array_merge(
			[
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
