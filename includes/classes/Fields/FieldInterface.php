<?php

namespace Outstand\WP\Forms\Fields;

use Outstand\WP\Forms\Components\ComponentInterface;

interface FieldInterface {

	/**
	 * Get the field type.
	 *
	 * @return string
	 */
	public function get_type(): string;

	/**
	 * Get the field attributes.
	 *
	 * @return array
	 */
	public function get_attributes(): array;

	/**
	 * Initialize components for this field type.
	 *
	 * @return void
	 */
	public function initialize_components(): void;

	/**
	 * Get a specific component.
	 *
	 * @param string $name Component name.
	 * @return ComponentInterface|null
	 */
	public function get_component( string $name ): ?ComponentInterface;

	/**
	 * Get all components.
	 *
	 * @return array Array of ComponentInterface instances.
	 */
	public function get_components(): array;

	/**
	 * Get the field ID.
	 *
	 * @return string
	 */
	public function get_field_id(): string;

	/**
	 * Get the field name.
	 *
	 * @return string
	 */
	public function get_field_name(): string;

	/**
	 * Get the field label ID.
	 *
	 * @return string
	 */
	public function get_label_id(): string;

	/**
	 * Get the field help text ID.
	 *
	 * @return string
	 */
	public function get_help_text_id(): string;

	/**
	 * Get the field error ID.
	 *
	 * @return string
	 */
	public function get_error_id(): string;

	/**
	 * Get the validation rules.
	 *
	 * @return array
	 */
	public function get_validation_rules(): array;

	/**
	 * Render the field.
	 *
	 * @return void
	 */
	public function render(): void;
}
