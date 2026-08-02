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
	 * Shared validator instance.
	 *
	 * @var ?Validator
	 */
	private static ?Validator $instance = null;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->register_default_validators();
	}

	/**
	 * Retrieve the shared validator.
	 *
	 * The instance is built once and passed through a filter so third parties
	 * can register additional validators that every submission path sees.
	 *
	 * @return Validator
	 */
	public static function instance(): Validator {

		if ( null === self::$instance ) {
			$validator = new self();

			/**
			 * Filters the shared validator.
			 *
			 * Register additional validators on the passed validator, or return
			 * a different validator instance entirely.
			 *
			 * @param Validator $validator The validator.
			 * @return Validator
			 */
			self::$instance = apply_filters( 'outstand_forms_validator', $validator );
		}

		return self::$instance;
	}

	/**
	 * Discard the shared validator so the next call rebuilds and refilters it.
	 *
	 * Needed when validators are registered after the validator has already
	 * been built (late-loading integrations, tests).
	 *
	 * @return void
	 */
	public static function reset_instance(): void {
		self::$instance = null;
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
		$this->register( 'options', [ $this, 'validate_options' ] );
		$this->register( 'minSelected', [ $this, 'validate_min_selected' ] );
		$this->register( 'maxSelected', [ $this, 'validate_max_selected' ] );
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

		// A multi-value field arrives as an array, where "filled in" means at
		// least one box ticked. An array of empty strings is nothing ticked.
		if ( is_array( $value ) ) {
			return ! empty( array_filter( $value, fn( mixed $item ): bool => ! $this->is_absent( $item ) ) );
		}

		return true;
	}

	/**
	 * Validate that every submitted value is one the field offers.
	 *
	 * The allowlist is built from the field's own options, so this is what
	 * separates a real choice from a forged or stale one. An empty config
	 * disables the rule, which is what a field with no options authored yet
	 * has.
	 *
	 * @param mixed $value  The value.
	 * @param array $params Parameters (unused).
	 * @param mixed $config Rule config (allowed values).
	 * @return bool
	 */
	protected function validate_options( mixed $value, array $params, mixed $config ): bool {

		if ( ! is_array( $config ) || empty( $config ) || $this->is_absent( $value ) ) {
			return true;
		}

		$allowed   = array_map( 'strval', $config );
		$submitted = array_map( 'strval', is_array( $value ) ? $value : [ $value ] );

		foreach ( $submitted as $item ) {
			// An unticked group submits nothing rather than an empty item, so a
			// blank here is a caller normalizing absence; `required` is what
			// decides whether that is acceptable.
			if ( '' === $item ) {
				continue;
			}

			if ( ! in_array( $item, $allowed, true ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Validate the minimum number of selected values.
	 *
	 * @param mixed $value  The value.
	 * @param array $params Parameters (unused).
	 * @param mixed $config Rule config (minimum count).
	 * @return bool
	 */
	protected function validate_min_selected( mixed $value, array $params, mixed $config ): bool {

		if ( ! is_numeric( $config ) || $config < 1 ) {
			return true;
		}

		return $this->count_selected( $value ) >= (int) $config;
	}

	/**
	 * Validate the maximum number of selected values.
	 *
	 * @param mixed $value  The value.
	 * @param array $params Parameters (unused).
	 * @param mixed $config Rule config (maximum count).
	 * @return bool
	 */
	protected function validate_max_selected( mixed $value, array $params, mixed $config ): bool {

		if ( ! is_numeric( $config ) || $config < 1 ) {
			return true;
		}

		return $this->count_selected( $value ) <= (int) $config;
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
		if ( ! $config || $this->is_absent( $value ) ) {
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
		if ( ! $config || $this->is_absent( $value ) ) {
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
		if ( $this->is_absent( $value ) || ! is_numeric( $config ) ) {
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
		if ( $this->is_absent( $value ) || ! is_numeric( $config ) ) {
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
	 * The pattern must match the entire value, mirroring the HTML `pattern`
	 * attribute semantics and the client-side validator in src/validation.js.
	 *
	 * @param mixed $value  The value.
	 * @param array $params Parameters (unused).
	 * @param mixed $config Rule config (regex pattern).
	 * @return bool
	 */
	protected function validate_pattern( mixed $value, array $params, mixed $config ): bool {
		if ( $this->is_absent( $value ) || ! is_string( $config ) || '' === $config ) {
			return true;
		}

		// Use ASCII SOH delimiter to avoid conflicts with patterns containing '/'.
		$delimiter = chr( 1 );

		// The `u` modifier makes matching unicode-aware, mirroring the
		// UTF-16-aware RegExp behavior in src/validation.js. With `u`,
		// preg_match() returns false (not 0) when the pattern or subject is
		// not valid UTF-8; that must fail closed as invalid, not as a match.
		// Suppress warnings from invalid regex.
		// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		$result = @preg_match( $delimiter . '^(?:' . $config . ')$' . $delimiter . 'u', (string) $value );

		return 1 === $result;
	}

	/**
	 * Determine whether a value counts as "absent" for rule guards.
	 *
	 * A value is only considered absent when it is an empty string or null.
	 * Falsy-but-present values such as the string "0", the number 0, or the
	 * boolean false are treated as real values and must go through the
	 * relevant rule, mirroring the client-side validator in src/validation.js.
	 *
	 * @param mixed $value The value to check.
	 * @return bool
	 */
	private function is_absent( mixed $value ): bool {
		return '' === $value || null === $value;
	}

	/**
	 * Count how many choices a submission actually carries.
	 *
	 * Blank items are not selections: an unticked group can arrive as `''` or
	 * as `[ '' ]` depending on the path, and neither means one box ticked.
	 *
	 * @param mixed $value The submitted value.
	 * @return int
	 */
	private function count_selected( mixed $value ): int {

		if ( $this->is_absent( $value ) ) {
			return 0;
		}

		$values = is_array( $value ) ? $value : [ $value ];

		return count( array_filter( $values, fn( mixed $item ): bool => ! $this->is_absent( $item ) ) );
	}
}
