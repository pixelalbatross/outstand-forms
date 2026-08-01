<?php

namespace Outstand\WP\Forms\Components;

use Outstand\WP\Forms\Components\ComponentInterface;
use Outstand\WP\Forms\Fields\FieldInterface;

abstract class AbstractComponent implements ComponentInterface {

	/**
	 * Field instance.
	 *
	 * @var FieldInterface
	 */
	protected FieldInterface $field;

	/**
	 * Constructor.
	 *
	 * @param FieldInterface $field Field instance.
	 */
	public function __construct( FieldInterface $field ) {
		$this->field = $field;
	}

	/**
	 * Get the field attributes.
	 *
	 * @return array
	 */
	protected function get_attributes(): array {
		return $this->field->get_attributes();
	}

	/**
	 * Get the field type.
	 *
	 * @return string
	 */
	protected function get_field_type(): string {
		return $this->field->get_type();
	}

	/**
	 * Get the field ID.
	 *
	 * @return string
	 */
	protected function get_field_id(): string {
		return $this->field->get_field_id();
	}

	/**
	 * Get the field name.
	 *
	 * @return string
	 */
	protected function get_field_name(): string {
		return $this->field->get_field_name();
	}

	/**
	 * Get the field label ID.
	 *
	 * @return string
	 */
	protected function get_field_label_id(): string {
		return $this->field->get_label_id();
	}

	/**
	 * Get the field help text ID.
	 *
	 * @return string
	 */
	protected function get_field_help_text_id(): string {
		return $this->field->get_help_text_id();
	}

	/**
	 * Get the field error ID.
	 *
	 * @return string
	 */
	protected function get_field_error_id(): string {
		return $this->field->get_error_id();
	}

	/**
	 * Get the Interactivity API directives shared by all field controls.
	 *
	 * @return array
	 */
	protected function get_interactivity_directives(): array {
		return [
			'data-wp-bind--value'                => 'context.value',
			'data-wp-bind--aria-invalid'         => '!context.isValid',
			'data-wp-bind--aria-describedby'     => 'state.fieldAriaDescribedByAttribute',
			'data-wp-on--focus'                  => 'actions.handleFieldFocus',
			'data-wp-on--blur'                   => 'actions.handleFieldBlur',
			'data-wp-on--change'                 => 'actions.handleFieldChange',
			'data-wp-init--register'             => 'callbacks.registerField',
			'data-wp-on--osf-field-validate'     => 'actions.handleFieldValidate',
			'data-wp-on--osf-field-server-error' => 'actions.handleFieldServerErrors',
		];
	}

	/**
	 * Build an HTML attribute string from an associative array.
	 *
	 * Entries whose value is `null` or `false` are omitted. Entries whose
	 * value is `true` are rendered as bare boolean attributes.
	 *
	 * @param array $attributes Attribute name/value pairs.
	 * @return string
	 */
	protected function build_attributes( array $attributes ): string {
		$pairs = [];

		foreach ( $attributes as $name => $value ) {
			if ( null === $value || false === $value ) {
				continue;
			}

			if ( true === $value ) {
				$pairs[] = esc_attr( $name );
				continue;
			}

			$pairs[] = sprintf( '%s="%s"', esc_attr( $name ), esc_attr( $value ) );
		}

		return implode( ' ', $pairs );
	}
}
