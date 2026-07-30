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
}
