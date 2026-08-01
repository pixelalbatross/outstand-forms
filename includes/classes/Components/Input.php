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

		$html_attributes = [
			'type'                => $this->input_type,
			'id'                  => $field_id,
			'name'                => $field_name,
			'value'               => $default_value,
			'required'            => $required,
			'placeholder'         => $placeholder ?: null,
			'autocomplete'        => $autocomplete ?: null,
			'minlength'           => $min_length ?: null,
			'maxlength'           => $max_length ?: null,
			'step'                => $step ?: null,
			'min'                 => $min,
			'max'                 => $max,
			'pattern'             => $pattern ?: null,
			'data-inputmask'      => $mask ? "'mask': '{$mask}'" : null,
			'data-wp-init---mask' => $mask ? 'callbacks.initMask' : null,
			'aria-required'       => $required ? 'true' : null,
			'aria-label'          => $aria_label ?: null,
			'aria-labelledby'     => $label_id ?: null,
			'class'               => "osf-field__input osf-field__input--{$this->input_type}",
		] + $this->get_interactivity_directives();

		return sprintf( '<input %s />', $this->build_attributes( $html_attributes ) );
	}
}
