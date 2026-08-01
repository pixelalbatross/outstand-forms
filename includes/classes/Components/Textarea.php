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

		$html_attributes = [
			'id'                 => $field_id,
			'name'               => $field_name,
			'required'           => $required,
			'placeholder'        => $placeholder ?: null,
			'autocomplete'       => $autocomplete ?: null,
			'minlength'          => $min_length ?: null,
			'maxlength'          => $max_length ?: null,
			'rows'               => $rows ?: null,
			'cols'               => $cols ?: null,
			'data-inputmask'     => $mask ? "'mask': '{$mask}'" : null,
			'data-wp-init--mask' => $mask ? 'callbacks.initMask' : null,
			'aria-required'      => $required ? 'true' : null,
			'aria-label'         => $aria_label ?: null,
			'aria-labelledby'    => $label_id ?: null,
			'class'              => 'osf-field__textarea',
		] + $this->get_interactivity_directives();

		return sprintf(
			'<textarea %s>%s</textarea>',
			$this->build_attributes( $html_attributes ),
			esc_textarea( $default_value )
		);
	}
}
