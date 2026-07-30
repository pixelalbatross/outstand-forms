<?php

namespace Outstand\WP\Forms;

use InvalidArgumentException;
use Outstand\WP\Forms\Fields\Email;
use Outstand\WP\Forms\Fields\FieldInterface;
use Outstand\WP\Forms\Fields\Number;
use Outstand\WP\Forms\Fields\Password;
use Outstand\WP\Forms\Fields\Phone;
use Outstand\WP\Forms\Fields\Text;
use Outstand\WP\Forms\Fields\Textarea;
use Outstand\WP\Forms\Fields\URL;

class FieldFactory {

	/**
	 * Field type mappings.
	 *
	 * @var array
	 */
	protected array $field_types = [
		'email'    => Email::class,
		'number'   => Number::class,
		'password' => Password::class,
		'tel'      => Phone::class,
		'text'     => Text::class,
		'textarea' => Textarea::class,
		'url'      => URL::class,
	];

	/**
	 * Register a new field type.
	 *
	 * @param string $type Field type.
	 * @param string $field_class Field class.
	 * @return void
	 * @throws InvalidArgumentException If the field class doesn't exist or doesn't implement FieldInterface.
	 */
	public function register( string $type, string $field_class ): void {

		if ( ! class_exists( $field_class ) ) {
			throw new InvalidArgumentException( "Field class {$field_class} does not exist" ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
		}

		if ( ! is_subclass_of( $field_class, FieldInterface::class ) ) {
			throw new InvalidArgumentException( 'Field class must implement FieldInterface' ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
		}

		$this->field_types[ $type ] = $field_class;
	}

	/**
	 * Create a field instance.
	 *
	 * @param string $type Field type.
	 * @param array  $attributes Field attributes.
	 * @return FieldInterface
	 * @throws InvalidArgumentException If the field type is not supported.
	 */
	public function create( string $type, array $attributes ): FieldInterface {

		if ( ! isset( $this->field_types[ $type ] ) ) {
			throw new InvalidArgumentException( "Unsupported field type: {$type}" ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
		}

		$field_class = $this->field_types[ $type ];

		return new $field_class( $attributes );
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
}
