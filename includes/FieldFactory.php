<?php

namespace Outstand\WP\Forms;

use InvalidArgumentException;
use Outstand\WP\Forms\Components\Checkbox as CheckboxComponent;
use Outstand\WP\Forms\Components\Choice as ChoiceComponent;
use Outstand\WP\Forms\Components\ComponentInterface;
use Outstand\WP\Forms\Components\GroupComponentInterface;
use Outstand\WP\Forms\Components\Select as SelectComponent;
use Outstand\WP\Forms\Components\Textarea as TextareaComponent;
use Outstand\WP\Forms\Fields\Field;
use Outstand\WP\Forms\Fields\FieldInterface;

/**
 * Registry of field types.
 *
 * A field type is a definition array, not a class. See register() for the
 * supported keys. Use FieldFactory::instance() to reach the shared, filtered
 * registry that rendering, parsing and sanitization all read from.
 */
class FieldFactory {

	/**
	 * Field type definitions keyed by type.
	 *
	 * @var array<string, array>
	 */
	protected array $field_types = [];

	/**
	 * Shared factory instance.
	 *
	 * @var ?FieldFactory
	 */
	private static ?FieldFactory $instance = null;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->field_types = $this->get_default_field_types();
	}

	/**
	 * Retrieve the shared field factory.
	 *
	 * The instance is built once and passed through a filter so third parties
	 * can register additional field types that rendering, validation and
	 * sanitization all see.
	 *
	 * @return FieldFactory
	 */
	public static function instance(): FieldFactory {

		if ( null === self::$instance ) {
			$factory = new self();

			/**
			 * Filters the shared field factory.
			 *
			 * Register additional field types on the passed factory, or return
			 * a different factory instance entirely.
			 *
			 * @param FieldFactory $factory The field factory.
			 * @return FieldFactory
			 */
			self::$instance = apply_filters( 'outstand_forms_field_factory', $factory );
		}

		return self::$instance;
	}

	/**
	 * Discard the shared factory so the next call rebuilds and refilters it.
	 *
	 * Needed when field types are registered after the factory has already
	 * been built (late-loading integrations, tests).
	 *
	 * @return void
	 */
	public static function reset_instance(): void {
		self::$instance = null;
	}

	/**
	 * Register a field type.
	 *
	 * The definition supports these optional keys:
	 *
	 * - `component`: callable( FieldInterface $field ): ComponentInterface.
	 *   Builds the control. Defaults to a text-style `<input>` of this type.
	 * - `rules`: array merged into the base validation rules, or
	 *   callable( array $rules, array $attributes ): array for full control.
	 * - `sanitize`: callable( mixed $value ): mixed. Defaults to
	 *   `sanitize_text_field`.
	 * - `control`: string naming the editor control that renders this type
	 *   (e.g. `input`, `textarea`). Read by the block editor, not by
	 *   rendering or validation. Defaults to `input`.
	 * - `label`: string shown for this type in the block editor. Defaults to
	 *   the type key with its first letter capitalized.
	 *
	 * @param string $type       Field type.
	 * @param array  $definition Type definition.
	 * @return void
	 * @throws InvalidArgumentException If the type or definition is invalid.
	 */
	public function register( string $type, array $definition ): void {

		if ( '' === $type ) {
			throw new InvalidArgumentException( 'Field type must not be empty' ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
		}

		$component = $definition['component'] ?? null;
		if ( null !== $component && ! is_callable( $component ) ) {
			throw new InvalidArgumentException( "Field type {$type}: component must be callable" ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
		}

		$rules = $definition['rules'] ?? [];
		if ( ! is_array( $rules ) && ! is_callable( $rules ) ) {
			throw new InvalidArgumentException( "Field type {$type}: rules must be an array or callable" ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
		}

		$sanitize = $definition['sanitize'] ?? null;
		if ( null !== $sanitize && ! is_callable( $sanitize ) ) {
			throw new InvalidArgumentException( "Field type {$type}: sanitize must be callable" ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
		}

		$control = $definition['control'] ?? null;
		if ( null !== $control && ! is_string( $control ) ) {
			throw new InvalidArgumentException( "Field type {$type}: control must be a string" ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
		}

		$label = $definition['label'] ?? null;
		if ( null !== $label && ! is_string( $label ) ) {
			throw new InvalidArgumentException( "Field type {$type}: label must be a string" ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
		}

		$this->field_types[ $type ] = $definition;
	}

	/**
	 * Create a field instance.
	 *
	 * @param string $type       Field type.
	 * @param array  $attributes Field attributes.
	 * @return FieldInterface
	 * @throws InvalidArgumentException If the field type is not supported.
	 */
	public function create( string $type, array $attributes ): FieldInterface {

		if ( ! isset( $this->field_types[ $type ] ) ) {
			throw new InvalidArgumentException( "Unsupported field type: {$type}" ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
		}

		return new Field( $type, $attributes, $this->field_types[ $type ] );
	}

	/**
	 * Check if a field type is supported.
	 *
	 * @param string $type Field type.
	 * @return bool
	 */
	public function supports( string $type ): bool {
		return isset( $this->field_types[ $type ] );
	}

	/**
	 * Sanitize a submitted value using the sanitizer of its field type.
	 *
	 * Unknown types fall back to `sanitize_text_field`, matching the default
	 * applied to registered types that declare no sanitizer.
	 *
	 * @param string $type  Field type.
	 * @param mixed  $value The submitted value.
	 * @return mixed
	 */
	public function sanitize( string $type, mixed $value ): mixed {
		$sanitizer = $this->field_types[ $type ]['sanitize'] ?? 'sanitize_text_field';

		return call_user_func( $sanitizer, $value );
	}

	/**
	 * Get the registered field types, formatted for the block editor.
	 *
	 * Rendering, validation and sanitization all read `$field_types`
	 * directly; this is the one accessor meant for consumers outside the
	 * class, e.g. localizing the registry to the block editor so it can
	 * offer every registered type, not just the built-ins.
	 *
	 * @return array<int, array{type: string, label: string, control: string}>
	 */
	public function get_registered_types(): array {

		$types = [];

		foreach ( $this->field_types as $type => $definition ) {
			$types[] = [
				'type'    => $type,
				'label'   => $definition['label'] ?? ucfirst( $type ),
				'control' => $definition['control'] ?? 'input',
				// Whether the type renders several inputs under one name. The
				// editor needs to know: a group labels a list rather than a
				// single control, so it cannot take an inline label.
				'group'   => $this->create( $type, [] )->get_component( 'field' ) instanceof GroupComponentInterface,
			];
		}

		return $types;
	}

	/**
	 * Get the built-in field type definitions.
	 *
	 * @return array<string, array>
	 */
	protected function get_default_field_types(): array {

		return [
			'checkbox' => [
				'component' => static function ( FieldInterface $field ): ComponentInterface {
					return new ChoiceComponent( $field, 'checkbox' );
				},
				'rules'     => static function ( array $rules, array $attributes ): array {
					return self::get_choice_rules( $rules, $attributes, true );
				},
				'sanitize'  => static function ( mixed $value ): array {
					return self::sanitize_choices( $value );
				},
				'control'   => 'checkbox',
				'label'     => __( 'Checkboxes', 'outstand-forms' ),
			],
			'consent'  => [
				'component' => static function ( FieldInterface $field ): ComponentInterface {
					return new CheckboxComponent( $field );
				},
				'rules'     => static function ( array $rules, array $attributes ): array {
					$rules = self::get_choice_rules( $rules, $attributes );

					// A consent box submits one value or nothing at all, so the
					// allowlist is fixed rather than authored.
					$rules['options'] = [ CheckboxComponent::CHECKED_VALUE ];

					return $rules;
				},
				'control'   => 'consent',
				'label'     => __( 'Consent', 'outstand-forms' ),
			],
			'email'    => [
				'rules'    => [ 'email' => true ],
				'sanitize' => 'sanitize_email',
			],
			'number'   => [
				'rules'    => static function ( array $rules, array $attributes ): array {
					return self::get_number_rules( $rules, $attributes );
				},
				// A non-numeric submission becomes null rather than 0, so a
				// required number field still fails the `required` rule.
				'sanitize' => static function ( mixed $value ): ?float {
					return is_numeric( $value ) ? (float) $value : null;
				},
			],
			'password' => [],
			'radio'    => [
				'component' => static function ( FieldInterface $field ): ComponentInterface {
					return new ChoiceComponent( $field, 'radio' );
				},
				'rules'     => static function ( array $rules, array $attributes ): array {
					return self::get_choice_rules( $rules, $attributes );
				},
				'control'   => 'radio',
				'label'     => __( 'Radio', 'outstand-forms' ),
			],
			'select'   => [
				'component' => static function ( FieldInterface $field ): ComponentInterface {
					return new SelectComponent( $field );
				},
				'rules'     => static function ( array $rules, array $attributes ): array {
					return self::get_choice_rules( $rules, $attributes );
				},
				'control'   => 'select',
				'label'     => __( 'Select', 'outstand-forms' ),
			],
			'tel'      => [],
			'text'     => [],
			'textarea' => [
				'component' => static function ( FieldInterface $field ): ComponentInterface {
					return new TextareaComponent( $field );
				},
				'sanitize'  => 'sanitize_textarea_field',
				'control'   => 'textarea',
			],
			'url'      => [
				'rules'    => [ 'url' => true ],
				'sanitize' => 'esc_url_raw',
			],
		];
	}

	/**
	 * Build the validation rules for a choice type.
	 *
	 * Choice fields drop the string constraints and take an allowlist of the
	 * authored option values instead. Multi-value fields additionally take the
	 * selection-count bounds.
	 *
	 * @param array $rules       The base validation rules.
	 * @param array $attributes  The field attributes.
	 * @param bool  $is_multiple Whether the field accepts several values.
	 * @return array
	 */
	protected static function get_choice_rules( array $rules, array $attributes, bool $is_multiple = false ): array {

		// String constraints describe a typed value; a choice field's value is
		// picked from a fixed list, so they can never apply.
		unset( $rules['minLength'], $rules['maxLength'], $rules['pattern'] );

		$options = $attributes['options'] ?? [];

		// The allowlist is derived from the authored options rather than set by
		// hand: a submitted value that no option offers is a forged or stale
		// one, and fails instead of being quietly accepted.
		$rules['options'] = Options::get_values( $options );

		if ( ! $is_multiple ) {
			return $rules;
		}

		if ( ! empty( $attributes['minSelected'] ) ) {
			$rules['minSelected'] = (int) $attributes['minSelected'];
		}

		if ( ! empty( $attributes['maxSelected'] ) ) {
			$rules['maxSelected'] = (int) $attributes['maxSelected'];
		}

		return $rules;
	}

	/**
	 * Sanitize a multi-value choice submission.
	 *
	 * Shape only: every item becomes a sanitized string and the list is
	 * reindexed. Whether a value is one the field actually offers is the
	 * `options` rule's job, so a forged value survives sanitization and then
	 * fails validation with a message, rather than vanishing silently.
	 *
	 * @param mixed $value The submitted value.
	 * @return array<int, string>
	 */
	protected static function sanitize_choices( mixed $value ): array {

		$values = is_array( $value ) ? $value : [ $value ];

		$values = array_filter(
			$values,
			static function ( mixed $item ): bool {
				return is_scalar( $item );
			}
		);

		return array_values( array_map( 'sanitize_text_field', array_map( 'strval', $values ) ) );
	}

	/**
	 * Build the validation rules for the number type.
	 *
	 * Numbers drop the string constraints and take numeric bounds instead.
	 *
	 * @param array $rules      The base validation rules.
	 * @param array $attributes The field attributes.
	 * @return array
	 */
	protected static function get_number_rules( array $rules, array $attributes ): array {

		unset( $rules['minLength'], $rules['maxLength'], $rules['pattern'] );

		if ( ! empty( $attributes['min'] ) ) {
			$rules['min'] = (float) $attributes['min'];
		}

		if ( ! empty( $attributes['max'] ) ) {
			$rules['max'] = (float) $attributes['max'];
		}

		return $rules;
	}
}
