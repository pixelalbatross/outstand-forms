<?php

namespace Outstand\WP\Forms\Validation;

class Validator {

	/**
	 * Email validation regex based on the HTML5 spec.
	 *
	 * Must match the regex used in src/validation.js so that client-side
	 * and server-side validation produce identical results.
	 *
	 * @see https://html.spec.whatwg.org/multipage/input.html#valid-e-mail-address
	 *
	 * @var string
	 */
	private const EMAIL_REGEX = '/^[a-zA-Z0-9.!#$%&\'*+\/=?^_`{|}~-]+@[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*$/';

	/**
	 * URL validation regex requiring http or https scheme.
	 *
	 * Must match the regex used in src/validation.js so that client-side
	 * and server-side validation produce identical results.
	 *
	 * @var string
	 */
	private const URL_REGEX = '/^https?:\/\/(?:(?:[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?\.)+[a-zA-Z]{2,}|localhost|\d{1,3}(?:\.\d{1,3}){3})(?::\d{1,5})?(?:\/[^\s]*)?$/i';

	/**
	 * Registered validators.
	 *
	 * @var array<string, callable>
	 */
	protected array $validators = [];

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->register_default_validators();
	}

	/**
	 * Register default validators.
	 *
	 * @return void
	 */
	protected function register_default_validators(): void {
		$this->register( 'required', [ $this, 'validate_required' ] );
		$this->register( 'email', [ $this, 'validate_email' ] );
		$this->register( 'url', [ $this, 'validate_url' ] );
		$this->register( 'minLength', [ $this, 'validate_min_length' ] );
		$this->register( 'maxLength', [ $this, 'validate_max_length' ] );
		$this->register( 'min', [ $this, 'validate_min' ] );
		$this->register( 'max', [ $this, 'validate_max' ] );
		$this->register( 'pattern', [ $this, 'validate_pattern' ] );
	}

	/**
	 * Register a validator.
	 *
	 * @param string   $name     Validator name.
	 * @param callable $callback Validator callback.
	 * @return void
	 */
	public function register( string $name, callable $callback ): void {
		$this->validators[ $name ] = $callback;
	}

	/**
	 * Validate a value against rules.
	 *
	 * @param mixed $value The value to validate.
	 * @param array $rules The validation rules.
	 * @return array{is_valid: bool, errors: array<string>}
	 */
	public function validate( mixed $value, array $rules ): array {
		$errors = [];

		foreach ( $rules as $rule_name => $rule_config ) {
			// Skip disabled rules.
			if ( false === $rule_config ) {
				continue;
			}

			if ( ! isset( $this->validators[ $rule_name ] ) ) {
				continue;
			}

			$validator = $this->validators[ $rule_name ];
			$params    = true === $rule_config ? [] : (array) $rule_config;

			if ( ! call_user_func( $validator, $value, $params, $rule_config ) ) {
				$errors[] = $rule_name;
			}
		}

		return [
			'is_valid' => empty( $errors ),
			'errors'   => $errors,
		];
	}

	/**
	 * Validate required field.
	 *
	 * @param mixed $value  The value.
	 * @param array $params Parameters (unused).
	 * @param mixed $config Rule config.
	 * @return bool
	 */
	protected function validate_required( mixed $value, array $params, mixed $config ): bool {
		if ( ! $config ) {
			return true;
		}

		if ( null === $value ) {
			return false;
		}

		if ( is_string( $value ) ) {
			return '' !== trim( $value );
		}

		return true;
	}

	/**
	 * Validate email.
	 *
	 * @param mixed $value  The value.
	 * @param array $params Parameters (unused).
	 * @param mixed $config Rule config.
	 * @return bool
	 */
	protected function validate_email( mixed $value, array $params, mixed $config ): bool {
		if ( ! $config || empty( $value ) ) {
			return true;
		}

		return (bool) preg_match( self::EMAIL_REGEX, (string) $value );
	}

	/**
	 * Validate URL.
	 *
	 * @param mixed $value  The value.
	 * @param array $params Parameters (unused).
	 * @param mixed $config Rule config.
	 * @return bool
	 */
	protected function validate_url( mixed $value, array $params, mixed $config ): bool {
		if ( ! $config || empty( $value ) ) {
			return true;
		}

		return (bool) preg_match( self::URL_REGEX, (string) $value );
	}

	/**
	 * Validate minimum length.
	 *
	 * @param mixed $value  The value.
	 * @param array $params Parameters (unused).
	 * @param mixed $config Rule config (minimum length).
	 * @return bool
	 */
	protected function validate_min_length( mixed $value, array $params, mixed $config ): bool {
		if ( empty( $value ) || ! is_numeric( $config ) ) {
			return true;
		}

		return mb_strlen( (string) $value, 'UTF-8' ) >= (int) $config;
	}

	/**
	 * Validate maximum length.
	 *
	 * @param mixed $value  The value.
	 * @param array $params Parameters (unused).
	 * @param mixed $config Rule config (maximum length).
	 * @return bool
	 */
	protected function validate_max_length( mixed $value, array $params, mixed $config ): bool {
		if ( empty( $value ) || ! is_numeric( $config ) ) {
			return true;
		}

		return mb_strlen( (string) $value, 'UTF-8' ) <= (int) $config;
	}

	/**
	 * Validate minimum value.
	 *
	 * @param mixed $value  The value.
	 * @param array $params Parameters (unused).
	 * @param mixed $config Rule config (minimum value).
	 * @return bool
	 */
	protected function validate_min( mixed $value, array $params, mixed $config ): bool {
		if ( ! is_numeric( $value ) || ! is_numeric( $config ) ) {
			return true;
		}

		return (float) $value >= (float) $config;
	}

	/**
	 * Validate maximum value.
	 *
	 * @param mixed $value  The value.
	 * @param array $params Parameters (unused).
	 * @param mixed $config Rule config (maximum value).
	 * @return bool
	 */
	protected function validate_max( mixed $value, array $params, mixed $config ): bool {
		if ( ! is_numeric( $value ) || ! is_numeric( $config ) ) {
			return true;
		}

		return (float) $value <= (float) $config;
	}

	/**
	 * Validate against a regex pattern.
	 *
	 * @param mixed $value  The value.
	 * @param array $params Parameters (unused).
	 * @param mixed $config Rule config (regex pattern).
	 * @return bool
	 */
	protected function validate_pattern( mixed $value, array $params, mixed $config ): bool {
		if ( empty( $value ) || ! is_string( $config ) || '' === $config ) {
			return true;
		}

		// Use ASCII SOH delimiter to avoid conflicts with patterns containing '/'.
		$delimiter = chr( 1 );

		// Suppress warnings from invalid regex.
		// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		return (bool) @preg_match( $delimiter . $config . $delimiter, (string) $value );
	}
}
