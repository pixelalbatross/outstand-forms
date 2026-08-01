<?php

namespace Outstand\WP\Forms;

use InvalidArgumentException;
use Outstand\WP\Forms\Components\ComponentInterface;
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
	 * Get the built-in field type definitions.
	 *
	 * @return array<string, array>
	 */
	protected function get_default_field_types(): array {

		return [
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
			'tel'      => [],
			'text'     => [],
			'textarea' => [
				'component' => static function ( FieldInterface $field ): ComponentInterface {
					return new TextareaComponent( $field );
				},
				'sanitize'  => 'sanitize_textarea_field',
			],
			'url'      => [
				'rules'    => [ 'url' => true ],
				'sanitize' => 'esc_url_raw',
			],
		];
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
